<?php

namespace App\Http\Controllers;

use App\Models\Lubricante;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Catálogo de lubricantes (tabla `lubricantes`): aceites, grasas, fluidos de
 * freno/refrigerantes usados por el módulo técnico (motores, cajas, CT-7).
 *
 * Cada lubricante se clasifica por `tipo` (motor, transmisión, hidráulico,
 * refrigerante, grasa...) tomando las opciones del catálogo unificado
 * `tipos_lubricantes`.
 */
class TiposLubricantesController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Lubricante::class);

        $lubricantes = Lubricante::query()
            ->when($request->search, function ($q, $s) {
                $q->where('nombre', 'like', "%{$s}%")
                    ->orWhere('codigo', 'like', "%{$s}%");
            })
            ->orderBy('nombre')
            ->paginate($request->integer('per_page', 20));

        return Inertia::render('TiposLubricantes/Index', [
            'title' => 'Tipos de Lubricantes',
            'lubricantes' => $lubricantes,
            'tipos' => \App\Support\Catalogos::opciones('tipos_lubricantes'),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Lubricante::class);

        $validated = $request->validate([
            'codigo' => 'nullable|string|max:50',
            'nombre' => 'required|string|max:255',
            'tipo' => 'nullable|string|max:100',
            'viscosidad' => 'nullable|string|max:50',
            'costo_litro' => 'nullable|numeric',
            'activo' => 'nullable|boolean',
        ]);

        $validated['activo'] = $validated['activo'] ?? true;

        Lubricante::create($validated);

        return redirect()->route('tipos-lubricantes.index')
            ->with('success', 'Lubricante creado correctamente.');
    }

    public function update(Request $request, Lubricante $tipos_lubricante)
    {
        $this->authorize('update', $tipos_lubricante);

        $validated = $request->validate([
            'codigo' => 'nullable|string|max:50',
            'nombre' => 'required|string|max:255',
            'tipo' => 'nullable|string|max:100',
            'viscosidad' => 'nullable|string|max:50',
            'costo_litro' => 'nullable|numeric',
            'activo' => 'nullable|boolean',
        ]);

        $tipos_lubricante->update($validated);

        return redirect()->route('tipos-lubricantes.index')
            ->with('success', 'Lubricante actualizado correctamente.');
    }

    public function destroy(Lubricante $tipos_lubricante)
    {
        $this->authorize('delete', $tipos_lubricante);

        $tipos_lubricante->delete();

        return redirect()->route('tipos-lubricantes.index')
            ->with('success', 'Lubricante eliminado correctamente.');
    }
}
