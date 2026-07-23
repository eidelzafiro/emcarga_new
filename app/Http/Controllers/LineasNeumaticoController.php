<?php

namespace App\Http\Controllers;

use App\Models\LineasNeumatico;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LineasNeumaticoController extends Controller
{
    public function index()
    {
        $items = LineasNeumatico::orderBy('id')->paginate(50);

        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Líneas de Neumático',
            'route' => 'lineas-neumatico',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nueva Línea de Neumático',
            'route' => 'lineas-neumatico',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        LineasNeumatico::create($validated);

        return redirect()->route('lineas-neumatico.index')->with('success', 'Línea de neumático creada.');
    }

    public function show($id)
    {
        $item = LineasNeumatico::findOrFail($id);

        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Línea de Neumático',
            'route' => 'lineas-neumatico',
        ]);
    }

    public function edit($id)
    {
        $item = LineasNeumatico::findOrFail($id);

        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Línea de Neumático',
            'route' => 'lineas-neumatico',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = LineasNeumatico::findOrFail($id);
        $item->update($validated);

        return redirect()->route('lineas-neumatico.index')->with('success', 'Línea de neumático actualizada.');
    }

    public function destroy($id)
    {
        $item = LineasNeumatico::findOrFail($id);
        $item->delete();

        return redirect()->route('lineas-neumatico.index')->with('success', 'Línea de neumático eliminada.');
    }
}
