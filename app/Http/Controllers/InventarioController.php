<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventarioController extends Controller
{
    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\Inventario::class);
        $items = Inventario::when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%")->orWhere('codigo', 'like', "%{$s}%")->orWhere('categoria', 'like', "%{$s}%"))
            ->orderBy('nombre')
            ->paginate(20);

        return Inertia::render('Inventario/Index', [
            'title' => 'Inventario',
            'items' => $items,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\Inventario::class);
        $validated = $request->validate([
            'codigo' => 'required|unique:inventario,codigo|max:50',
            'nombre' => 'required|max:255',
            'descripcion' => 'nullable|max:500',
            'categoria' => 'nullable|max:100',
            'unidad_medida' => 'nullable|max:50',
            'cantidad_actual' => 'required|numeric|min:0',
            'costo_unitario' => 'required|numeric|min:0',
            'costo_total' => 'required|numeric|min:0',
            'ubicacion' => 'nullable|max:255',
        ]);
        Inventario::create($validated);

        return redirect()->route('inventario.index')->with('success', 'Item de inventario creado correctamente.');
    }

    public function update(Request $request, Inventario $inventario)
    {
        
        $this->authorize('update', $inventario);
        $validated = $request->validate([
            'codigo' => 'required|unique:inventario,codigo,'.$inventario->id.'|max:50',
            'nombre' => 'required|max:255',
            'descripcion' => 'nullable|max:500',
            'categoria' => 'nullable|max:100',
            'unidad_medida' => 'nullable|max:50',
            'cantidad_actual' => 'required|numeric|min:0',
            'costo_unitario' => 'required|numeric|min:0',
            'costo_total' => 'required|numeric|min:0',
            'ubicacion' => 'nullable|max:255',
        ]);
        $inventario->update($validated);

        return redirect()->route('inventario.index')->with('success', 'Item de inventario actualizado correctamente.');
    }

    public function destroy(Inventario $inventario)
    {
        
        $this->authorize('delete', $inventario);
        $inventario->delete();

        return redirect()->route('inventario.index')->with('success', 'Item de inventario eliminado correctamente.');
    }
}
