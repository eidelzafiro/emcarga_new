<?php

namespace App\Http\Controllers;

use App\Models\Tractivo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TractivosController extends Controller
{
    /**
     * Display a listing of tractivos.
     */
    public function index(Request $request)
    {
        $tractivos = Tractivo::when($request->search, function ($query, $search) {
            $query->where('descripcion', 'like', "%{$search}%")
                ->orWhere('placa', 'like', "%{$search}%");
        })
            ->when(true, function ($q) {
                $entidadId = (int) session('entidad_activa_id');
                if ($entidadId) {
                    $q->where('id_entidad', $entidadId);
                }
                return $q;
            })
            ->paginate(20);

        return Inertia::render('Tractivos/Index', [
            'title' => 'Vehículos',
            'tractivos' => $tractivos,
            'filters' => $request->only(['search']),
            'catalogos' => [
                'tiposTractivo' => $this->combos(\App\Models\TipoTractivo::class, 'nombre'),
                'motores' => $this->combos(\App\Models\Motore::class, 'descripcion'),
                'cajas' => $this->combos(\App\Models\Caja::class, 'descripcion'),
                'diferenciales' => $this->combos(\App\Models\Diferenciale::class, 'descripcion'),
                'grupos' => $this->combos(\App\Models\Grupo::class, 'nombre'),
                'tiposServicio' => $this->combos(\App\Models\TipoServicio::class, 'nombre'),
                'colores' => $this->combos(\App\Models\Color::class, 'nombre'),
                'estados' => $this->combos(\App\Models\EstadoComponente::class, 'nombre'),
                'lubricantes' => $this->combos(\App\Models\Lubricante::class, 'nombre'),
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
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'anno' => 'nullable|integer|min:1900|max:'.(date('Y') + 1),
            'id_tipo_vehiculo' => 'nullable|exists:tipos_tractivos,id',
            'id_motor' => 'nullable|exists:motores,id',
            'id_caja' => 'nullable|exists:cajas,id',
            'id_diferencial' => 'nullable|exists:diferenciales,id',
            'id_grupo' => 'nullable|exists:grupos,id',
            'id_tipo_servicio' => 'nullable|exists:tipos_servicios,id',
            'id_color_primario' => 'nullable|exists:colores,id',
            'id_color_secundario' => 'nullable|exists:colores,id',
            'id_tipo_estado' => 'nullable|exists:estados_componentes,id',
            'id_lubricante_hidraulico' => 'nullable|exists:lubricantes,id',
            'color' => 'nullable|string|max:100',
            'numero_motor' => 'nullable|string|max:100',
            'numero_chasis' => 'nullable|string|max:100',
            'numero_caja' => 'nullable|string|max:100',
            'capacidad_toneladas' => 'nullable|numeric',
            'vin' => 'nullable|string|max:100',
            'nro_carroceria' => 'nullable|string|max:100',
            'nro_registro' => 'nullable|string|max:100',
            'nro_resolucion' => 'nullable|string|max:100',
            'tara' => 'nullable|numeric',
            'cap_deposito' => 'nullable|numeric',
            'cap_hidraulico' => 'nullable|numeric',
            'cta_combustible' => 'nullable|string|max:50',
            'indice_consumo' => 'nullable|numeric',
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
        $tractivo->delete();

        return redirect()->route('tractivos.index')
            ->with('success', 'Tractivo eliminado correctamente.');
    }
}
