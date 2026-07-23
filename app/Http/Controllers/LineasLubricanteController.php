<?php

namespace App\Http\Controllers;

use App\Models\LineasLubricante;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LineasLubricanteController extends Controller
{
    public function index()
    {
        $items = LineasLubricante::orderBy('id')->paginate(50);
        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Líneas de Lubricante',
            'route' => 'lineas-lubricante',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nueva Línea de Lubricante',
            'route' => 'lineas-lubricante',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        LineasLubricante::create($validated);

        return redirect()->route('lineas-lubricante.index')->with('success', 'Línea de lubricante creada.');
    }

    public function show($id)
    {
        $item = LineasLubricante::findOrFail($id);
        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Línea de Lubricante',
            'route' => 'lineas-lubricante',
        ]);
    }

    public function edit($id)
    {
        $item = LineasLubricante::findOrFail($id);
        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Línea de Lubricante',
            'route' => 'lineas-lubricante',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = LineasLubricante::findOrFail($id);
        $item->update($validated);

        return redirect()->route('lineas-lubricante.index')->with('success', 'Línea de lubricante actualizada.');
    }

    public function destroy($id)
    {
        $item = LineasLubricante::findOrFail($id);
        $item->delete();

        return redirect()->route('lineas-lubricante.index')->with('success', 'Línea de lubricante eliminada.');
    }
}
