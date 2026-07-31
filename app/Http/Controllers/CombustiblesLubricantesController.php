<?php

namespace App\Http\Controllers;

use App\Models\CombustibleCarga;
use App\Models\CombustibleLubricante;
use App\Models\TipoCausa;
use App\Models\TipoLubricante;
use App\Models\Tractivo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CombustiblesLubricantesController extends Controller
{
    public function index(Request $request)
    {
        $items = CombustibleLubricante::with(['carga', 'tractivo', 'tipoLubricante', 'causa'])
            ->when($request->id_tractivo, fn ($q, $v) => $q->where('id_tractivo', $v))
            ->orderBy('fecha', 'desc')
            ->paginate(20);

        $cargas = CombustibleCarga::select('id', 'numero')->orderBy('numero')->get();
        $tractivos = Tractivo::select('id', 'codigo')->orderBy('codigo')->get();
        $lubricantes = TipoLubricante::select('id', 'nombre')->orderBy('nombre')->get();
        $causas = TipoCausa::select('id', 'nombre')->orderBy('nombre')->get();

        return Inertia::render('CombustiblesLubricantes/Index', [
            'title' => 'Comb. Lubricantes',
            'items' => $items,
            'cargas' => $cargas,
            'tractivos' => $tractivos,
            'lubricantes' => $lubricantes,
            'causas' => $causas,
            'filters' => $request->only(['id_tractivo']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_carga' => 'nullable|exists:combustible_cargas,id',
            'id_tractivo' => 'required|exists:tractivos,id',
            'id_tipo_lubricante' => 'nullable|exists:tipos_lubricantes,id',
            'id_causa' => 'nullable|exists:tipos_causas,id',
            'fecha' => 'required|date',
            'folio' => 'required|max:50',
            'cantidad' => 'required|numeric|min:0',
            'importe_mn' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);
        CombustibleLubricante::create($validated);

        return redirect()->route('combustibles-lubricantes.index')->with('success', 'Registro creado correctamente.');
    }

    public function update(Request $request, CombustibleLubricante $combustiblesLubricante)
    {
        $validated = $request->validate([
            'id_carga' => 'nullable|exists:combustible_cargas,id',
            'id_tractivo' => 'required|exists:tractivos,id',
            'id_tipo_lubricante' => 'nullable|exists:tipos_lubricantes,id',
            'id_causa' => 'nullable|exists:tipos_causas,id',
            'fecha' => 'required|date',
            'folio' => 'required|max:50',
            'cantidad' => 'required|numeric|min:0',
            'importe_mn' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);
        $combustiblesLubricante->update($validated);

        return redirect()->route('combustibles-lubricantes.index')->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(Request $request, CombustibleLubricante $combustiblesLubricante)
    {
        $combustiblesLubricante->delete();

        return redirect()->route('combustibles-lubricantes.index')->with('success', 'Registro eliminado correctamente.');
    }
}
