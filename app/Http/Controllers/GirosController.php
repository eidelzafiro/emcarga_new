<?php

namespace App\Http\Controllers;

use App\Models\CartaPorte;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GirosController extends Controller
{
    public function index(Request $request)
    {
        $cartas = CartaPorte::with([
            'cliente:id,nombre',
            'solicitud:id,nombre',
            'tractivo:id,codigo',
            'chofer:id,nombre',
        ])
            ->when($request->search, fn ($q, $s) => $q->where('numero', 'like', "%{$s}%"))
            ->orderBy('fecha_emision', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Giros/Index', [
            'title' => 'Cartas Porte',
            'cartas' => $cartas,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero' => ['required', 'string', 'max:20', 'unique:cartas_porte,numero'],
            'fecha_emision' => ['nullable', 'date'],
            'fecha_parte' => ['nullable', 'date'],
            'peso1' => ['nullable', 'numeric', 'min:0'],
            'peso2' => ['nullable', 'numeric', 'min:0'],
            'toneladas' => ['nullable', 'numeric', 'min:0'],
            'ingreso_mt' => ['nullable', 'numeric', 'min:0'],
            'flete_mt' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['nullable', 'string', 'in:emitida,recepcionada,facturada,cancelada'],
        ]);

        $validated['id_user'] = auth()->id();
        $validated['estado'] = $validated['estado'] ?? 'emitida';
        $validated['fecha_emision'] = $validated['fecha_emision'] ?? now()->toDateString();
        $validated['fecha_parte'] = $validated['fecha_parte'] ?? $validated['fecha_emision'];

        $carta = CartaPorte::create($validated);

        return back()->with('success', "Carta de porte {$carta->numero} creada.");
    }

    public function update(Request $request, CartaPorte $giro)
    {
        $validated = $request->validate([
            'numero' => ['required', 'string', 'max:20', 'unique:cartas_porte,numero,'.$giro->id],
            'fecha_emision' => ['nullable', 'date'],
            'fecha_parte' => ['nullable', 'date'],
            'peso1' => ['nullable', 'numeric', 'min:0'],
            'peso2' => ['nullable', 'numeric', 'min:0'],
            'toneladas' => ['nullable', 'numeric', 'min:0'],
            'ingreso_mt' => ['nullable', 'numeric', 'min:0'],
            'flete_mt' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['nullable', 'string', 'in:emitida,recepcionada,facturada,cancelada'],
        ]);

        $giro->update($validated);

        return back()->with('success', "Carta de porte {$giro->numero} actualizada.");
    }

    public function destroy(CartaPorte $giro)
    {
        $numero = $giro->numero;
        $giro->delete();

        return back()->with('success', "Carta de porte {$numero} eliminada.");
    }
}