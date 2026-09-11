<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Aforo;
use App\Models\AforoIndicadore;
use App\Services\AforoCotizadorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Editor de indicadores de explotación por aforo (réplica del legacy
 * `Indicadores.php`). Cada aforo tiene `viajes`, `tipo_indicadores` y hasta 7
 * filas normalizadas en `aforo_indicadores` (tn/km/tráfico). Los totales
 * denormalizados de `aforos` se recalculan como resumen de esas filas.
 */
class IndicadoresController extends Controller
{
    use EntidadScoping;

    /** Etiquetas de los tipos de indicadores (paridad legacy). */
    private const TIPOS = [
        1 => 'Normal',
        2 => '/Viajes',
        3 => 'Desagregado',
        4 => 'Manual',
    ];

    public function __construct(
        private readonly AforoCotizadorService $cotizador,
    ) {}

    /**
     * Grid de aforos del mes de operaciones con sus indicadores. Filtrado por
     * entidad (vía carta de porte) y búsqueda por folio de CP o cliente.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Aforo::class);

        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $anio = (int) Carbon::parse($fechaOperaciones)->year;
        $mes = (int) Carbon::parse($fechaOperaciones)->month;

        $query = Aforo::with([
            'cartaPorte:id,numero,id_hoja_ruta,id_solicitud',
            'cartaPorte.cliente',
            'cartaPorte.tractivo',
            'indicadoresFilas',
        ])
            ->whereYear('fecha_parte', $anio)
            ->whereMonth('fecha_parte', $mes);

        $query->when($request->search, function ($q, $s) {
            $q->where(function ($q2) use ($s) {
                $q2->whereHas('cartaPorte', fn ($q3) => $q3->where('numero', 'like', "%{$s}%"))
                    ->orWhereHas('cartaPorte.cliente', fn ($q3) => $q3->where('nombre', 'like', "%{$s}%"));
            });
        });

        $this->aplicarScopeEntidad($query);

        $aforos = $query->orderByDesc('fecha_parte')->orderByDesc('id')->paginate(30);

        return Inertia::render('Indicadores/Index', [
            'title' => 'Indicadores',
            'aforos' => $aforos,
            'tipos' => collect(self::TIPOS)->map(fn ($label, $id) => ['id' => $id, 'label' => $label])->values(),
            'filters' => $request->only(['search']),
            'fechaOperaciones' => $fechaOperaciones,
        ]);
    }

    /**
     * Guarda los indicadores de un aforo: `viajes`, `tipo_indicadores` y las
     * filas 1-7 en `aforo_indicadores` (reemplaza las previas). Recalcula los
     * totales denormalizados de `aforos` con el motor de cotización (paridad
     * `aforoCalcularIndicadores`).
     */
    public function update(Request $request, Aforo $aforo)
    {
        $this->authorize('update', $aforo);
        $this->autorizarEntidadAforo($aforo);

        $validated = $request->validate([
            'viajes' => 'nullable|integer|min:1',
            'tipo_indicadores' => 'nullable|integer|in:1,2,3,4',
            'indicadores_filas' => 'nullable|array|max:7',
            'indicadores_filas.*.tn_pos' => 'nullable|numeric',
            'indicadores_filas.*.tn_real' => 'nullable|numeric',
            'indicadores_filas.*.km_carga' => 'nullable|numeric',
            'indicadores_filas.*.km_vacio' => 'nullable|numeric',
            'indicadores_filas.*.km_total' => 'nullable|numeric',
            'indicadores_filas.*.traf_pos' => 'nullable|numeric',
            'indicadores_filas.*.traf_real' => 'nullable|numeric',
        ]);

        $viajes = (int) ($validated['viajes'] ?? 1);
        $tipo = (int) ($validated['tipo_indicadores'] ?? 1);
        $filas = collect($validated['indicadores_filas'] ?? [])->values()->all();

        // Totales como resumen de las filas (misma lógica que el legacy).
        $resumen = $this->cotizador->calcularIndicadores($tipo, $viajes, $filas);

        DB::transaction(function () use ($aforo, $viajes, $tipo, $filas, $resumen) {
            $aforo->update([
                'viajes' => $viajes,
                'tipo_indicadores' => $tipo,
                'tn_pos_total' => $resumen['tnpos_total'] ?? 0,
                'tn_real_total' => $resumen['tnreal_total'] ?? 0,
                'km_carga_total' => $resumen['kmcarga_total'] ?? 0,
                'km_vacio_total' => $resumen['kmvacio_total'] ?? 0,
                'km_total_total' => $resumen['kmtotal_total'] ?? 0,
                'traf_pos_total' => $resumen['trafpos_total'] ?? 0,
                'traf_real_total' => $resumen['trafreal_total'] ?? 0,
            ]);

            AforoIndicadore::where('id_aforo', $aforo->id)->delete();

            foreach ($filas as $i => $fila) {
                $datos = [
                    'tn_pos' => (float) ($fila['tn_pos'] ?? 0),
                    'tn_real' => (float) ($fila['tn_real'] ?? 0),
                    'km_carga' => (float) ($fila['km_carga'] ?? 0),
                    'km_vacio' => (float) ($fila['km_vacio'] ?? 0),
                    'km_total' => (float) ($fila['km_total'] ?? 0),
                    'traf_pos' => (float) ($fila['traf_pos'] ?? 0),
                    'traf_real' => (float) ($fila['traf_real'] ?? 0),
                ];

                if (! collect($datos)->contains(fn ($v) => $v != 0)) {
                    continue;
                }

                AforoIndicadore::create([
                    'id_aforo' => $aforo->id,
                    'posicion' => $i + 1,
                    ...$datos,
                ]);
            }
        });

        return redirect()->route('indicadores.index')->with('success', 'Indicadores actualizados correctamente.');
    }

    /**
     * Restringe la consulta de aforos a las entidades permitidas. La entidad
     * de un aforo se deriva de su carta de porte (hoja de ruta → tractivo, o
     * solicitud), igual que en `AforosController`.
     */
    private function aplicarScopeEntidad($query): void
    {
        $ids = $this->entidadesPermitidas();
        if (empty($ids)) {
            return;
        }

        $query->whereHas('cartaPorte', function ($q) use ($ids) {
            $q->whereHas('hojaRuta', fn ($h) => $h->whereIn('id_entidad', $ids))
                ->orWhereHas('hojaRuta.tractivo', fn ($t) => $t->whereIn('id_entidad', $ids))
                ->orWhereHas('solicitud', fn ($s) => $s->whereIn('id_entidad', $ids));
        });
    }

    /**
     * Aborta con 403 si el aforo pertenece a una entidad fuera de las permitidas.
     */
    private function autorizarEntidadAforo(Aforo $aforo): void
    {
        $ids = $this->entidadesPermitidas();
        if (empty($ids)) {
            return;
        }

        $pertenece = Aforo::whereKey($aforo->id)
            ->whereHas('cartaPorte', function ($q) use ($ids) {
                $q->whereHas('hojaRuta', fn ($h) => $h->whereIn('id_entidad', $ids))
                    ->orWhereHas('hojaRuta.tractivo', fn ($t) => $t->whereIn('id_entidad', $ids))
                    ->orWhereHas('solicitud', fn ($s) => $s->whereIn('id_entidad', $ids));
            })
            ->exists();

        if (! $pertenece) {
            abort(403, 'No tiene permiso para acceder a este registro.');
        }
    }
}
