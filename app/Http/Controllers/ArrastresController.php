<?php

namespace App\Http\Controllers;

use App\Models\Arrastre;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ArrastresController extends Controller
{
    public function index()
    {
        $items = Arrastre::orderBy('id')->paginate(50);

        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Arrastres',
            'route' => 'arrastres',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nuevo Arrastre',
            'route' => 'arrastres',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        Arrastre::create($validated);

        return redirect()->route('arrastres.index')->with('success', 'Arrastre creado.');
    }

    public function show($id)
    {
        $item = Arrastre::findOrFail($id);

        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Arrastre',
            'route' => 'arrastres',
        ]);
    }

    public function edit($id)
    {
        $item = Arrastre::findOrFail($id);

        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Arrastre',
            'route' => 'arrastres',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = Arrastre::findOrFail($id);
        $item->update($validated);

        return redirect()->route('arrastres.index')->with('success', 'Arrastre actualizado.');
    }

    public function destroy($id)
    {
        $item = Arrastre::findOrFail($id);
        $item->delete();

        return redirect()->route('arrastres.index')->with('success', 'Arrastre eliminado.');
    }
}
