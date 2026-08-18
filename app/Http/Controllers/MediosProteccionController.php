<?php

namespace App\Http\Controllers;

use App\Models\MedioProteccion;
use App\Models\TipoMedioProteccion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MediosProteccionController extends Controller
{
    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\MedioProteccion::class);
        $items = MedioProteccion::with('tipoMedioProteccion')
            ->when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%"))
            ->orderBy('nombre')
            ->paginate(20);

        $tipos = TipoMedioProteccion::select('id', 'nombre')->orderBy('nombre')->get();

        return Inertia::render('MediosProteccion/Index', [
            'title' => 'Medios de Protección',
            'items' => $items,
            'tipos' => $tipos,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\MedioProteccion::class);
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'id_tipo_medio_proteccion' => 'nullable|exists:tipos_medios_proteccion,id',
            'duracion' => 'nullable|integer|min:0',
            'tipo_duracion' => 'nullable|string|max:150',
            'activo' => 'boolean',
        ]);
        MedioProteccion::create($validated);

        return redirect()->route('medios-proteccion.index')->with('success', 'Medio creado correctamente.');
    }

    public function update(Request $request, MedioProteccion $mediosProteccion)
    {
        
        $this->authorize('update', $mediosProteccion);
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'id_tipo_medio_proteccion' => 'nullable|exists:tipos_medios_proteccion,id',
            'duracion' => 'nullable|integer|min:0',
            'tipo_duracion' => 'nullable|string|max:150',
            'activo' => 'boolean',
        ]);
        $mediosProteccion->update($validated);

        return redirect()->route('medios-proteccion.index')->with('success', 'Medio actualizado correctamente.');
    }

    public function destroy(MedioProteccion $mediosProteccion)
    {
        
        $this->authorize('delete', $mediosProteccion);
        $mediosProteccion->delete();

        return redirect()->route('medios-proteccion.index')->with('success', 'Medio eliminado correctamente.');
    }
}
