<?php

namespace App\Http\Controllers;

use App\Models\DetalleValesInventario;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DetalleValesInventarioController extends Controller
{
    public function index()
    {
        $items = DetalleValesInventario::orderBy('id')->paginate(50);

        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Detalle de Vales',
            'route' => 'detalle-vales-inventario',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nuevo Detalle de Vale',
            'route' => 'detalle-vales-inventario',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        DetalleValesInventario::create($validated);

        return redirect()->route('detalle-vales-inventario.index')->with('success', 'Detalle de vale creado.');
    }

    public function show($id)
    {
        $item = DetalleValesInventario::findOrFail($id);

        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Detalle de Vale',
            'route' => 'detalle-vales-inventario',
        ]);
    }

    public function edit($id)
    {
        $item = DetalleValesInventario::findOrFail($id);

        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Detalle de Vale',
            'route' => 'detalle-vales-inventario',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = DetalleValesInventario::findOrFail($id);
        $item->update($validated);

        return redirect()->route('detalle-vales-inventario.index')->with('success', 'Detalle de vale actualizado.');
    }

    public function destroy($id)
    {
        $item = DetalleValesInventario::findOrFail($id);
        $item->delete();

        return redirect()->route('detalle-vales-inventario.index')->with('success', 'Detalle de vale eliminado.');
    }
}
