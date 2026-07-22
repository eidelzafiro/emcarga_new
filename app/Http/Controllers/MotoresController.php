<?php

namespace App\Http\Controllers;

use App\Models\Motore;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MotoresController extends Controller
{
    public function index(Request $request)
    {
        $motores = Motore::with('tractivo:id,descripcion,placa')
            ->when($request->search, fn ($q, $s) => $q->where('descripcion', 'like', "%{$s}%")
                ->orWhere('codigo', 'like', "%{$s}%"))
            ->paginate(20);

        return Inertia::render('Motores/Index', [
            'title' => 'Motores',
            'motores' => $motores,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:motores,codigo',
            'descripcion' => 'required|string|max:255',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'numero_serie' => 'nullable|string|max:100',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'estado' => 'nullable|string|max:50',
        ]);

        Motore::create($validated);

        return redirect()->route('motores.index')
            ->with('success', 'Motor creado correctamente.');
    }

    public function update(Request $request, Motore $motore)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:motores,codigo,' . $motore->id,
            'descripcion' => 'required|string|max:255',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'numero_serie' => 'nullable|string|max:100',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'estado' => 'nullable|string|max:50',
        ]);

        $motore->update($validated);

        return redirect()->route('motores.index')
            ->with('success', 'Motor actualizado correctamente.');
    }

    public function destroy(Motore $motore)
    {
        $motore->delete();

        return redirect()->route('motores.index')
            ->with('success', 'Motor eliminado correctamente.');
    }
}
