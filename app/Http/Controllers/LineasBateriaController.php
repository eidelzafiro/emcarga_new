<?php

namespace App\Http\Controllers;

use App\Models\LineasBaterium;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LineasBateriaController extends Controller
{
    public function index()
    {
        $items = LineasBaterium::orderBy('id')->paginate(50);

        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Líneas de Batería',
            'route' => 'lineas-bateria',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nueva Línea de Batería',
            'route' => 'lineas-bateria',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        LineasBaterium::create($validated);

        return redirect()->route('lineas-bateria.index')->with('success', 'Línea de batería creada.');
    }

    public function show($id)
    {
        $item = LineasBaterium::findOrFail($id);

        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Línea de Batería',
            'route' => 'lineas-bateria',
        ]);
    }

    public function edit($id)
    {
        $item = LineasBaterium::findOrFail($id);

        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Línea de Batería',
            'route' => 'lineas-bateria',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = LineasBaterium::findOrFail($id);
        $item->update($validated);

        return redirect()->route('lineas-bateria.index')->with('success', 'Línea de batería actualizada.');
    }

    public function destroy($id)
    {
        $item = LineasBaterium::findOrFail($id);
        $item->delete();

        return redirect()->route('lineas-bateria.index')->with('success', 'Línea de batería eliminada.');
    }
}
