<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Tractivo;

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
        })->paginate(20);

        return Inertia::render('Tractivos/Index', [
            'tractivos' => $tractivos,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created tractivo.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'descripcion' => 'required|string|max:255',
            'placa' => 'required|string|max:50|unique:tractivos,placa',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'anno' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
        ]);

        Tractivo::create($validated);

        return redirect()->route('tractivos.index')
            ->with('success', 'Tractivo creado correctamente.');
    }

    /**
     * Update the specified tractivo.
     */
    public function update(Request $request, Tractivo $tractivo)
    {
        $validated = $request->validate([
            'descripcion' => 'required|string|max:255',
            'placa' => 'required|string|max:50|unique:tractivos,placa,' . $tractivo->id,
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'anno' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
        ]);

        $tractivo->update($validated);

        return redirect()->route('tractivos.index')
            ->with('success', 'Tractivo actualizado correctamente.');
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
