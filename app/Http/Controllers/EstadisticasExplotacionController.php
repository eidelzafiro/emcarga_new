<?php

namespace App\Http\Controllers;

use App\Models\EstadisticasExplotacion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EstadisticasExplotacionController extends Controller
{
    public function index()
    {
        $items = EstadisticasExplotacion::orderBy('id')->paginate(50);

        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Estadísticas de Explotación',
            'route' => 'estadisticas-explotacion',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nueva Estadística de Explotación',
            'route' => 'estadisticas-explotacion',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        EstadisticasExplotacion::create($validated);

        return redirect()->route('estadisticas-explotacion.index')->with('success', 'Estadística de explotación creada.');
    }

    public function show($id)
    {
        $item = EstadisticasExplotacion::findOrFail($id);

        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Estadística de Explotación',
            'route' => 'estadisticas-explotacion',
        ]);
    }

    public function edit($id)
    {
        $item = EstadisticasExplotacion::findOrFail($id);

        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Estadística de Explotación',
            'route' => 'estadisticas-explotacion',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = EstadisticasExplotacion::findOrFail($id);
        $item->update($validated);

        return redirect()->route('estadisticas-explotacion.index')->with('success', 'Estadística de explotación actualizada.');
    }

    public function destroy($id)
    {
        $item = EstadisticasExplotacion::findOrFail($id);
        $item->delete();

        return redirect()->route('estadisticas-explotacion.index')->with('success', 'Estadística de explotación eliminada.');
    }
}
