<?php

namespace App\Http\Controllers;

use App\Models\TipoPenalizacione;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TiposPenalizacionesController extends Controller
{
    public function index(Request $request)
    {
        $tipos = TipoPenalizacione::when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%")->orWhere('codigo', 'like', "%{$s}%"))
            ->orderBy('nombre')
            ->paginate(20);

        return Inertia::render('TipoPenalizaciones/Index', [
            'title' => 'Tipos de Penalizaciones',
            'tipos' => $tipos,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:tipo_penalizaciones,codigo|max:50',
            'nombre' => 'required|max:255',
        ]);
        TipoPenalizacione::create($validated);
        return redirect()->route('tipos-penalizaciones.index')->with('success', 'Tipo de penalización creado correctamente.');
    }

    public function update(Request $request, TipoPenalizacione $tiposPenalizacione)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:tipo_penalizaciones,codigo,' . $tiposPenalizacione->id . '|max:50',
            'nombre' => 'required|max:255',
        ]);
        $tiposPenalizacione->update($validated);
        return redirect()->route('tipos-penalizaciones.index')->with('success', 'Tipo de penalización actualizado correctamente.');
    }

    public function destroy(TipoPenalizacione $tiposPenalizacione)
    {
        $tiposPenalizacione->delete();
        return redirect()->route('tipos-penalizaciones.index')->with('success', 'Tipo de penalización eliminado correctamente.');
    }
}
