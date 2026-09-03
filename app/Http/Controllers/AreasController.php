<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Area;
use App\Models\Entidad;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AreasController extends Controller
{
    use EntidadScoping;

    public function index()
    {
        $entidadId = (int) entidadActivaId();
        $idsEntidades = $entidadId ? Entidad::idsPermitidos($entidadId) : [];

        $areas = Area::whereIn('id_entidad', $idsEntidades)
            ->where('activo', true)
            ->with(['subAreas' => function ($q) {
                $q->where('activo', true)->with(['subAreas' => function ($q2) {
                    $q2->where('activo', true)->with(['subAreas' => function ($q3) {
                        $q3->where('activo', true);
                    }]);
                }]);
            }])
            ->whereNull('id_area_padre')
            ->orderBy('nombre')
            ->get();

        $todasAreas = Area::whereIn('id_entidad', $idsEntidades)
            ->where('activo', true)
            ->select('id', 'nombre', 'id_area_padre')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Areas/Organigrama', [
            'title' => 'Áreas',
            'areas' => $areas,
            'todasAreas' => $todasAreas,
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

        Area::create($validated);

        return back()->with('success', 'Área creada correctamente.');
    }

    public function update(Request $request, Area $area)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'id_area_padre' => 'nullable|exists:areas,id',
        ]);

        $area->update($validated);

        return back()->with('success', 'Área actualizada correctamente.');
    }

    public function destroy(Area $area)
    {
        if ($area->subAreas()->where('activo', true)->count() > 0) {
            return back()->withErrors(['error' => 'No se puede eliminar un área que tiene sub-áreas.']);
        }

        $area->update(['activo' => false]);

        return back()->with('success', 'Área eliminada correctamente.');
    }
}
