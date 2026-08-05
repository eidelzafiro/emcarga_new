<?php

namespace App\Http\Controllers;

use App\Models\Distancia;
use App\Models\Lugare;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DistanciasController extends Controller
{
    public function index(Request $request)
    {
        $distancias = Distancia::with('origen:id,nombre', 'destino:id,nombre')
            ->when($request->search, function ($q, $s) {
                $q->whereHas('origen', fn ($c) => $c->where('nombre', 'like', "%{$s}%"))
                    ->orWhereHas('destino', fn ($c) => $c->where('nombre', 'like', "%{$s}%"));
            })
            ->paginate(20);

        return Inertia::render('Distancias/Index', [
            'title' => 'Distancias',
            'distancias' => $distancias,
            'lugares' => Lugare::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);
        Distancia::create($validated);

        return redirect()->route('distancias.index')->with('success', 'Distancia creada correctamente.');
    }

    public function update(Request $request, Distancia $distancia)
    {
        $validated = $this->validar($request);
        $distancia->update($validated);

        return redirect()->route('distancias.index')->with('success', 'Distancia actualizada correctamente.');
    }

    public function destroy(Distancia $distancia)
    {
        $distancia->delete();

        return redirect()->route('distancias.index')->with('success', 'Distancia eliminada correctamente.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'id_lugar_origen' => 'required|exists:lugares,id|different:id_lugar_destino',
            'id_lugar_destino' => 'required|exists:lugares,id',
            'distancia_km' => 'required|numeric|min:0',
            'activo' => 'sometimes|boolean',
        ]);
    }
}
