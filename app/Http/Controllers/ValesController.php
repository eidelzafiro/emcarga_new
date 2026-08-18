<?php

namespace App\Http\Controllers;

use App\Models\Bolsa;
use App\Models\Tractivo;
use App\Models\Vale;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ValesController extends Controller
{
    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\Vale::class);
        $vales = Vale::with(['bolsa', 'tractivo', 'detalles'])
            ->when($request->search, fn ($q, $s) => $q->where('numero', 'like', "%{$s}%")->orWhere('concepto', 'like', "%{$s}%"))
            ->orderBy('fecha_emision', 'desc')
            ->paginate(20);

        $bolsa = Bolsa::select('id', 'nombre')->orderBy('nombre')->get();
        $tractivos = Tractivo::select('id', 'codigo')->orderBy('codigo')->get();

        return Inertia::render('Vales/Index', [
            'title' => 'Vales',
            'vales' => $vales,
            'bolsa' => $bolsa,
            'tractivos' => $tractivos,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\Vale::class);
        $validated = $request->validate([
            'numero' => 'required|unique:vales,numero|max:50',
            'id_bolsa' => 'required|exists:bolsa,id',
            'id_tractivo' => 'required|exists:tractivos,id',
            'fecha_emision' => 'required|date',
            'tipo' => 'required|max:50',
            'concepto' => 'nullable|max:255',
        ]);
        Vale::create($validated);

        return redirect()->route('vales.index')->with('success', 'Vale creado correctamente.');
    }

    public function update(Request $request, Vale $vale)
    {
        
        $this->authorize('update', $vale);
        $validated = $request->validate([
            'numero' => 'required|unique:vales,numero,'.$vale->id.'|max:50',
            'id_bolsa' => 'required|exists:bolsa,id',
            'id_tractivo' => 'required|exists:tractivos,id',
            'fecha_emision' => 'required|date',
            'tipo' => 'required|max:50',
            'concepto' => 'nullable|max:255',
        ]);
        $vale->update($validated);

        return redirect()->route('vales.index')->with('success', 'Vale actualizado correctamente.');
    }

    public function destroy(Vale $vale)
    {
        
        $this->authorize('delete', $vale);
        $vale->delete();

        return redirect()->route('vales.index')->with('success', 'Vale eliminado correctamente.');
    }
}
