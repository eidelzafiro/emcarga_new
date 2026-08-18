<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TurnosController extends Controller
{
    public function index(Request $request)
    {
        $items = Turno::query()
            ->when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%")
                ->orWhere('codigo', 'like', "%{$s}%"))
            ->orderBy('nombre')
            ->paginate(20);

        return Inertia::render('Turnos/Index', [
            'title' => 'Turnos de Trabajo',
            'items' => $items,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'nullable|string|max:50|unique:turnos,codigo',
            'nombre' => 'required|string|max:255',
            'hora_entrada' => 'required|date_format:H:i',
            'hora_salida' => 'required|date_format:H:i',
            'dias_descanso' => 'nullable|integer|min:0|max:7',
            'activo' => 'sometimes|boolean',
        ]);

        Turno::create($validated);

        return redirect()->route('turnos.index')->with('success', 'Turno creado correctamente.');
    }

    public function update(Request $request, Turno $turno)
    {
        $validated = $request->validate([
            'codigo' => 'nullable|string|max:50|unique:turnos,codigo,'.$turno->id,
            'nombre' => 'required|string|max:255',
            'hora_entrada' => 'required|date_format:H:i',
            'hora_salida' => 'required|date_format:H:i',
            'dias_descanso' => 'nullable|integer|min:0|max:7',
            'activo' => 'sometimes|boolean',
        ]);

        $turno->update($validated);

        return redirect()->route('turnos.index')->with('success', 'Turno actualizado correctamente.');
    }

    public function destroy(Turno $turno)
    {
        $turno->delete();

        return redirect()->route('turnos.index')->with('success', 'Turno eliminado correctamente.');
    }
}
