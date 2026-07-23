<?php

namespace App\Http\Controllers;

use App\Models\CompetenciasCargo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompetenciasCargoController extends Controller
{
    public function index()
    {
        $items = CompetenciasCargo::orderBy('id')->paginate(50);
        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Competencias de Cargo',
            'route' => 'competencias-cargo',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nueva Competencia de Cargo',
            'route' => 'competencias-cargo',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        CompetenciasCargo::create($validated);

        return redirect()->route('competencias-cargo.index')->with('success', 'Competencia de cargo creada.');
    }

    public function show($id)
    {
        $item = CompetenciasCargo::findOrFail($id);
        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Competencia de Cargo',
            'route' => 'competencias-cargo',
        ]);
    }

    public function edit($id)
    {
        $item = CompetenciasCargo::findOrFail($id);
        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Competencia de Cargo',
            'route' => 'competencias-cargo',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = CompetenciasCargo::findOrFail($id);
        $item->update($validated);

        return redirect()->route('competencias-cargo.index')->with('success', 'Competencia de cargo actualizada.');
    }

    public function destroy($id)
    {
        $item = CompetenciasCargo::findOrFail($id);
        $item->delete();

        return redirect()->route('competencias-cargo.index')->with('success', 'Competencia de cargo eliminada.');
    }
}
