<?php

namespace App\Http\Controllers;

use App\Models\HistorialMovimiento;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HistorialMovimientosController extends Controller
{
    public function index(Request $request)
    {
        $items = HistorialMovimiento::with(['bolsa', 'movimiento'])
            ->when($entidadId = session('entidad_activa_id'), fn ($q) => $q->whereHas('bolsa', fn ($b) => $b->where('id_entidad', $entidadId)))
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->through(function (HistorialMovimiento $h) {
                return [
                    'id' => $h->id,
                    'fecha' => $h->fecha?->toDateString(),
                    'tipo_movimiento' => $h->tipo,
                    'bolsa_nombre' => $h->bolsa?->nombre,
                    'ci' => $h->bolsa?->ci,
                    'descripcion' => $h->observaciones,
                ];
            });

        return Inertia::render('HistorialMovimientos/Index', [
            'title' => 'Historial',
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
