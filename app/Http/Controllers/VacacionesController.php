<?php

namespace App\Http\Controllers;

use App\Models\Vacacione;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VacacionesController extends Controller
{
    public function index()
    {
        
        $this->authorize('viewAny', \App\Models\Vacacione::class);
        $items = Vacacione::orderBy('id')->paginate(50);

        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Vacaciones',
            'route' => 'vacaciones',
        ]);
    }

    public function create()
    {
        
        $this->authorize('create', \App\Models\Vacacione::class);return Inertia::render('Catalogo/Form', [
            'title' => 'Nueva Vacación',
            'route' => 'vacaciones',
        ]);
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\Vacacione::class);
        $validated = $request->validate([
            // add validation rules
        ]);

        Vacacione::create($validated);

        return redirect()->route('vacaciones.index')->with('success', 'Vacación creada.');
    }

    public function show($id)
    {
        $item = Vacacione::findOrFail($id);

        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Vacación',
            'route' => 'vacaciones',
        ]);
    }

    public function edit($id)
    {
        $item = Vacacione::findOrFail($id);

        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Vacación',
            'route' => 'vacaciones',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = Vacacione::findOrFail($id);
        $item->update($validated);

        return redirect()->route('vacaciones.index')->with('success', 'Vacación actualizada.');
    }

    public function destroy($id)
    {
        $item = Vacacione::findOrFail($id);
        $item->delete();

        return redirect()->route('vacaciones.index')->with('success', 'Vacación eliminada.');
    }
}
