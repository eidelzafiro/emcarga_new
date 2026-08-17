<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Color;
use App\Models\Diferenciale;
use App\Models\EstadoComponente;
use App\Models\Grupo;
use App\Models\Lubricante;
use App\Models\Motore;
use App\Models\TipoArrastre;
use App\Models\TipoServicio;
use App\Models\TipoTractivo;
use App\Models\Tractivo;
use App\Http\Controllers\Traits\EntidadScoping;
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
        $tractivos = Tractivo::query()
            ->when($request->grupo, function ($query, $grupo) {
                $query->where('id_grupo', $grupo);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('descripcion', 'like', "%{$search}%")
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

        $tiposArrastre = $this->combosArrastre();
        $tiposTractivo = $this->combosTipoTractivo();

        // La descripción del equipo sale de su "tipo" (ficha compuesta
        // marca + modelo + año). Para los arrastres (grupo 8) la ficha se
        // resuelve desde tipos_arrastres (p. ej. COSIC ST-TVO) y para el
        // resto desde tipos_tractivos (p. ej. NORTH BENZ 25325 2005). En
        // legacy el idtipotractivos colisiona entre ambas fichas: el mismo
        // id 100 es GAZ 69 en tipos_tractivos y COSIC ST-TVO en tipos_arrastres.
        $tractivos->getCollection()->transform(function ($tractivo) use ($tiposArrastre, $tiposTractivo) {
            $catalogo = (int) $tractivo->id_grupo === 8 ? $tiposArrastre : $tiposTractivo;
            $tipo = collect($catalogo)->firstWhere('value', $tractivo->id_tipo_vehiculo);
            $tractivo->tipo_vehiculo_label = $tipo['label'] ?? ('Tipo '.$tractivo->id_tipo_vehiculo);
            $tractivo->tipo_equipo_label = $tipo['tipo_equipo'] ?? null;
            $tractivo->tipo_mtto_label = $tipo['tipo_mtto'] ?? null;

            // Ficha heredada del tipo (marca/modelo/año) para el formulario.
            $tractivo->tipo_ficha = $tipo['ficha'] ?? null;

            return $tractivo;
        });

        return Inertia::render('Tractivos/Index', [
            'title' => 'Vehículos',
            'tractivos' => $tractivos,
            'filters' => $request->only(['search', 'grupo']),
            'catalogos' => [
                'tiposArrastre' => $tiposArrastre,
                'tiposTractivo' => $this->combosTipoTractivo(),
                'motores' => $this->combos(Motore::class, 'descripcion'),
                'cajas' => $this->combos(Caja::class, 'descripcion'),
                'diferenciales' => $this->combos(Diferenciale::class, 'descripcion'),
                'grupos' => $this->combos(Grupo::class, 'nombre'),
                'tiposServicio' => $this->combos(TipoServicio::class, 'nombre'),
                'colores' => $this->combos(Color::class, 'nombre'),
                'estados' => $this->combos(EstadoComponente::class, 'nombre'),
                'lubricantes' => $this->combos(Lubricante::class, 'nombre'),
            ],
        ]);
    }

    private function combos(string $model, string $labelField): array
    {
        return $model::orderBy($labelField)
            ->get()
            ->map(fn ($item) => ['value' => $item->id, 'label' => (string) $item->{$labelField}])
            ->values()
            ->toArray();
    }

    private function combosArrastre(): array
    {
        return TipoArrastre::with(['marca', 'modelo', 'tipoEquipo', 'tipoMantenimiento'])
            ->orderBy('id')
            ->get()
            ->map(function ($item) {
                $marca = $item->marca?->nombre;
                $modelo = $item->modelo?->nombre;
                $anio = $item->fabricacion;
                $partes = array_filter([$marca, $modelo, $anio]);
                $etiqueta = $partes ? implode(' - ', $partes) : ('Tipo '.$item->id);

                return [
                    'value' => $item->id,
                    'label' => $etiqueta,
                    'tipo_equipo' => $item->tipoEquipo?->nombre ?? null,
                    'tipo_mtto' => $item->tipoMantenimiento?->nombre ?? null,
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

    private function combosTipoTractivo(): array
    {
        return TipoTractivo::with(['marca', 'modelo'])
            ->orderBy('id')
            ->get()
            ->map(function ($item) {
                $marca = $item->marca?->nombre;
                $modelo = $item->modelo?->nombre;
                $anio = $item->fabricacion;
                $partes = array_filter([$marca, $modelo, $anio]);
                $etiqueta = $partes ? implode(' - ', $partes) : ('Tipo '.$item->id);

                return [
                    'value' => $item->id,
                    'label' => $etiqueta,
                    'tipo_equipo' => $item->tipo_equipo ?? null,
                    'tipo_mtto' => array_key_exists('id_tipo_mantenimiento', $item->getAttributes()) && $item->tipoMtto ? $item->tipoMtto?->nombre : null,
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

    /**
     * Store a newly created tractivo.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->reglas());

        $validated['id_entidad'] = (int) session('entidad_activa_id');
        Tractivo::create($validated);

        return redirect()->route('tractivos.index')
            ->with('success', 'Tractivo creado correctamente.');
    }

    /**
     * Update the specified tractivo.
     */
    public function update(Request $request, Tractivo $tractivo)
    {
        $this->autorizarEntidad($tractivo->id_entidad);

        $validated = $request->validate($this->reglas($tractivo->id));

        $tractivo->update($validated);

        return redirect()->route('tractivos.index')
            ->with('success', 'Tractivo actualizado correctamente.');
    }

    private function reglas(?int $id = null): array
    {
        return [
            'descripcion' => 'required|string|max:255',
            'placa' => 'required|string|max:50|unique:tractivos,placa'.($id ? ','.$id : ''),
            'id_tipo_vehiculo' => ['required', function ($attr, $value, $fail) {
                if (! $value) {
                    $fail('El tipo de vehículo es obligatorio.');
                    return;
                }
                $enTractivos = DB::table('tipos_tractivos')->where('id', $value)->exists();
                $enArrastres = DB::table('tipos_arrastres')->where('id', $value)->exists();
                if (! $enTractivos && ! $enArrastres) {
                    $fail('El tipo de vehículo seleccionado no existe.');
                }
            }],
            'id_motor' => 'nullable|exists:motores,id',
            'id_caja' => 'nullable|exists:cajas,id',
            'id_diferencial' => 'nullable|exists:diferenciales,id',
            'id_grupo' => 'required|exists:grupos,id',
            'id_tipo_servicio' => 'nullable|exists:tipos_servicios,id',
            'id_color_primario' => 'nullable|exists:colores,id',
            'id_color_secundario' => 'nullable|exists:colores,id',
            'id_tipo_estado' => 'nullable|exists:estados_componentes,id',
            'id_lubricante_hidraulico' => 'nullable|exists:lubricantes,id',
            'numero_motor' => 'nullable|string|max:100',
            'numero_chasis' => 'nullable|string|max:100',
            'numero_caja' => 'nullable|string|max:100',
            'capacidad_toneladas' => 'required|numeric',
            'vin' => 'nullable|string|max:100',
            'nro_carroceria' => 'nullable|string|max:100',
            'nro_registro' => 'nullable|string|max:100',
            'nro_resolucion' => 'nullable|string|max:100',
            'tara' => 'nullable|numeric',
            'cap_deposito' => 'nullable|numeric',
            'cap_hidraulico' => 'nullable|numeric',
            'cta_combustible' => 'nullable|string|max:50',
            'indice_consumo' => 'required|numeric',
            'indice_aceite' => 'nullable|numeric',
            'estado' => 'nullable|string|max:50',
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
        $this->autorizarEntidad($tractivo->id_entidad);

        $tractivo->delete();

        return redirect()->route('tractivos.index')
            ->with('success', 'Tractivo eliminado correctamente.');
    }
}
