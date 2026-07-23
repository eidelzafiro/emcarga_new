<?php

namespace App\Http\Controllers;

use App\Models\CombustibleCarga;
use App\Models\CombustibleDescarga;
use App\Models\Tractivo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CombustibleDescargasController extends Controller
{
    public function index(Request $request)
    {
        $descargas = CombustibleDescarga::with(['carga', 'tractivo'])
            ->when($request->search, fn ($q, $s) => $q->where('tipo_combustible', 'like', "%{$s}%"))
            ->orderBy('fecha_descarga', 'desc')
            ->paginate(20);

        $cargas = CombustibleCarga::select('id', 'numero')->orderBy('numero')->get();
        $tractivos = Tractivo::select('id', 'codigo')->orderBy('codigo')->get();

        return Inertia::render('CombustibleDescargas/Index', [
            'title' => 'Descargas de Combustible',
            'descargas' => $descargas,
            'cargas' => $cargas,
            'tractivos' => $tractivos,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_carga' => 'required|exists:combustible_cargas,id',
            'id_tractivo' => 'required|exists:tractivos,id',
            'fecha_descarga' => 'required|date',
            'cantidad_litros' => 'required|numeric|min:0',
            'kilometraje' => 'nullable|numeric|min:0',
            'tipo_combustible' => 'required|max:50',
        ]);
        CombustibleDescarga::create($validated);

        return redirect()->route('combustible-descargas.index')->with('success', 'Descarga de combustible creada correctamente.');
    }

    public function update(Request $request, CombustibleDescarga $combustibleDescarga)
    {
        $validated = $request->validate([
            'id_carga' => 'required|exists:combustible_cargas,id',
            'id_tractivo' => 'required|exists:tractivos,id',
            'fecha_descarga' => 'required|date',
            'cantidad_litros' => 'required|numeric|min:0',
            'kilometraje' => 'nullable|numeric|min:0',
            'tipo_combustible' => 'required|max:50',
        ]);
        $combustibleDescarga->update($validated);

        return redirect()->route('combustible-descargas.index')->with('success', 'Descarga de combustible actualizada correctamente.');
    }

    public function destroy(CombustibleDescarga $combustibleDescarga)
    {
        $combustibleDescarga->delete();

        return redirect()->route('combustible-descargas.index')->with('success', 'Descarga de combustible eliminada correctamente.');
    }
}
