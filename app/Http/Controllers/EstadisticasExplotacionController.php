<?php

namespace App\Http\Controllers;

use App\Models\EstadisticasExplotacion;
use App\Models\HojasRuta;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EstadisticasExplotacionController extends Controller
{
    use EntidadScoping;

    public function index()
    {
        $items = EstadisticasExplotacion::query()
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->whereIn('id_entidad', $this->entidadesPermitidas())))
            ->orderBy('id')->paginate(50);

        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Estad. Explotación',
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
        $this->autorizarEntidad($item->hojaRuta?->id_entidad);

        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Estadística de Explotación',
            'route' => 'estadisticas-explotacion',
        ]);
    }

    public function edit($id)
    {
        $item = EstadisticasExplotacion::findOrFail($id);
        $this->autorizarEntidad($item->hojaRuta?->id_entidad);

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
        $this->autorizarEntidad($item->hojaRuta?->id_entidad);
        $item->update($validated);

        return redirect()->route('estadisticas-explotacion.index')->with('success', 'Estadística de explotación actualizada.');
    }

    public function destroy($id)
    {
        $item = EstadisticasExplotacion::findOrFail($id);
        $this->autorizarEntidad($item->hojaRuta?->id_entidad);
        $item->delete();

        return redirect()->route('estadisticas-explotacion.index')->with('success', 'Estadística de explotación eliminada.');
    }
}
