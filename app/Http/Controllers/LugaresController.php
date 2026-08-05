<?php

namespace App\Http\Controllers;

use App\Models\Lugare;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LugaresController extends Controller
{
    public function index(Request $request)
    {
        $lugares = Lugare::when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%"))
            ->orderBy('nombre')
            ->paginate(20);

        return Inertia::render('Lugares/Index', [
            'title' => 'Lugares',
            'lugares' => $lugares,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);
        Lugare::create($validated);

        return redirect()->route('lugares.index')->with('success', 'Lugar creado correctamente.');
    }

    public function update(Request $request, Lugare $lugar)
    {
        $validated = $this->validar($request);
        $lugar->update($validated);

        return redirect()->route('lugares.index')->with('success', 'Lugar actualizado correctamente.');
    }

    public function destroy(Lugare $lugar)
    {
        $lugar->delete();

        return redirect()->route('lugares.index')->with('success', 'Lugar eliminado correctamente.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => 'required|max:255',
            'provincia' => 'nullable|max:100',
            'municipio' => 'nullable|max:100',
            'direccion' => 'nullable|max:500',
            'personalidad' => 'nullable|max:255',
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180',
            'activo' => 'sometimes|boolean',
        ]);
    }
}