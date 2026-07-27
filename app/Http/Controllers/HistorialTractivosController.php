<?php

namespace App\Http\Controllers;

use App\Models\HistorialTractivo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HistorialTractivosController extends Controller
{
    public function index()
    {
        $items = HistorialTractivo::when(session('entidad_activa_id'), fn ($q, $id) => $q->where('id_entidad', $id))
            ->orderBy('id')->paginate(50);

        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Historial de Tractivos',
            'route' => 'historial-tractivos',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nuevo Historial de Tractivo',
            'route' => 'historial-tractivos',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        HistorialTractivo::create($validated);

        return redirect()->route('historial-tractivos.index')->with('success', 'Historial de tractivo creado.');
    }

    public function show($id)
    {
        $item = HistorialTractivo::findOrFail($id);

        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Historial de Tractivo',
            'route' => 'historial-tractivos',
        ]);
    }

    public function edit($id)
    {
        $item = HistorialTractivo::findOrFail($id);

        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Historial de Tractivo',
            'route' => 'historial-tractivos',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = HistorialTractivo::findOrFail($id);
        $item->update($validated);

        return redirect()->route('historial-tractivos.index')->with('success', 'Historial de tractivo actualizado.');
    }

    public function destroy($id)
    {
        $item = HistorialTractivo::findOrFail($id);
        $item->delete();

        return redirect()->route('historial-tractivos.index')->with('success', 'Historial de tractivo eliminado.');
    }
}
