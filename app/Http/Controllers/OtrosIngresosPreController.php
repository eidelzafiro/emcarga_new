<?php

namespace App\Http\Controllers;

use App\Models\CartaPorte;
use App\Models\OtrosIngresosPre;
use App\Models\TipoIngreso;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OtrosIngresosPreController extends Controller
{
    public function index(Request $request)
    {
        $items = OtrosIngresosPre::with(['cartaPorte', 'tipoIngreso'])
            ->when($request->id_carta_porte, fn ($q, $v) => $q->where('id_carta_porte', $v))
            ->orderBy('id', 'desc')
            ->paginate(20);

        $cartasPorte = CartaPorte::select('id', 'numero')->orderBy('numero')->get();
        $tiposIngreso = TipoIngreso::select('id', 'nombre')->orderBy('nombre')->get();

        return Inertia::render('OtrosIngresosPre/Index', [
            'title' => 'Otros Ingresos',
            'items' => $items,
            'cartasPorte' => $cartasPorte,
            'tiposIngreso' => $tiposIngreso,
            'filters' => $request->only(['id_carta_porte']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_carta_porte' => 'required|exists:cartas_porte,id',
            'id_tipo_ingreso' => 'required|exists:tipo_ingresos,id',
            'cantidad' => 'required|integer|min:0',
            'importe_mn' => 'required|numeric|min:0',
        ]);
        OtrosIngresosPre::create($validated);

        return redirect()->route('otros-ingresos-pre.index')->with('success', 'Ingreso creado correctamente.');
    }

    public function update(Request $request, OtrosIngresosPre $otrosIngresosPre)
    {
        $validated = $request->validate([
            'id_carta_porte' => 'required|exists:cartas_porte,id',
            'id_tipo_ingreso' => 'required|exists:tipo_ingresos,id',
            'cantidad' => 'required|integer|min:0',
            'importe_mn' => 'required|numeric|min:0',
        ]);
        $otrosIngresosPre->update($validated);

        return redirect()->route('otros-ingresos-pre.index')->with('success', 'Ingreso actualizado correctamente.');
    }

    public function destroy(OtrosIngresosPre $otrosIngresosPre)
    {
        $otrosIngresosPre->delete();

        return redirect()->route('otros-ingresos-pre.index')->with('success', 'Ingreso eliminado correctamente.');
    }
}
