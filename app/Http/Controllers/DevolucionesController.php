<?php

namespace App\Http\Controllers;

use App\Models\Devolucione;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DevolucionesController extends Controller
{
    public function index()
    {
        $items = Devolucione::orderBy('id')->paginate(50);

        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Devoluciones',
            'route' => 'devoluciones',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nueva Devolución',
            'route' => 'devoluciones',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        Devolucione::create($validated);

        return redirect()->route('devoluciones.index')->with('success', 'Devolución creada.');
    }

    public function show($id)
    {
        $item = Devolucione::findOrFail($id);

        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Devolución',
            'route' => 'devoluciones',
        ]);
    }

    public function edit($id)
    {
        $item = Devolucione::findOrFail($id);

        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Devolución',
            'route' => 'devoluciones',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = Devolucione::findOrFail($id);
        $item->update($validated);

        return redirect()->route('devoluciones.index')->with('success', 'Devolución actualizada.');
    }

    public function destroy($id)
    {
        $item = Devolucione::findOrFail($id);
        $item->delete();

        return redirect()->route('devoluciones.index')->with('success', 'Devolución eliminada.');
    }
}
