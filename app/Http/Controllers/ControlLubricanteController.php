<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\ControlLubricante;
use App\Models\Lubricante;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Control de Lubricantes CT-7 (módulo Taller, réplica del legacy).
 *
 * Registra el consumo por sistema del vehículo (motor, transmisión, dirección,
 * hidráulico, frenos, agua, grasas rollete/copillas) con el tipo de lubricante
 * de cada sistema y un tipo de operación (RELLENO/MTTO/O.CAUSAS).
 */
class ControlLubricanteController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $registros = ControlLubricante::with('tractivo:id,descripcion,placa', 'entidad:id,nombre')
            ->when($request->id_tractivo, fn ($q, $v) => $q->where('id_tractivo', $v))
            ->when($request->tipo_operacion, fn ($q, $v) => $q->where('tipo_operacion', $v))
            ->when($request->desde, fn ($q, $v) => $q->whereDate('fecha_cambio', '>=', $v))
            ->when($request->hasta, fn ($q, $v) => $q->whereDate('fecha_cambio', '<=', $v))
            ->when(true, function ($q) {
                $entidades = $this->entidadesPermitidas();
                if (! empty($entidades)) {
                    $q->whereIn('id_entidad', $entidades);
                }

                return $q;
            })
            ->orderByDesc('fecha_cambio')
            ->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('Lubricantes/Control', [
            'title' => 'Control de Lubricantes (CT-7)',
            'registros' => $registros,
            'filtros' => [
                'tractivos' => \App\Models\Tractivo::where('id_grupo', '!=', 8)
                    ->orderBy('descripcion')->get(['id', 'descripcion', 'placa']),
                'tipos_operacion' => ['RELLENO', 'MTTO', 'O.CAUSAS'],
                'lubricantes' => Lubricante::orderBy('nombre')->get(['id', 'nombre', 'tipo']),
            ],
            'filters' => $request->only(['id_tractivo', 'tipo_operacion', 'desde', 'hasta']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->reglas());

        $validated['id_entidad'] = (int) session('entidad_activa_id') ?: null;
        $validated['id_unidad'] = $validated['id_entidad'];

        ControlLubricante::create($validated);

        return redirect()->route('control-lubricante.index')
            ->with('success', 'Registro de control de lubricante creado.');
    }

    public function update(Request $request, ControlLubricante $controlLubricante)
    {
        $this->autorizarEntidad($controlLubricante->id_entidad);

        $validated = $request->validate($this->reglas());
        $controlLubricante->update($validated);

        return redirect()->route('control-lubricante.index')
            ->with('success', 'Registro actualizado.');
    }

    public function destroy(ControlLubricante $controlLubricante)
    {
        $this->autorizarEntidad($controlLubricante->id_entidad);

        $controlLubricante->delete();

        return redirect()->route('control-lubricante.index')
            ->with('success', 'Registro eliminado.');
    }

    private function reglas(): array
    {
        return [
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'fecha_cambio' => 'nullable|date',
            'tipo_operacion' => 'required|in:RELLENO,MTTO,O.CAUSAS',
            'litros_motor' => 'nullable|numeric|min:0',
            'litros_transmision' => 'nullable|numeric|min:0',
            'litros_direccion' => 'nullable|numeric|min:0',
            'litros_hidraulico' => 'nullable|numeric|min:0',
            'liquido_freno' => 'nullable|numeric|min:0',
            'agua_refrigerada' => 'nullable|numeric|min:0',
            'grasa_rollete' => 'nullable|numeric|min:0',
            'grasa_copillas' => 'nullable|numeric|min:0',
            'id_lub_motor' => 'nullable|exists:lubricantes,id',
            'id_lub_transmision' => 'nullable|exists:lubricantes,id',
            'id_lub_hidraulico' => 'nullable|exists:lubricantes,id',
            'id_lub_direccion' => 'nullable|exists:lubricantes,id',
            'id_grasa_rollete' => 'nullable|exists:lubricantes,id',
            'id_grasa_copillas' => 'nullable|exists:lubricantes,id',
            'id_liquido_freno' => 'nullable|exists:lubricantes,id',
            'id_agua' => 'nullable|exists:lubricantes,id',
        ];
    }
}
