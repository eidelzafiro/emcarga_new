<?php

namespace App\Http\Controllers;

use App\Models\TipoIncidencia;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TiposIncidenciasController extends Controller
{
    public function index(Request $request)
    {
        $tipos = TipoIncidencia::when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%")->orWhere('codigo', 'like', "%{$s}%"))
            ->orderBy('nombre')
            ->paginate(20);

        return Inertia::render('TipoIncidencias/Index', [
            'title' => 'Tipos de Incidencias',
            'tipos' => $tipos,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:tipos_incidencias,codigo|max:50',
            'nombre' => 'required|max:255',
            'siglas' => 'nullable|max:20',
        ]);
        TipoIncidencia::create($validated);

        return redirect()->route('tipos-incidencias.index')->with('success', 'Tipo de incidencia creado correctamente.');
    }

    public function update(Request $request, TipoIncidencia $tiposIncidencia)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:tipos_incidencias,codigo,'.$tiposIncidencia->id.'|max:50',
            'nombre' => 'required|max:255',
            'siglas' => 'nullable|max:20',
        ]);
        $tiposIncidencia->update($validated);

        return redirect()->route('tipos-incidencias.index')->with('success', 'Tipo de incidencia actualizado correctamente.');
    }

    public function destroy(TipoIncidencia $tiposIncidencia)
    {
        $tiposIncidencia->delete();

        return redirect()->route('tipos-incidencias.index')->with('success', 'Tipo de incidencia eliminado correctamente.');
    }
}
