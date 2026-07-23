<?php

namespace App\Http\Controllers;

use App\Models\LineasDiferencial;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LineasDiferencialController extends Controller
{
    public function index()
    {
        $items = LineasDiferencial::orderBy('id')->paginate(50);
        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Líneas de Diferencial',
            'route' => 'lineas-diferencial',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nueva Línea de Diferencial',
            'route' => 'lineas-diferencial',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        LineasDiferencial::create($validated);

        return redirect()->route('lineas-diferencial.index')->with('success', 'Línea de diferencial creada.');
    }

    public function show($id)
    {
        $item = LineasDiferencial::findOrFail($id);
        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Línea de Diferencial',
            'route' => 'lineas-diferencial',
        ]);
    }

    public function edit($id)
    {
        $item = LineasDiferencial::findOrFail($id);
        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Línea de Diferencial',
            'route' => 'lineas-diferencial',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = LineasDiferencial::findOrFail($id);
        $item->update($validated);

        return redirect()->route('lineas-diferencial.index')->with('success', 'Línea de diferencial actualizada.');
    }

    public function destroy($id)
    {
        $item = LineasDiferencial::findOrFail($id);
        $item->delete();

        return redirect()->route('lineas-diferencial.index')->with('success', 'Línea de diferencial eliminada.');
    }
}
