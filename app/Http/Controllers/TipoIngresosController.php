<?php

namespace App\Http\Controllers;

use App\Models\TipoIngreso;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TipoIngresosController extends Controller
{
    public function index(Request $request)
    {
        $tipos = TipoIngreso::when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%")->orWhere('codigo', 'like', "%{$s}%"))
            ->orderBy('nombre')
            ->paginate(20);

        return Inertia::render('TipoIngresos/Index', [
            'title' => 'Tipos de Ingreso',
            'tipos' => $tipos,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:tipo_ingresos,codigo|max:50',
            'nombre' => 'required|max:255',
            'siglas' => 'nullable|max:20',
        ]);
        TipoIngreso::create($validated);
        return redirect()->route('tipo-ingresos.index')->with('success', 'Tipo de ingreso creado correctamente.');
    }

    public function update(Request $request, TipoIngreso $tipoIngreso)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:tipo_ingresos,codigo,' . $tipoIngreso->id . '|max:50',
            'nombre' => 'required|max:255',
            'siglas' => 'nullable|max:20',
        ]);
        $tipoIngreso->update($validated);
        return redirect()->route('tipo-ingresos.index')->with('success', 'Tipo de ingreso actualizado correctamente.');
    }

    public function destroy(TipoIngreso $tipoIngreso)
    {
        $tipoIngreso->delete();
        return redirect()->route('tipo-ingresos.index')->with('success', 'Tipo de ingreso eliminado correctamente.');
    }
}
