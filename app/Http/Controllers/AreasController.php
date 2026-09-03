<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Area;
use App\Models\Bolsa;
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

        // Cargar áreas con sub-áreas anidadas (3 niveles)
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
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $todasAreas = Area::whereIn('id_entidad', $idsEntidades)
            ->where('activo', true)
            ->select('id', 'nombre', 'id_area_padre', 'orden', 'imagen')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        // Conteo directo de trabajadores por área
        $conteoDirecto = Bolsa::query()
            ->selectRaw('id_area, COUNT(*) as total')
            ->where('activo', true)
            ->whereIn('id_entidad', $idsEntidades)
            ->groupBy('id_area')
            ->pluck('total', 'id_area')
            ->toArray();

        // Calcular totales agregados (área + todas sus sub-áreas recursivamente)
        $trabajadoresPorArea = [];
        $this->calcularTotales($areas, $conteoDirecto, $trabajadoresPorArea);

        return Inertia::render('Areas/Organigrama', [
            'title' => 'Áreas',
            'areas' => $areas,
            'todasAreas' => $todasAreas,
            'trabajadoresPorArea' => $trabajadoresPorArea,
        ]);
    }

    /**
     * Calcula recursivamente el total de trabajadores de cada área
     * sumando sus trabajadores directos + los de todas sus sub-áreas.
     */
    private function calcularTotales($areas, array $conteoDirecto, array &$resultado): int
    {
        $total = 0;
        foreach ($areas as $area) {
            $directo = $conteoDirecto[$area->id] ?? 0;
            $subTotal = 0;
            if ($area->subAreas && $area->subAreas->count() > 0) {
                $subTotal = $this->calcularTotales($area->subAreas, $conteoDirecto, $resultado);
            }
            $totalArea = $directo + $subTotal;
            $resultado[$area->id] = $totalArea;
            $total += $totalArea;
        }
        return $total;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'id_area_padre' => 'nullable|exists:areas,id',
            'orden' => 'nullable|integer|min:0',
            'imagen' => 'nullable|string|max:500',
        ]);

        $entidadId = (int) entidadActivaId();
        $validated['id_entidad'] = $entidadId;
        $validated['activo'] = true;
        $validated['orden'] = $validated['orden'] ?? 0;

        Area::create($validated);

        return back()->with('success', 'Área creada correctamente.');
    }

    public function update(Request $request, Area $area)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'id_area_padre' => 'nullable|exists:areas,id',
            'orden' => 'nullable|integer|min:0',
            'imagen' => 'nullable|string|max:500',
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
