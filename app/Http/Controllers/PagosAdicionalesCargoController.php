<?php

namespace App\Http\Controllers;

use App\Models\PagosAdicionalesCargo;
use App\Models\Cargo;
use App\Models\TipoPagoAdicionale;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PagosAdicionalesCargoController extends Controller
{
    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\PagosAdicionalesCargo::class);
        $items = PagosAdicionalesCargo::query()
            ->orderBy('id')->paginate(50);

        return Inertia::render('PagosAdicionalesCargo/Index', [
            'items' => $items,
            'title' => 'Pagos Adicionales de Cargo',
            'cargos' => Cargo::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'tiposPago' => TipoPagoAdicionale::orderBy('nombre')->get(['id', 'nombre']),
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        
        $this->authorize('create', \App\Models\PagosAdicionalesCargo::class);return redirect()->route('pagos-adicionales-cargo.index');
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\PagosAdicionalesCargo::class);
        $validated = $request->validate([
            'id_cargo' => 'required|exists:cargos,id',
            'id_tipo_pago_adicional' => 'required|exists:tipos_pagos_adicionales,id',
            'monto' => 'required|numeric|min:0',
        ]);

        PagosAdicionalesCargo::create($validated);

        return redirect()->route('pagos-adicionales-cargo.index')->with('success', 'Pago adicional de cargo creado.');
    }

    public function show($id)
    {
        return redirect()->route('pagos-adicionales-cargo.index');
    }

    public function edit($id)
    {
        return redirect()->route('pagos-adicionales-cargo.index');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_cargo' => 'required|exists:cargos,id',
            'id_tipo_pago_adicional' => 'required|exists:tipos_pagos_adicionales,id',
            'monto' => 'required|numeric|min:0',
        ]);

        $item = PagosAdicionalesCargo::findOrFail($id);
        $item->update($validated);

        return redirect()->route('pagos-adicionales-cargo.index')->with('success', 'Pago adicional de cargo actualizado.');
    }

    public function destroy($id)
    {
        $item = PagosAdicionalesCargo::findOrFail($id);
        $item->delete();

        return redirect()->route('pagos-adicionales-cargo.index')->with('success', 'Pago adicional de cargo eliminado.');
    }
}
