<?php

namespace App\Http\Controllers;

use App\Models\HistorialTractivo;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HistorialTractivosController extends Controller
{
    use EntidadScoping;

    public function index()
    {
        
        $this->authorize('viewAny', \App\Models\HistorialTractivo::class);
        $items = HistorialTractivo::query()
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereHas('tractivo', fn ($t) => $t->whereIn('id_entidad', $this->entidadesPermitidas())))
            ->orderBy('id')->paginate(50);

        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Historial Tractivos',
            'route' => 'historial-tractivos',
        ]);
    }

    public function create()
    {
        
        $this->authorize('create', \App\Models\HistorialTractivo::class);return Inertia::render('Catalogo/Form', [
            'title' => 'Nuevo Historial de Tractivo',
            'route' => 'historial-tractivos',
        ]);
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\HistorialTractivo::class);
        $validated = $request->validate([
            // add validation rules
        ]);

        HistorialTractivo::create($validated);

        return redirect()->route('historial-tractivos.index')->with('success', 'Historial de tractivo creado.');
    }

    public function show($id)
    {
        $item = HistorialTractivo::findOrFail($id);
        $this->autorizarEntidad($item->tractivo?->id_entidad);

        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Historial de Tractivo',
            'route' => 'historial-tractivos',
        ]);
    }

    public function edit($id)
    {
        $item = HistorialTractivo::findOrFail($id);
        $this->autorizarEntidad($item->tractivo?->id_entidad);

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
        $this->autorizarEntidad($item->tractivo?->id_entidad);
        $item->update($validated);

        return redirect()->route('historial-tractivos.index')->with('success', 'Historial de tractivo actualizado.');
    }

    public function destroy($id)
    {
        $item = HistorialTractivo::findOrFail($id);
        $this->autorizarEntidad($item->tractivo?->id_entidad);
        $item->delete();

        return redirect()->route('historial-tractivos.index')->with('success', 'Historial de tractivo eliminado.');
    }
}
