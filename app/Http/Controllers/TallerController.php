<?php

namespace App\Http\Controllers;

use App\Models\OrdenesTaller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TallerController extends Controller
{
    public function index(Request $request)
    {
        $ordenes = OrdenesTaller::with('tractivo:id,descripcion,placa', 'tipoMantenimiento:id,nombre')
            ->when($request->search, fn ($q, $s) => $q->where('numero', 'like', "%{$s}%")
                ->orWhere('diagnostico', 'like', "%{$s}%"))
            ->orderBy('fecha_ingreso', 'desc')
            ->paginate(20);

        return Inertia::render('Taller/Index', [
            'title' => 'Taller',
            'ordenes' => $ordenes,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero' => 'required|string|max:50|unique:ordenes_taller,numero',
            'id_tractivo' => 'required|exists:tractivos,id',
            'id_tipo_mantenimiento' => 'required|exists:tipos_mantenimiento,id',
            'fecha_ingreso' => 'required|date',
            'fecha_salida_estimada' => 'nullable|date',
            'kilometraje' => 'nullable|numeric',
            'estado' => 'nullable|string|max:50',
            'diagnostico' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ]);

        OrdenesTaller::create($validated);

        return redirect()->route('taller.index')
            ->with('success', 'Orden de taller creada correctamente.');
    }

    public function update(Request $request, OrdenesTaller $ordene)
    {
        $validated = $request->validate([
            'numero' => 'required|string|max:50|unique:ordenes_taller,numero,'.$ordene->id,
            'id_tractivo' => 'required|exists:tractivos,id',
            'id_tipo_mantenimiento' => 'required|exists:tipos_mantenimiento,id',
            'fecha_ingreso' => 'required|date',
            'fecha_salida_estimada' => 'nullable|date',
            'fecha_salida_real' => 'nullable|date',
            'kilometraje' => 'nullable|numeric',
            'estado' => 'nullable|string|max:50',
            'diagnostico' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ]);

        $ordene->update($validated);

        return redirect()->route('taller.index')
            ->with('success', 'Orden actualizada correctamente.');
    }

    public function destroy(OrdenesTaller $ordene)
    {
        $ordene->delete();

        return redirect()->route('taller.index')
            ->with('success', 'Orden eliminada correctamente.');
    }
}
