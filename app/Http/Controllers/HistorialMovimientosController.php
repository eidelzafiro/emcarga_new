<?php

namespace App\Http\Controllers;

use App\Models\HistorialMovimiento;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HistorialMovimientosController extends Controller
{
    public function index(Request $request)
    {
        $items = HistorialMovimiento::with([])
            ->when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%"))
            ->when($entidadId = session('entidad_activa_id'), fn ($q) => $q->where(function ($q) use ($entidadId) {
                $q->where('id_entidad_origen', $entidadId)
                    ->orWhere('id_entidad_destino', $entidadId);
            }))
            ->orderBy('id')
            ->paginate(20);

        return Inertia::render('HistorialMovimientos/Index', [
            'title' => 'Historial de Movimientos',
            'historial' => $items,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:historial_movimientos,codigo|max:50',
            'nombre' => 'required|max:255',
        ]);
        HistorialMovimiento::create($validated);

        return redirect()->route('historial-movimientos.index')->with('success', 'Movimiento creado correctamente.');
    }

    public function update(Request $request, HistorialMovimiento $historialMovimiento)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:historial_movimientos,codigo,'.$historialMovimiento->id.'|max:50',
            'nombre' => 'required|max:255',
        ]);
        $historialMovimiento->update($validated);

        return redirect()->route('historial-movimientos.index')->with('success', 'Movimiento actualizado correctamente.');
    }

    public function destroy(HistorialMovimiento $historialMovimiento)
    {
        $historialMovimiento->delete();

        return redirect()->route('historial-movimientos.index')->with('success', 'Movimiento eliminado correctamente.');
    }
}
