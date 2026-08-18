<?php

namespace App\Http\Controllers;

use App\Models\MovimientosInventario;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MovimientosInventarioController extends Controller
{
    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\MovimientosInventario::class);
        $items = MovimientosInventario::query()
            ->when($request->search, fn ($q, $s) => $q->where('folio', 'like', "%{$s}%"))
            ->orderBy('id')->paginate(50);

        return Inertia::render('MovimientosInventario/Index', [
            'items' => $items,
            'title' => 'Movimientos de Inventario',
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        
        $this->authorize('create', \App\Models\MovimientosInventario::class);return redirect()->route('movimientos-inventario.index');
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\MovimientosInventario::class);
        $validated = $request->validate([
            'folio' => 'nullable|string|max:255',
            'id_almacen' => 'nullable|integer',
            'id_suministrador' => 'nullable|integer',
            'fecha_movimiento' => 'nullable|date',
            'factura' => 'nullable|string|max:255',
            'fecha_factura' => 'nullable|date',
            'importe_mn' => 'nullable|numeric',
            'importe_me' => 'nullable|numeric',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        MovimientosInventario::create($validated);

        return redirect()->route('movimientos-inventario.index')->with('success', 'Movimiento de inventario creado.');
    }

    public function show($id)
    {
        return redirect()->route('movimientos-inventario.index');
    }

    public function edit($id)
    {
        return redirect()->route('movimientos-inventario.index');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'folio' => 'nullable|string|max:255',
            'id_almacen' => 'nullable|integer',
            'id_suministrador' => 'nullable|integer',
            'fecha_movimiento' => 'nullable|date',
            'factura' => 'nullable|string|max:255',
            'fecha_factura' => 'nullable|date',
            'importe_mn' => 'nullable|numeric',
            'importe_me' => 'nullable|numeric',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $item = MovimientosInventario::findOrFail($id);
        $item->update($validated);

        return redirect()->route('movimientos-inventario.index')->with('success', 'Movimiento de inventario actualizado.');
    }

    public function destroy($id)
    {
        $item = MovimientosInventario::findOrFail($id);
        $item->delete();

        return redirect()->route('movimientos-inventario.index')->with('success', 'Movimiento de inventario eliminado.');
    }
}
