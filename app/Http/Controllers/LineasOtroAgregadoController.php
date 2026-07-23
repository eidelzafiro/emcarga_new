<?php

namespace App\Http\Controllers;

use App\Models\LineasOtroAgregado;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LineasOtroAgregadoController extends Controller
{
    public function index()
    {
        $items = LineasOtroAgregado::orderBy('id')->paginate(50);
        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Líneas de Otro Agregado',
            'route' => 'lineas-otro-agregado',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nueva Línea de Otro Agregado',
            'route' => 'lineas-otro-agregado',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        LineasOtroAgregado::create($validated);

        return redirect()->route('lineas-otro-agregado.index')->with('success', 'Línea de otro agregado creada.');
    }

    public function show($id)
    {
        $item = LineasOtroAgregado::findOrFail($id);
        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Línea de Otro Agregado',
            'route' => 'lineas-otro-agregado',
        ]);
    }

    public function edit($id)
    {
        $item = LineasOtroAgregado::findOrFail($id);
        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Línea de Otro Agregado',
            'route' => 'lineas-otro-agregado',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = LineasOtroAgregado::findOrFail($id);
        $item->update($validated);

        return redirect()->route('lineas-otro-agregado.index')->with('success', 'Línea de otro agregado actualizada.');
    }

    public function destroy($id)
    {
        $item = LineasOtroAgregado::findOrFail($id);
        $item->delete();

        return redirect()->route('lineas-otro-agregado.index')->with('success', 'Línea de otro agregado eliminada.');
    }
}
