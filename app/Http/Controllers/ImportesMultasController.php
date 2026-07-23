<?php

namespace App\Http\Controllers;

use App\Models\ImportesMulta;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ImportesMultasController extends Controller
{
    public function index()
    {
        $items = ImportesMulta::orderBy('id')->paginate(50);
        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Importes de Multas',
            'route' => 'importes-multas',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nuevo Importe de Multa',
            'route' => 'importes-multas',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        ImportesMulta::create($validated);

        return redirect()->route('importes-multas.index')->with('success', 'Importe de multa creado.');
    }

    public function show($id)
    {
        $item = ImportesMulta::findOrFail($id);
        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Importe de Multa',
            'route' => 'importes-multas',
        ]);
    }

    public function edit($id)
    {
        $item = ImportesMulta::findOrFail($id);
        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Importe de Multa',
            'route' => 'importes-multas',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = ImportesMulta::findOrFail($id);
        $item->update($validated);

        return redirect()->route('importes-multas.index')->with('success', 'Importe de multa actualizado.');
    }

    public function destroy($id)
    {
        $item = ImportesMulta::findOrFail($id);
        $item->delete();

        return redirect()->route('importes-multas.index')->with('success', 'Importe de multa eliminado.');
    }
}
