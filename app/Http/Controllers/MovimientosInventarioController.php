<?php

namespace App\Http\Controllers;

use App\Models\MovimientosInventario;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MovimientosInventarioController extends Controller
{
    public function index()
    {
        $items = MovimientosInventario::orderBy('id')->paginate(50);

        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Movimientos de Inventario',
            'route' => 'movimientos-inventario',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nuevo Movimiento de Inventario',
            'route' => 'movimientos-inventario',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        MovimientosInventario::create($validated);

        return redirect()->route('movimientos-inventario.index')->with('success', 'Movimiento de inventario creado.');
    }

    public function show($id)
    {
        $item = MovimientosInventario::findOrFail($id);

        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Movimiento de Inventario',
            'route' => 'movimientos-inventario',
        ]);
    }

    public function edit($id)
    {
        $item = MovimientosInventario::findOrFail($id);

        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Movimiento de Inventario',
            'route' => 'movimientos-inventario',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
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
