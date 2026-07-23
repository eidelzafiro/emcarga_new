<?php

namespace App\Http\Controllers;

use App\Models\CombustibleCarga;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CombustibleCargasController extends Controller
{
    public function index(Request $request)
    {
        $cargas = CombustibleCarga::with(['tarjeta', 'tractivo'])
            ->when($request->search, fn ($q, $s) => $q->where('numero', 'like', "%{$s}%")->orWhere('lugar', 'like', "%{$s}%"))
            ->orderBy('fecha_carga', 'desc')
            ->paginate(20);

        $tarjetas = \App\Models\Tarjeta::select('id', 'numero')->orderBy('numero')->get();
        $tractivos = \App\Models\Tractivo::select('id', 'codigo')->orderBy('codigo')->get();

        return Inertia::render('CombustibleCargas/Index', [
            'title' => 'Cargas de Combustible',
            'cargas' => $cargas,
            'tarjetas' => $tarjetas,
            'tractivos' => $tractivos,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero' => 'required|unique:combustible_cargas,numero|max:50',
            'id_tarjeta' => 'required|exists:tarjetas,id',
            'id_tractivo' => 'required|exists:tractivos,id',
            'fecha_carga' => 'required|date',
            'cantidad_litros' => 'required|numeric|min:0',
            'precio_litro' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'tipo_combustible' => 'required|max:50',
            'lugar' => 'nullable|max:255',
        ]);
        CombustibleCarga::create($validated);
        return redirect()->route('combustible-cargas.index')->with('success', 'Carga de combustible creada correctamente.');
    }

    public function update(Request $request, CombustibleCarga $combustibleCarga)
    {
        $validated = $request->validate([
            'numero' => 'required|unique:combustible_cargas,numero,' . $combustibleCarga->id . '|max:50',
            'id_tarjeta' => 'required|exists:tarjetas,id',
            'id_tractivo' => 'required|exists:tractivos,id',
            'fecha_carga' => 'required|date',
            'cantidad_litros' => 'required|numeric|min:0',
            'precio_litro' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'tipo_combustible' => 'required|max:50',
            'lugar' => 'nullable|max:255',
        ]);
        $combustibleCarga->update($validated);
        return redirect()->route('combustible-cargas.index')->with('success', 'Carga de combustible actualizada correctamente.');
    }

    public function destroy(CombustibleCarga $combustibleCarga)
    {
        $combustibleCarga->delete();
        return redirect()->route('combustible-cargas.index')->with('success', 'Carga de combustible eliminada correctamente.');
    }
}
