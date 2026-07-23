<?php

namespace App\Http\Controllers;

use App\Models\OtrosGasto;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OtrosGastosController extends Controller
{
    public function index(Request $request)
    {
        $gastos = OtrosGasto::with(['bolsa', 'tractivo', 'tipoConcepto'])
            ->when($request->search, fn ($q, $s) => $q->where('concepto', 'like', "%{$s}%")->orWhere('descripcion', 'like', "%{$s}%"))
            ->orderBy('fecha', 'desc')
            ->paginate(20);

        $bolsa = \App\Models\Bolsa::select('id', 'nombre')->orderBy('nombre')->get();
        $tractivos = \App\Models\Tractivo::select('id', 'codigo')->orderBy('codigo')->get();

        return Inertia::render('OtrosGastos/Index', [
            'title' => 'Otros Gastos',
            'gastos' => $gastos,
            'bolsa' => $bolsa,
            'tractivos' => $tractivos,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_bolsa' => 'required|exists:bolsa,id',
            'id_tractivo' => 'required|exists:tractivos,id',
            'id_tipo_concepto' => 'required|exists:tipos_conceptos,id',
            'fecha' => 'required|date',
            'concepto' => 'required|max:255',
            'monto_mn' => 'required|numeric|min:0',
            'monto_mlc' => 'required|numeric|min:0',
            'descripcion' => 'nullable|max:500',
        ]);
        OtrosGasto::create($validated);
        return redirect()->route('otros-gastos.index')->with('success', 'Gasto creado correctamente.');
    }

    public function update(Request $request, OtrosGasto $otrosGasto)
    {
        $validated = $request->validate([
            'id_bolsa' => 'required|exists:bolsa,id',
            'id_tractivo' => 'required|exists:tractivos,id',
            'id_tipo_concepto' => 'required|exists:tipos_conceptos,id',
            'fecha' => 'required|date',
            'concepto' => 'required|max:255',
            'monto_mn' => 'required|numeric|min:0',
            'monto_mlc' => 'required|numeric|min:0',
            'descripcion' => 'nullable|max:500',
        ]);
        $otrosGasto->update($validated);
        return redirect()->route('otros-gastos.index')->with('success', 'Gasto actualizado correctamente.');
    }

    public function destroy(OtrosGasto $otrosGasto)
    {
        $otrosGasto->delete();
        return redirect()->route('otros-gastos.index')->with('success', 'Gasto eliminado correctamente.');
    }
}
