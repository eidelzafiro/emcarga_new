<?php

namespace App\Http\Controllers;

use App\Models\TipoContrato;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TiposContratosController extends Controller
{
    public function index(Request $request)
    {
        $tipos = TipoContrato::when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%")->orWhere('codigo', 'like', "%{$s}%"))
            ->orderBy('nombre')
            ->paginate(20);

        return Inertia::render('TiposContratos/Index', [
            'title' => 'Tipos de Contratos',
            'tipos' => $tipos,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:tipo_contratos,codigo|max:50',
            'nombre' => 'required|max:255',
        ]);
        TipoContrato::create($validated);
        return redirect()->route('tipos-contratos.index')->with('success', 'Tipo de contrato creado correctamente.');
    }

    public function update(Request $request, TipoContrato $tiposContrato)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:tipo_contratos,codigo,' . $tiposContrato->id . '|max:50',
            'nombre' => 'required|max:255',
        ]);
        $tiposContrato->update($validated);
        return redirect()->route('tipos-contratos.index')->with('success', 'Tipo de contrato actualizado correctamente.');
    }

    public function destroy(TipoContrato $tiposContrato)
    {
        $tiposContrato->delete();
        return redirect()->route('tipos-contratos.index')->with('success', 'Tipo de contrato eliminado correctamente.');
    }
}
