<?php

namespace App\Http\Controllers;

use App\Models\HistorialMovimiento;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HistorialMovimientosController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\HistorialMovimiento::class);
        $items = HistorialMovimiento::with(['bolsa', 'movimiento'])
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereHas('bolsa', fn ($b) => $b->whereIn('id_entidad', $this->entidadesPermitidas())))
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
        
        $this->authorize('create', \App\Models\HistorialMovimiento::class);
        $validated = $request->validate([
            'codigo' => 'required|unique:historial_movimientos,codigo|max:50',
            'nombre' => 'required|max:255',
        ]);
        HistorialMovimiento::create($validated);

        return redirect()->route('historial-movimientos.index')->with('success', 'Movimiento creado correctamente.');
    }

    public function update(Request $request, HistorialMovimiento $historialMovimiento)
    {
        
        $this->authorize('update', $historialMovimiento);
        $this->autorizarEntidad($historialMovimiento->bolsa?->id_entidad);

        $validated = $request->validate([
            'codigo' => 'required|unique:historial_movimientos,codigo,'.$historialMovimiento->id.'|max:50',
            'nombre' => 'required|max:255',
        ]);
        $historialMovimiento->update($validated);

        return redirect()->route('historial-movimientos.index')->with('success', 'Movimiento actualizado correctamente.');
    }

    public function destroy(HistorialMovimiento $historialMovimiento)
    {
        
        $this->authorize('delete', $historialMovimiento);
        $this->autorizarEntidad($historialMovimiento->bolsa?->id_entidad);

        $historialMovimiento->delete();

        return redirect()->route('historial-movimientos.index')->with('success', 'Movimiento eliminado correctamente.');
    }
}
