<?php

namespace App\Http\Controllers;

use App\Models\FuncionesCargo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FuncionesCargoController extends Controller
{
    public function index()
    {
        $items = FuncionesCargo::orderBy('id')->paginate(50);

        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Funciones de Cargo',
            'route' => 'funciones-cargo',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nueva Función de Cargo',
            'route' => 'funciones-cargo',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        FuncionesCargo::create($validated);

        return redirect()->route('funciones-cargo.index')->with('success', 'Función de cargo creada.');
    }

    public function show($id)
    {
        $item = FuncionesCargo::findOrFail($id);

        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Función de Cargo',
            'route' => 'funciones-cargo',
        ]);
    }

    public function edit($id)
    {
        $item = FuncionesCargo::findOrFail($id);

        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Función de Cargo',
            'route' => 'funciones-cargo',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = FuncionesCargo::findOrFail($id);
        $item->update($validated);

        return redirect()->route('funciones-cargo.index')->with('success', 'Función de cargo actualizada.');
    }

    public function destroy($id)
    {
        $item = FuncionesCargo::findOrFail($id);
        $item->delete();

        return redirect()->route('funciones-cargo.index')->with('success', 'Función de cargo eliminada.');
    }
}
