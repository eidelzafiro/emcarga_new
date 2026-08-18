<?php

namespace App\Http\Controllers;

use App\Models\Tarifa;
use App\Models\TipoCarga;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TarifasController extends Controller
{
    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\Tarifa::class);
        $items = Tarifa::with('tipoCarga')
            ->when($request->id_tipo_carga, fn ($q, $v) => $q->where('id_tipo_carga', $v))
            ->when($request->version, fn ($q, $v) => $q->where('version', $v))
            ->orderBy('kms')
            ->paginate(20);

        $tiposCarga = TipoCarga::select('id', 'nombre')->orderBy('nombre')->get();

        return Inertia::render('Tarifas/Index', [
            'title' => 'Tarifas',
            'items' => $items,
            'tiposCarga' => $tiposCarga,
            'filters' => $request->only(['id_tipo_carga', 'version']),
        ]);
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\Tarifa::class);
        $validated = $request->validate([
            'id_tipo_carga' => 'required|exists:tipos_cargas,id',
            'kms' => 'nullable|numeric|min:0',
            'tarifa_mt' => 'nullable|numeric|min:0',
            'version' => 'required|in:normal,46',
        ]);
        Tarifa::create($validated);

        return redirect()->route('tarifas.index')->with('success', 'Tarifa creada correctamente.');
    }

    public function update(Request $request, Tarifa $tarifa)
    {
        
        $this->authorize('update', $tarifa);
        $validated = $request->validate([
            'id_tipo_carga' => 'required|exists:tipos_cargas,id',
            'kms' => 'nullable|numeric|min:0',
            'tarifa_mt' => 'nullable|numeric|min:0',
            'version' => 'required|in:normal,46',
        ]);
        $tarifa->update($validated);

        return redirect()->route('tarifas.index')->with('success', 'Tarifa actualizada correctamente.');
    }

    public function destroy(Tarifa $tarifa)
    {
        
        $this->authorize('delete', $tarifa);
        $tarifa->delete();

        return redirect()->route('tarifas.index')->with('success', 'Tarifa eliminada correctamente.');
    }
}
