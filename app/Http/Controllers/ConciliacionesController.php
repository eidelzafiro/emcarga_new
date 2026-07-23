<?php

namespace App\Http\Controllers;

use App\Models\Conciliacione;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConciliacionesController extends Controller
{
    public function index(Request $request)
    {
        $conciliaciones = Conciliacione::with('factura')
            ->when($request->search, fn ($q, $s) => $q->where('concepto', 'like', "%{$s}%"))
            ->when($request->estado, fn ($q, $e) => $q->where('estado', $e))
            ->orderBy('fecha', 'desc')
            ->paginate(20);

        return Inertia::render('Conciliaciones/Index', [
            'title' => 'Conciliaciones',
            'conciliaciones' => $conciliaciones,
            'filters' => $request->only(['search', 'estado']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_factura' => 'required|exists:facturas,id',
            'fecha' => 'required|date',
            'monto' => 'required|numeric|min:0',
            'estado' => 'required|in:pendiente,conciliado,pendiente',
            'descripcion' => 'nullable|max:500',
        ]);
        Conciliacione::create($validated);

        return redirect()->route('conciliaciones.index')->with('success', 'Conciliación creada correctamente.');
    }

    public function update(Request $request, Conciliacione $conciliacione)
    {
        $validated = $request->validate([
            'id_factura' => 'required|exists:facturas,id',
            'fecha' => 'required|date',
            'monto' => 'required|numeric|min:0',
            'estado' => 'required|in:pendiente,conciliado,pendiente',
            'descripcion' => 'nullable|max:500',
        ]);
        $conciliacione->update($validated);

        return redirect()->route('conciliaciones.index')->with('success', 'Conciliación actualizada correctamente.');
    }

    public function destroy(Conciliacione $conciliacione)
    {
        $conciliacione->delete();

        return redirect()->route('conciliaciones.index')->with('success', 'Conciliación eliminada correctamente.');
    }
}
