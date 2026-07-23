<?php

namespace App\Http\Controllers;

use App\Models\PizarraTractivo;
use App\Models\Tractivo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PizarraTractivosController extends Controller
{
    public function index(Request $request)
    {
        $items = PizarraTractivo::with('tractivo')
            ->when($request->mes, fn ($q, $v) => $q->where('mes', $v))
            ->when($request->ano, fn ($q, $v) => $q->where('ano', $v))
            ->orderBy('ano', 'desc')
            ->orderBy('mes', 'desc')
            ->paginate(20);

        $tractivos = Tractivo::select('id', 'codigo')->orderBy('codigo')->get();

        return Inertia::render('PizarraTractivos/Index', [
            'items' => $items,
            'tractivos' => $tractivos,
            'filters' => $request->only(['mes', 'ano']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mes' => 'required|integer|min:1|max:12',
            'ano' => 'required|integer|min:2000',
            'id_tractivo' => 'required|exists:tractivos,id',
            'dias' => 'nullable|array',
        ]);
        PizarraTractivo::create($validated);
        return redirect()->route('pizarra-tractivos.index')->with('success', 'Registro creado correctamente.');
    }

    public function update(Request $request, PizarraTractivo $pizarraTractivo)
    {
        $validated = $request->validate([
            'mes' => 'required|integer|min:1|max:12',
            'ano' => 'required|integer|min:2000',
            'id_tractivo' => 'required|exists:tractivos,id',
            'dias' => 'nullable|array',
        ]);
        $pizarraTractivo->update($validated);
        return redirect()->route('pizarra-tractivos.index')->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(PizarraTractivo $pizarraTractivo)
    {
        $pizarraTractivo->delete();
        return redirect()->route('pizarra-tractivos.index')->with('success', 'Registro eliminado correctamente.');
    }
}
