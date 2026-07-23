<?php

namespace App\Http\Controllers;

use App\Models\ImportesGp;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ImportesGpsController extends Controller
{
    public function index()
    {
        $items = ImportesGp::orderBy('id')->paginate(50);

        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Importes GPS',
            'route' => 'importes-gps',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nuevo Importe GPS',
            'route' => 'importes-gps',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        ImportesGp::create($validated);

        return redirect()->route('importes-gps.index')->with('success', 'Importe GPS creado.');
    }

    public function show($id)
    {
        $item = ImportesGp::findOrFail($id);

        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Importe GPS',
            'route' => 'importes-gps',
        ]);
    }

    public function edit($id)
    {
        $item = ImportesGp::findOrFail($id);

        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Importe GPS',
            'route' => 'importes-gps',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = ImportesGp::findOrFail($id);
        $item->update($validated);

        return redirect()->route('importes-gps.index')->with('success', 'Importe GPS actualizado.');
    }

    public function destroy($id)
    {
        $item = ImportesGp::findOrFail($id);
        $item->delete();

        return redirect()->route('importes-gps.index')->with('success', 'Importe GPS eliminado.');
    }
}
