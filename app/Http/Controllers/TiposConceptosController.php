<?php

namespace App\Http\Controllers;

use App\Models\TipoConcepto;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TiposConceptosController extends Controller
{
    public function index(Request $request)
    {
        $tipos = TipoConcepto::when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%")->orWhere('codigo', 'like', "%{$s}%"))
            ->orderBy('nombre')
            ->paginate(20);

        return Inertia::render('TiposConceptos/Index', [
            'title' => 'Tipos de Concepto',
            'tipos' => $tipos,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:tipos_conceptos,codigo|max:50',
            'nombre' => 'required|max:255',
            'activo' => 'boolean',
        ]);
        TipoConcepto::create($validated);
        return redirect()->route('tipos-conceptos.index')->with('success', 'Tipo de concepto creado correctamente.');
    }

    public function update(Request $request, TipoConcepto $tipoConcepto)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:tipos_conceptos,codigo,' . $tipoConcepto->id . '|max:50',
            'nombre' => 'required|max:255',
            'activo' => 'boolean',
        ]);
        $tipoConcepto->update($validated);
        return redirect()->route('tipos-conceptos.index')->with('success', 'Tipo de concepto actualizado correctamente.');
    }

    public function destroy(TipoConcepto $tipoConcepto)
    {
        $tipoConcepto->delete();
        return redirect()->route('tipos-conceptos.index')->with('success', 'Tipo de concepto eliminado correctamente.');
    }
}
