<?php

namespace App\Http\Controllers;

use App\Models\ConsumoLubricante;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LubricantesController extends Controller
{
    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\Lubricante::class);
        $lubricantes = ConsumoLubricante::with('tractivo:id,descripcion,placa', 'tipoAceite:id,nombre')
            ->when($request->search, fn ($q, $s) => $q->where('folio', 'like', "%{$s}%"))
            ->paginate(20);

        return Inertia::render('Lubricantes/Index', [
            'title' => 'Lubricantes',
            'lubricantes' => $lubricantes,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\Lubricante::class);
        $validated = $request->validate([
            'folio' => 'required|unique:consumo_lubricantes,folio',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'id_tipo_aceite' => 'nullable|exists:tipos_lubricantes,id',
            'id_causa' => 'nullable|exists:tipos_causas,id',
            'cantidad' => 'required|numeric|min:0',
            'unidad' => 'nullable|string|max:20',
            'importe_mn' => 'nullable|numeric',
            'importe_me' => 'nullable|numeric',
            'fecha' => 'required|date',
        ]);

        ConsumoLubricante::create($validated);

        return redirect()->route('lubricantes.index')
            ->with('success', 'Consumo registrado correctamente.');
    }

    public function update(Request $request, ConsumoLubricante $lubricante)
    {
        
        $this->authorize('update', $lubricante);
        $validated = $request->validate([
            'folio' => 'required|unique:consumo_lubricantes,folio,'.$lubricante->id,
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'id_tipo_aceite' => 'nullable|exists:tipos_lubricantes,id',
            'id_causa' => 'nullable|exists:tipos_causas,id',
            'cantidad' => 'required|numeric|min:0',
            'unidad' => 'nullable|string|max:20',
            'importe_mn' => 'nullable|numeric',
            'importe_me' => 'nullable|numeric',
            'fecha' => 'required|date',
        ]);

        $lubricante->update($validated);

        return redirect()->route('lubricantes.index')
            ->with('success', 'Consumo actualizado correctamente.');
    }

    public function destroy(ConsumoLubricante $lubricante)
    {
        
        $this->authorize('delete', $lubricante);
        $lubricante->delete();

        return redirect()->route('lubricantes.index')
            ->with('success', 'Consumo eliminado correctamente.');
    }
}
