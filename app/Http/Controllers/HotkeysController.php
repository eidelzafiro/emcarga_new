<?php

namespace App\Http\Controllers;

use App\Models\Hotkey;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HotkeysController extends Controller
{
    public function index()
    {
        $items = Hotkey::orderBy('id')->paginate(50);
        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Hotkeys',
            'route' => 'hotkeys',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nueva Hotkey',
            'route' => 'hotkeys',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        Hotkey::create($validated);

        return redirect()->route('hotkeys.index')->with('success', 'Hotkey creada.');
    }

    public function show($id)
    {
        $item = Hotkey::findOrFail($id);
        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Hotkey',
            'route' => 'hotkeys',
        ]);
    }

    public function edit($id)
    {
        $item = Hotkey::findOrFail($id);
        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Hotkey',
            'route' => 'hotkeys',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = Hotkey::findOrFail($id);
        $item->update($validated);

        return redirect()->route('hotkeys.index')->with('success', 'Hotkey actualizada.');
    }

    public function destroy($id)
    {
        $item = Hotkey::findOrFail($id);
        $item->delete();

        return redirect()->route('hotkeys.index')->with('success', 'Hotkey eliminada.');
    }
}
