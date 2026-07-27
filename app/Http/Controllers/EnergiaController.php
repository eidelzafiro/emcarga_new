<?php

namespace App\Http\Controllers;

use App\Models\Medidore;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnergiaController extends Controller
{
    public function index(Request $request)
    {
        $medidores = Medidore::with('lecturas')
            ->when($request->search, fn ($q, $s) => $q->where('codigo', 'like', "%{$s}%"))
            ->when(session('entidad_activa_id'), fn ($q, $id) => $q->where('id_entidad', $id))
            ->paginate(20);

        return Inertia::render('Energia/Index', [
            'title' => 'Control de Energía',
            'medidores' => $medidores,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:medidores,codigo',
            'ruta_folio' => 'nullable|string|max:100',
            'metro' => 'nullable|string|max:100',
            'prepago' => 'boolean',
            'tipo' => 'nullable|string|max:50',
            'lectura_actual' => 'nullable|numeric',
            'factor' => 'nullable|numeric',
            'activo' => 'boolean',
        ]);

        Medidore::create($validated);

        return redirect()->route('energia.index')
            ->with('success', 'Medidor creado correctamente.');
    }

    public function update(Request $request, Medidore $medidore)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:medidores,codigo,'.$medidore->id,
            'ruta_folio' => 'nullable|string|max:100',
            'metro' => 'nullable|string|max:100',
            'prepago' => 'boolean',
            'tipo' => 'nullable|string|max:50',
            'lectura_actual' => 'nullable|numeric',
            'factor' => 'nullable|numeric',
            'activo' => 'boolean',
        ]);

        $medidore->update($validated);

        return redirect()->route('energia.index')
            ->with('success', 'Medidor actualizado correctamente.');
    }

    public function destroy(Medidore $medidore)
    {
        $medidore->delete();

        return redirect()->route('energia.index')
            ->with('success', 'Medidor eliminado correctamente.');
    }
}
