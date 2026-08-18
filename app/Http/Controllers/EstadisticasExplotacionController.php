<?php

namespace App\Http\Controllers;

use App\Models\EstadisticasExplotacion;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EstadisticasExplotacionController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $items = EstadisticasExplotacion::query()
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->whereIn('id_entidad', $this->entidadesPermitidas())))
            ->orderBy('id')->paginate(50);

        return Inertia::render('EstadisticasExplotacion/Index', [
            'items' => $items,
            'title' => 'Estad. Explotación',
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return redirect()->route('estadisticas-explotacion.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_hoja_ruta' => 'nullable|integer',
            'fecha_indicadores' => 'nullable|date',
            'viajes' => 'nullable|numeric|min:0',
            'kms_carga' => 'nullable|numeric|min:0',
            'kms_vacio' => 'nullable|numeric|min:0',
            'kms_total' => 'nullable|numeric|min:0',
            'toneladas_posibles' => 'nullable|numeric|min:0',
            'toneladas_reales' => 'nullable|numeric|min:0',
            'trafico_posible' => 'nullable|numeric|min:0',
            'trafico_producido' => 'nullable|numeric|min:0',
        ]);

        EstadisticasExplotacion::create($validated);

        return redirect()->route('estadisticas-explotacion.index')->with('success', 'Estadística de explotación creada.');
    }

    public function show($id)
    {
        return redirect()->route('estadisticas-explotacion.index');
    }

    public function edit($id)
    {
        return redirect()->route('estadisticas-explotacion.index');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_hoja_ruta' => 'nullable|integer',
            'fecha_indicadores' => 'nullable|date',
            'viajes' => 'nullable|numeric|min:0',
            'kms_carga' => 'nullable|numeric|min:0',
            'kms_vacio' => 'nullable|numeric|min:0',
            'kms_total' => 'nullable|numeric|min:0',
            'toneladas_posibles' => 'nullable|numeric|min:0',
            'toneladas_reales' => 'nullable|numeric|min:0',
            'trafico_posible' => 'nullable|numeric|min:0',
            'trafico_producido' => 'nullable|numeric|min:0',
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
