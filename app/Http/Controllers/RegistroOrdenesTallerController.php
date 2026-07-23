<?php

namespace App\Http\Controllers;

use App\Models\RegistroOrdenesTaller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegistroOrdenesTallerController extends Controller
{
    public function index()
    {
        $items = RegistroOrdenesTaller::orderBy('id')->paginate(50);
        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Registro de Órdenes de Taller',
            'route' => 'registro-ordenes-taller',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nuevo Registro de Orden de Taller',
            'route' => 'registro-ordenes-taller',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        RegistroOrdenesTaller::create($validated);

        return redirect()->route('registro-ordenes-taller.index')->with('success', 'Registro de orden de taller creado.');
    }

    public function show($id)
    {
        $item = RegistroOrdenesTaller::findOrFail($id);
        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Registro de Orden de Taller',
            'route' => 'registro-ordenes-taller',
        ]);
    }

    public function edit($id)
    {
        $item = RegistroOrdenesTaller::findOrFail($id);
        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Registro de Orden de Taller',
            'route' => 'registro-ordenes-taller',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = RegistroOrdenesTaller::findOrFail($id);
        $item->update($validated);

        return redirect()->route('registro-ordenes-taller.index')->with('success', 'Registro de orden de taller actualizado.');
    }

    public function destroy($id)
    {
        $item = RegistroOrdenesTaller::findOrFail($id);
        $item->delete();

        return redirect()->route('registro-ordenes-taller.index')->with('success', 'Registro de orden de taller eliminado.');
    }
}
