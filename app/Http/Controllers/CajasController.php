<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CajasController extends Controller
{
    public function index(Request $request)
    {
        $cajas = Caja::with('tractivo:id,descripcion,placa')
            ->when($request->search, fn ($q, $s) => $q->where('descripcion', 'like', "%{$s}%"))
            ->paginate(20);

        return Inertia::render('Cajas/Index', [
            'title' => 'Cajas de Transmisión',
            'cajas' => $cajas,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:cajas,codigo',
            'descripcion' => 'required|string|max:255',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'numero_serie' => 'nullable|string|max:100',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'estado' => 'nullable|string|max:50',
        ]);

        Caja::create($validated);

        return redirect()->route('cajas.index')
            ->with('success', 'Caja creada correctamente.');
    }

    public function update(Request $request, Caja $caja)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:cajas,codigo,' . $caja->id,
            'descripcion' => 'required|string|max:255',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'numero_serie' => 'nullable|string|max:100',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'estado' => 'nullable|string|max:50',
        ]);

        $caja->update($validated);

        return redirect()->route('cajas.index')
            ->with('success', 'Caja actualizada correctamente.');
    }

    public function destroy(Caja $caja)
    {
        $caja->delete();

        return redirect()->route('cajas.index')
            ->with('success', 'Caja eliminada correctamente.');
    }
}
