<?php

namespace App\Http\Controllers;

use App\Models\Diferenciale;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DiferencialesController extends Controller
{
    public function index(Request $request)
    {
        $diferenciales = Diferenciale::with('tractivo:id,descripcion,placa')
            ->when($request->search, fn ($q, $s) => $q->where('descripcion', 'like', "%{$s}%"))
            ->paginate(20);

        return Inertia::render('Diferenciales/Index', [
            'title' => 'Diferenciales',
            'diferenciales' => $diferenciales,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:diferenciales,codigo',
            'descripcion' => 'required|string|max:255',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'numero_serie' => 'nullable|string|max:100',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'estado' => 'nullable|string|max:50',
        ]);

        Diferenciale::create($validated);

        return redirect()->route('diferenciales.index')
            ->with('success', 'Diferencial creado correctamente.');
    }

    public function update(Request $request, Diferenciale $diferencial)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:diferenciales,codigo,' . $diferencial->id,
            'descripcion' => 'required|string|max:255',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'numero_serie' => 'nullable|string|max:100',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'estado' => 'nullable|string|max:50',
        ]);

        $diferencial->update($validated);

        return redirect()->route('diferenciales.index')
            ->with('success', 'Diferencial actualizado correctamente.');
    }

    public function destroy(Diferenciale $diferencial)
    {
        $diferencial->delete();

        return redirect()->route('diferenciales.index')
            ->with('success', 'Diferencial eliminado correctamente.');
    }
}
