<?php

namespace App\Http\Controllers;

use App\Models\Firma;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FirmasController extends Controller
{
    public function index(Request $request)
    {
        $items = Firma::orderBy('nombre')
            ->when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%"))
            ->paginate(20);

        return Inertia::render('Firmas/Index', [
            'items' => $items,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'confecciona_nombre' => 'nullable|string|max:150',
            'confecciona_cargo' => 'nullable|string|max:150',
            'revisa_nombre' => 'nullable|string|max:150',
            'revisa_cargo' => 'nullable|string|max:150',
            'aprueba_nombre' => 'nullable|string|max:150',
            'aprueba_cargo' => 'nullable|string|max:150',
            'activo' => 'boolean',
        ]);
        Firma::create($validated);

        return redirect()->route('firmas.index')->with('success', 'Firma creada correctamente.');
    }

    public function update(Request $request, Firma $firma)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'confecciona_nombre' => 'nullable|string|max:150',
            'confecciona_cargo' => 'nullable|string|max:150',
            'revisa_nombre' => 'nullable|string|max:150',
            'revisa_cargo' => 'nullable|string|max:150',
            'aprueba_nombre' => 'nullable|string|max:150',
            'aprueba_cargo' => 'nullable|string|max:150',
            'activo' => 'boolean',
        ]);
        $firma->update($validated);

        return redirect()->route('firmas.index')->with('success', 'Firma actualizada correctamente.');
    }

    public function destroy(Firma $firma)
    {
        $firma->delete();

        return redirect()->route('firmas.index')->with('success', 'Firma eliminada correctamente.');
    }
}
