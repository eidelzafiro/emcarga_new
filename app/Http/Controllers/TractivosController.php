<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Caja;
use App\Models\Diferenciale;
use App\Models\EstadoComponente;
use App\Models\Grupo;
use App\Models\Lubricante;
use App\Models\Motore;
use App\Models\TipoArrastre;
use App\Models\TipoVehiculo;
use App\Models\TipoTractivo;
use App\Models\Tractivo;
use App\Support\Catalogos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TractivosController extends Controller
{
    use EntidadScoping;

    /**
     * Display a listing of tractivos.
     */
    public function index(Request $request)
    {

        $this->authorize('viewAny', Tractivo::class);
        $tractivos = Tractivo::query()
            ->with([
                'motor:id,codigo,descripcion',
                'caja:id,codigo,descripcion',
                'diferencial:id,codigo,descripcion',
                'tipoCombustible:id,nombre',
                'amortizacion',
                'planes',
                'documentacion',
            ])
            ->when($request->grupo, function ($query, $grupo) {
                $query->where('id_grupo', $grupo);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('codigo', 'like', "%{$search}%")
                    ->orWhere('placa', 'like', "%{$search}%");
            })
            ->when(true, function ($q) {
                $entidades = $this->entidadesPermitidas();
                if (! empty($entidades)) {
                    $q->whereIn('id_entidad', $entidades);
                }

                return $q;
            })
            ->paginate(20);

        $tiposVehiculo = $this->combosTipoVehiculo();

        $tractivos->getCollection()->transform(function ($tractivo) use ($tiposVehiculo) {
            $tipo = collect($tiposVehiculo)->firstWhere('value', $tractivo->id_tipo_vehiculo);
            $tractivo->tipo_vehiculo_label = $tipo['label'] ?? ('Tipo '.$tractivo->id_tipo_vehiculo);
            $tractivo->tipo_equipo_label = $tipo['tipo_equipo'] ?? null;
            $tractivo->tipo_mtto_label = $tipo['tipo_mtto'] ?? null;

            // Nombres normalizados. El tipo de equipo se deriva del tipo de
            // vehículo (id_tipo_equipo ya no existe en la tabla tractivos).
            $tractivo->tipo_equipo_nombre = $tipo['tipo_equipo'] ?? null;
            $tractivo->tipo_combustible_nombre = $tractivo->tipoCombustible?->nombre;

            // Ficha heredada del tipo (marca/modelo/año) para el formulario.
            $tractivo->tipo_ficha = $tipo['ficha'] ?? null;

            // Fichas extraídas a tablas polimórficas (Fase C) para el formulario.
            $tractivo->amortmn = $tractivo->amortizacion?->amortmn;
            $tractivo->amortme = $tractivo->amortizacion?->amortme;
            $tractivo->vchapa = $tractivo->amortizacion?->vchapa;
            $tractivo->plan_comb = $tractivo->planes?->plan_comb;
            $tractivo->plan_tn = $tractivo->planes?->plan_tn;
            $tractivo->plan_viajes = $tractivo->planes?->plan_viajes;
            $tractivo->plan_gastos = $tractivo->planes?->plan_gastos;
            $tractivo->plan_cdt = $tractivo->planes?->plan_cdt;
            $tractivo->plan_diario = $tractivo->planes?->plan_diario;
            $tractivo->ficav = $tractivo->documentacion?->ficav;
            $tractivo->femision_ficav = $tractivo->documentacion?->femision_ficav;
            $tractivo->fvence_ficav = $tractivo->documentacion?->fvence_ficav;
            $tractivo->lot = $tractivo->documentacion?->lot;
            $tractivo->femision_lot = $tractivo->documentacion?->femision_lot;
            $tractivo->fvence_lot = $tractivo->documentacion?->fvence_lot;
            $tractivo->circulacion = $tractivo->documentacion?->circulacion;
            $tractivo->femision_circ = $tractivo->documentacion?->femision_circ;
            $tractivo->fvence_circ = $tractivo->documentacion?->fvence_circ;
            $tractivo->f_reconstruccion = $tractivo->documentacion?->f_reconstruccion;

            return $tractivo;
        });

        return Inertia::render('Tractivos/Index', [
            'title' => 'Vehículos',
            'tractivos' => $tractivos,
            'filters' => $request->only(['search', 'grupo']),
            'catalogos' => [
                'tiposVehiculo' => $this->combosTipoVehiculo(),
                'grupos' => $this->combosCatalogo('grupos'),
                'tiposServicio' => $this->combosCatalogo('tipos_servicio'),
                'colores' => $this->combosCatalogo('colores'),
                'estados' => $this->combos(EstadoComponente::class, 'nombre'),
                'lubricantes' => $this->combos(Lubricante::class, 'nombre'),
            ],
        ]);
    }

    private function combos(string $clase, string $campo): array
    {
        return $clase::query()
            ->orderBy($campo)
            ->get(['id', $campo])
            ->map(fn ($m) => ['value' => $m->id, 'label' => (string) $m->{$campo}])
            ->values()
            ->toArray();
    }

    private function combosCatalogo(string $tipo): array
    {
        return collect(Catalogos::opciones($tipo))
            ->map(fn ($o) => ['value' => $o['id'], 'label' => (string) $o['nombre']])
            ->values()
            ->toArray();
    }

    private function combosTipoVehiculo(): array
    {
        return TipoVehiculo::with(['marca', 'modelo', 'tipoEquipo', 'tipoMantenimiento', 'tipoTractivo', 'tipoArrastre'])
            ->where('clase', 'tractivo')
            ->orderBy('id')
            ->get()
            ->map(function ($tv) {
                $marca = $tv->marca?->nombre;
                $modelo = $tv->modelo?->nombre;
                $anio = $tv->fabricacion;
                $partes = array_filter([$marca, $modelo, $anio]);
                $etiqueta = $partes ? implode(' - ', $partes) : ('Tipo '.$tv->id);

                return [
                    'value' => $tv->id,
                    'label' => $etiqueta,
                    'tipo_equipo' => $tv->tipoEquipo?->nombre ?? null,
                    'tipo_mtto' => $tv->tipoMantenimiento?->nombre ?? null,
                    'ficha' => [
                        'marca' => $marca,
                        'modelo' => $modelo,
                        'anno' => $anio,
                    ],
                ];
            })
            ->values()
            ->toArray();
    }

    public function store(Request $request)
    {

        $this->authorize('create', Tractivo::class);
        $validated = $request->validate($this->reglas());

        // Las asociaciones motor/caja/diferencial son solo informativas desde
        // el codificador: el componente se crea/gestiona por regla de negocio.
        unset($validated['id_motor'], $validated['id_caja'], $validated['id_diferencial']);

        $validated['id_entidad'] = (int) entidadActivaId();
        $tractivo = Tractivo::create($validated);

        // Fichas polimórficas (amortización/planes/documentación) extraídas en Fase C.
        $tractivo->syncVehiculoExtra($validated);

        // Regla 2026-08-23: todo tractivo tiene SIEMPRE motor, caja y diferencial.
        app(\App\Services\AgregadosTractivoService::class)->asegurar($tractivo);

        return redirect()->route('tractivos.index')
            ->with('success', 'Tractivo creado correctamente.');
    }

    public function update(Request $request, Tractivo $tractivo)
    {

        $this->authorize('update', $tractivo);
        $this->autorizarEntidad($tractivo->id_entidad);

        $validated = $request->validate($this->reglas($tractivo->id));

        // Solo informativas desde el codificador (se cambian por Orden de Taller).
        unset($validated['id_motor'], $validated['id_caja'], $validated['id_diferencial']);

        $tractivo->update($validated);

        // Fichas polimórficas (amortización/planes/documentación) extraídas en Fase C.
        $tractivo->syncVehiculoExtra($validated);

        // Garantiza los 3 agregados si el tractivo quedó sin alguno.
        app(\App\Services\AgregadosTractivoService::class)->asegurar($tractivo);

        return redirect()->route('tractivos.index')
            ->with('success', 'Tractivo actualizado correctamente.');
    }

    private function reglas(?int $id = null): array
    {
        return [
            'placa' => 'required|string|max:50|unique:tractivos,placa'.($id ? ','.$id : ''),
            'id_tipo_vehiculo' => ['required', 'exists:tipo_vehiculos,id'],
            'id_motor' => 'nullable|exists:motores,id',
            'id_caja' => 'nullable|exists:cajas,id',
            'id_diferencial' => 'nullable|exists:diferenciales,id',
            'id_grupo' => 'required|exists:catalogo_items,id',
            'id_tipo_servicio' => 'nullable|exists:tipos_servicios,id',
            'id_color_primario' => 'nullable|exists:catalogo_items,id',
            'id_color_secundario' => 'nullable|exists:catalogo_items,id',
            'id_tipo_estado' => 'nullable|exists:estados_componentes,id',
            'id_lubricante_hidraulico' => 'nullable|exists:lubricantes,id',
            'nro_chasis' => 'nullable|string|max:100',
            'capacidad_toneladas' => 'required|numeric',
            'vin' => 'nullable|string|max:100',
            'nro_carroceria' => 'nullable|string|max:100',
            'nro_registro' => 'nullable|string|max:100',
            'nro_resolucion' => 'nullable|string|max:100',
            'tara' => 'nullable|numeric',
            'cap_deposito' => 'nullable|numeric',
            'cap_hidraulico' => 'nullable|numeric',
            'indice_consumo' => 'required|numeric',
            'indice_aceite' => 'nullable|numeric',
            'fecha_alta' => 'nullable|date',
            'fecha_baja' => 'nullable|date',
            'kilometraje_actual' => 'nullable|numeric',
            'kms_disp' => 'nullable|numeric',
            'kms_plan_mtto' => 'nullable|numeric',
            'plan_comb' => 'nullable|numeric',
            'plan_tn' => 'nullable|numeric',
            'plan_viajes' => 'nullable|numeric',
            'plan_gastos' => 'nullable|numeric',
            'plan_cdt' => 'nullable|numeric',
            'plan_diario' => 'nullable|numeric',
            'ficav' => 'nullable|string|max:50',
            'femision_ficav' => 'nullable|date',
            'fvence_ficav' => 'nullable|date',
            'lot' => 'nullable|string|max:50',
            'femision_lot' => 'nullable|date',
            'fvence_lot' => 'nullable|date',
            'circulacion' => 'nullable|string|max:50',
            'femision_circ' => 'nullable|date',
            'fvence_circ' => 'nullable|date',
            'f_reconstruccion' => 'nullable|date',
            'gps' => 'nullable|string|max:50',
        ];
    }

    /**
     * Remove the specified tractivo.
     */
    public function destroy(Tractivo $tractivo)
    {

        $this->authorize('delete', $tractivo);
        $this->autorizarEntidad($tractivo->id_entidad);

        $tractivo->delete();

        return redirect()->route('tractivos.index')
            ->with('success', 'Tractivo eliminado correctamente.');
    }
}
