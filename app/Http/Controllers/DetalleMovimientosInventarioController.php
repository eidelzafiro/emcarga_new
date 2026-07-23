<?php

namespace App\Http\Controllers;

use App\Models\DetalleMovimientosInventario;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DetalleMovimientosInventarioController extends Controller
{
    public function index()
    {
        $items = DetalleMovimientosInventario::orderBy('id')->paginate(50);

        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Detalle de Movimientos',
            'route' => 'detalle-movimientos-inventario',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nuevo Detalle de Movimiento',
            'route' => 'detalle-movimientos-inventario',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        DetalleMovimientosInventario::create($validated);

        return redirect()->route('detalle-movimientos-inventario.index')->with('success', 'Detalle de movimiento creado.');
    }

    public function show($id)
    {
        $item = DetalleMovimientosInventario::findOrFail($id);

        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Detalle de Movimiento',
            'route' => 'detalle-movimientos-inventario',
        ]);
    }

    public function edit($id)
    {
        $item = DetalleMovimientosInventario::findOrFail($id);

        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Detalle de Movimiento',
            'route' => 'detalle-movimientos-inventario',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = DetalleMovimientosInventario::findOrFail($id);
        $item->update($validated);

        return redirect()->route('detalle-movimientos-inventario.index')->with('success', 'Detalle de movimiento actualizado.');
    }

    public function destroy($id)
    {
        $item = DetalleMovimientosInventario::findOrFail($id);
        $item->delete();

        return redirect()->route('detalle-movimientos-inventario.index')->with('success', 'Detalle de movimiento eliminado.');
    }
}
