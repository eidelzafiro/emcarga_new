<?php

namespace App\Http\Controllers;

use App\Models\TipoSistemaPago;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TiposSistemasPagoController extends Controller
{
    public function index(Request $request)
    {
        $tipos = TipoSistemaPago::when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%")->orWhere('codigo', 'like', "%{$s}%"))
            ->orderBy('nombre')
            ->paginate(20);

        return Inertia::render('TiposSistemasPago/Index', [
            'title' => 'Tipos de Sistemas de Pago',
            'tipos' => $tipos,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:tipos_sistemas_pago,codigo|max:50',
            'nombre' => 'required|max:255',
        ]);
        TipoSistemaPago::create($validated);

        return redirect()->route('tipos-sistemas-pago.index')->with('success', 'Tipo de sistema de pago creado correctamente.');
    }

    public function update(Request $request, TipoSistemaPago $tiposSistemaPago)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:tipos_sistemas_pago,codigo,'.$tiposSistemaPago->id.'|max:50',
            'nombre' => 'required|max:255',
        ]);
        $tiposSistemaPago->update($validated);

        return redirect()->route('tipos-sistemas-pago.index')->with('success', 'Tipo de sistema de pago actualizado correctamente.');
    }

    public function destroy(TipoSistemaPago $tiposSistemaPago)
    {
        $tiposSistemaPago->delete();

        return redirect()->route('tipos-sistemas-pago.index')->with('success', 'Tipo de sistema de pago eliminado correctamente.');
    }
}
