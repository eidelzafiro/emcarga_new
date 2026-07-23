<?php

namespace App\Http\Controllers;

use App\Models\TipoPagoAdicionale;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TiposPagosAdicionalesController extends Controller
{
    public function index(Request $request)
    {
        $tipos = TipoPagoAdicionale::when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%")->orWhere('codigo', 'like', "%{$s}%"))
            ->orderBy('nombre')
            ->paginate(20);

        return Inertia::render('TiposPagosAdicionales/Index', [
            'title' => 'Tipos de Pagos Adicionales',
            'tipos' => $tipos,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:tipos_pagos_adicionales,codigo|max:50',
            'nombre' => 'required|max:255',
        ]);
        TipoPagoAdicionale::create($validated);

        return redirect()->route('tipos-pagos-adicionales.index')->with('success', 'Tipo de pago adicional creado correctamente.');
    }

    public function update(Request $request, TipoPagoAdicionale $tiposPagoAdicionale)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:tipos_pagos_adicionales,codigo,'.$tiposPagoAdicionale->id.'|max:50',
            'nombre' => 'required|max:255',
        ]);
        $tiposPagoAdicionale->update($validated);

        return redirect()->route('tipos-pagos-adicionales.index')->with('success', 'Tipo de pago adicional actualizado correctamente.');
    }

    public function destroy(TipoPagoAdicionale $tiposPagoAdicionale)
    {
        $tiposPagoAdicionale->delete();

        return redirect()->route('tipos-pagos-adicionales.index')->with('success', 'Tipo de pago adicional eliminado correctamente.');
    }
}
