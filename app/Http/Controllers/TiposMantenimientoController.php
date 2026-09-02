<?php

namespace App\Http\Controllers;

use App\Models\LineasMantenimiento;
use App\Models\TiposMantenimiento;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Planes de mantenimiento (tabla `tipos_mantenimiento`): catálogo técnico que
 * define el ciclo (frecuencia/kms) de cada tipo de vehículo y regenera las
 * líneas del plan (lineas_mantenimiento) al guardar (paridad legacy
 * Taller::crear_planmtto).
 */
class TiposMantenimientoController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', TiposMantenimiento::class);

        $tipos = TiposMantenimiento::query()
            ->withCount('lineas')
            ->when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%"))
            ->orderBy('nombre')
            ->paginate($request->integer('per_page', 20));

        return Inertia::render('TiposMantenimiento/Index', [
            'title' => 'Tipos de Mantenimiento',
            'tipos' => $tipos,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', TiposMantenimiento::class);

        $validated = $request->validate($this->reglas());

        $tipo = TiposMantenimiento::create($validated);
        // El guardado genera el plan de líneas (crear_planmtto legacy).
        $tipo->regenerarLineas();

        return redirect()->route('tipos-mantenimiento.index')
            ->with('success', 'Tipo de mantenimiento creado correctamente.');
    }

    public function update(Request $request, TiposMantenimiento $tipos_mantenimiento)
    {
        $this->authorize('update', $tipos_mantenimiento);

        $validated = $request->validate($this->reglas());

        $tipos_mantenimiento->update($validated);
        // Regenera las líneas del plan para reflejar los nuevos parámetros.
        $tipos_mantenimiento->regenerarLineas();

        return redirect()->route('tipos-mantenimiento.index')
            ->with('success', 'Tipo de mantenimiento actualizado correctamente.');
    }

    public function destroy(TiposMantenimiento $tipos_mantenimiento)
    {
        $this->authorize('delete', $tipos_mantenimiento);

        $tipos_mantenimiento->lineas()->delete();
        $tipos_mantenimiento->delete();

        return redirect()->route('tipos-mantenimiento.index')
            ->with('success', 'Tipo de mantenimiento eliminado correctamente.');
    }

    private function reglas(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'frecuencia' => 'nullable|integer|min:0',
            'kms_max' => 'nullable|integer|min:0',
            'mtto_base' => 'nullable|integer|min:0',
            'holgura' => 'nullable|integer|min:0',
            'mttos' => 'nullable|string|max:255',
            'activo' => 'nullable|boolean',
        ];
    }
}
