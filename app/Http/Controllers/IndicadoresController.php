<?php

namespace App\Http\Controllers;

use App\Models\Indicadore;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IndicadoresController extends Controller
{
    public function index(Request $request)
    {
        $items = Indicadore::with('cartaPorte')
            ->orderBy('id_carta_porte', 'desc')
            ->paginate(20);

        return Inertia::render('Indicadores/Index', [
            'title' => 'Indicadores',
            'items' => $items,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_carta_porte' => 'required|exists:cartas_porte,id|unique:indicadores,id_carta_porte',
        ]);
        Indicadore::create($validated);

        return redirect()->route('indicadores.index')->with('success', 'Indicadores creados correctamente.');
    }

    public function update(Request $request, Indicadore $indicadore)
    {
        $validated = $request->validate([]);
        $indicadore->update($request->all());

        return redirect()->route('indicadores.index')->with('success', 'Indicadores actualizados correctamente.');
    }

    public function destroy(Indicadore $indicadore)
    {
        $indicadore->delete();

        return redirect()->route('indicadores.index')->with('success', 'Indicadores eliminados correctamente.');
    }
}
