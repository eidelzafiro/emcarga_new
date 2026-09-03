<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Area;
use App\Models\Entidad;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AreasOrganigramaController extends Controller
{
    use EntidadScoping;

    public function index()
    {
        $entidadId = (int) entidadActivaId();
        $idsEntidades = $entidadId ? Entidad::idsPermitidos($entidadId) : [];

        $areas = Area::whereIn('id_entidad', $idsEntidades)
            ->where('activo', true)
            ->with(['subAreas' => function ($q) {
                $q->where('activo', true)->with('subAreas');
            }])
            ->whereNull('id_area_padre')
            ->orderBy('nombre')
            ->get();

        $todasAreas = Area::whereIn('id_entidad', $idsEntidades)
            ->where('activo', true)
            ->select('id', 'nombre', 'id_area_padre')
            ->orderBy('nombre')
            ->get();

        $trabajadoresPorArea = \App\Models\Bolsa::where('activo', true)
            ->whereIn('id_entidad', $idsEntidades)
            ->whereNotNull('id_area')
            ->selectRaw('id_area, COUNT(*) as total')
            ->groupBy('id_area')
            ->pluck('total', 'id_area')
            ->toArray();

        return Inertia::render('Areas/Organigrama', [
            'title' => 'Organigrama de Áreas',
            'areas' => $areas,
            'todasAreas' => $todasAreas,
            'trabajadoresPorArea' => $trabajadoresPorArea,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'id_area_padre' => 'nullable|exists:areas,id',
        ]);

        $entidadId = (int) entidadActivaId();
        $validated['id_entidad'] = $entidadId;
        $validated['activo'] = true;

        $area = Area::create($validated);

        return redirect()->route('areas.organigrama')
            ->with('success', 'Área creada correctamente.');
    }

    public function update(Request $request, Area $area)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'id_area_padre' => 'nullable|exists:areas,id',
        ]);

        $area->update($validated);

        return redirect()->route('areas.organigrama')
            ->with('success', 'Área actualizada correctamente.');
    }

    public function destroy(Area $area)
    {
        if ($area->subAreas()->where('activo', true)->count() > 0) {
            return back()->withErrors(['error' => 'No se puede eliminar un área que tiene sub-áreas.']);
        }

        $area->update(['activo' => false]);

        return redirect()->route('areas.organigrama')
            ->with('success', 'Área eliminada correctamente.');
    }
}
