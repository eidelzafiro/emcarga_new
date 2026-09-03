<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Tasa;
use App\Models\TipoCarga;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TasasController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $query = Tasa::query()
            ->with(['tipoCarga:id,nombre'])
            ->when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%"))
            ->when($request->id_tipo_carga, fn ($q, $v) => $q->where('id_tipo_carga', $v))
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()));

        if ($request->mostrar_todas) {
            $items = $query->orderBy('id_tipo_carga')->orderBy('nombre')->orderBy('version', 'desc')->paginate(50);
        } else {
            $items = $query->where('activo', true)
                ->orderBy('id_tipo_carga')->orderBy('nombre')
                ->paginate(50);
        }

        $agrupadas = $items->getCollection()
            ->groupBy(fn ($tasa) => $tasa->tipoCarga?->nombre ?? 'Sin tipo')
            ->map(function ($grupo, $tipoNombre) {
                return [
                    'tipo_carga' => $tipoNombre,
                    'tasas' => $grupo->values(),
                ];
            })->values();

        return Inertia::render('Tasas/Index', [
            'title' => 'Tasas Salariales',
            'items' => $items,
            'agrupadas' => $agrupadas,
            'tiposCarga' => TipoCarga::orderBy('nombre')->get(['id', 'nombre']),
            'filters' => $request->only(['search', 'id_tipo_carga', 'mostrar_todas']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);
        $validated['id_entidad'] = (int) entidadActivaId() ?: null;

        $ultimaVersion = Tasa::where('id_tipo_carga', $validated['id_tipo_carga'] ?? null)
            ->where('id_entidad', $validated['id_entidad'])
            ->max('version') ?? 0;

        $validated['version'] = $ultimaVersion + 1;
        $validated['activo'] = true;

        if (!empty($validated['fecha_inicio'])) {
            Tasa::where('id_tipo_carga', $validated['id_tipo_carga'] ?? null)
                ->where('id_entidad', $validated['id_entidad'])
                ->where('activo', true)
                ->update(['activo' => false, 'fecha_fin' => now()->subDay()]);
        }

        Tasa::create($validated);

        return redirect()->route('tasas.index')->with('success', 'Tasa creada correctamente.');
    }

    public function update(Request $request, Tasa $tasa)
    {
        $this->autorizarEntidad($tasa->id_entidad);
        $validated = $this->validar($request);

        $tasa->update($validated);

        return redirect()->route('tasas.index')->with('success', 'Tasa actualizada correctamente.');
    }

    public function destroy(Tasa $tasa)
    {
        $this->autorizarEntidad($tasa->id_entidad);
        $tasa->delete();

        return redirect()->route('tasas.index')->with('success', 'Tasa eliminada correctamente.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => 'required|string|max:250',
            'tasa' => 'required|numeric|min:0',
            'tasa2' => 'nullable|numeric|min:0',
            'id_tipo_carga' => 'nullable|exists:tipos_cargas,id',
            'distancia_1' => 'nullable|integer|min:0',
            'distancia_2' => 'nullable|integer|min:0',
            'capacidad_1' => 'nullable|integer|min:0',
            'capacidad_2' => 'nullable|integer|min:0',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);
    }
}
