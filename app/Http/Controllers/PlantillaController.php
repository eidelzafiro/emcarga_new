<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Plantilla;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlantillaController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $items = Plantilla::query()
            ->with(['area:id,nombre,id_entidad', 'cargo:id,nombre'])
            ->when($request->search, fn ($q, $s) => $q->whereHas('cargo', fn ($c) => $c->where('nombre', 'like', "%{$s}%"))
                ->orWhereHas('area', fn ($a) => $a->where('nombre', 'like', "%{$s}%")))
            ->when($request->id_area, fn ($q, $v) => $q->where('id_area', $v))
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
            ->orderBy('id_area')->orderBy('id_cargo')
            ->paginate(20);

        return Inertia::render('Plantilla/Index', [
            'title' => 'Plantilla de Puestos',
            'items' => $items,
            'areas' => Area::select('id', 'nombre')
                ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
                ->orderBy('nombre')->get(),
            'cargos' => Cargo::select('id', 'nombre')
                ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
                ->orderBy('nombre')->get(),
            'filters' => $request->only(['search', 'id_area']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);
        $validated['id_entidad'] = $this->entidadActiva();

        Plantilla::create($validated);

        return redirect()->route('plantilla.index')->with('success', 'Puesto de plantilla creado correctamente.');
    }

    public function update(Request $request, Plantilla $plantilla)
    {
        $this->autorizarEntidad($plantilla->id_entidad);
        $validated = $this->validar($request);

        $plantilla->update($validated);

        return redirect()->route('plantilla.index')->with('success', 'Puesto de plantilla actualizado correctamente.');
    }

    public function destroy(Plantilla $plantilla)
    {
        $this->autorizarEntidad($plantilla->id_entidad);
        $plantilla->delete();

        return redirect()->route('plantilla.index')->with('success', 'Puesto de plantilla eliminado correctamente.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'id_cargo' => 'required|exists:cargos,id',
            'id_area' => 'required|exists:areas,id',
            'aprobada' => 'nullable|integer|min:0',
            'cubierta' => 'nullable|integer|min:0',
            'cubierta2' => 'nullable|integer|min:0',
            'propuesta' => 'nullable|integer|min:0',
            'v_necesidad' => 'nullable|integer|min:0',
            'necesidad' => 'nullable|integer|min:0',
        ]);
    }

    private function entidadActiva(): ?int
    {
        $id = (int) session('entidad_activa_id');

        return $id ?: null;
    }
}
