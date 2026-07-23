<?php

namespace App\Http\Controllers;

use App\Models\EstadoTarjeta;
use App\Models\Tarjeta;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EstadosTarjetasController extends Controller
{
    public function index(Request $request)
    {
        $estados = EstadoTarjeta::with(['tarjeta', 'entrega', 'recibe'])
            ->when($request->id_tarjeta, fn ($q, $v) => $q->where('id_tarjeta', $v))
            ->orderBy('fecha_movimiento', 'desc')
            ->paginate(20);

        $tarjetas = Tarjeta::select('id', 'numero', 'descripcion')->orderBy('numero')->get();

        return Inertia::render('EstadosTarjetas/Index', [
            'estados' => $estados,
            'tarjetas' => $tarjetas,
            'filters' => $request->only(['id_tarjeta']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_tarjeta' => 'required|exists:tarjetas,id',
            'fecha_movimiento' => 'required|date',
            'id_entrega' => 'nullable|exists:users,id',
            'id_recibe' => 'nullable|exists:users,id',
            'saldo_mn' => 'required|numeric',
            'saldo_mlc' => 'required|numeric',
            'comprobante' => 'nullable|max:50',
            'observaciones' => 'nullable|string',
        ]);
        EstadoTarjeta::create($validated);
        return redirect()->route('estados-tarjetas.index')->with('success', 'Estado creado correctamente.');
    }

    public function update(Request $request, EstadoTarjeta $estadosTarjeta)
    {
        $validated = $request->validate([
            'id_tarjeta' => 'required|exists:tarjetas,id',
            'fecha_movimiento' => 'required|date',
            'id_entrega' => 'nullable|exists:users,id',
            'id_recibe' => 'nullable|exists:users,id',
            'saldo_mn' => 'required|numeric',
            'saldo_mlc' => 'required|numeric',
            'comprobante' => 'nullable|max:50',
            'observaciones' => 'nullable|string',
        ]);
        $estadosTarjeta->update($validated);
        return redirect()->route('estados-tarjetas.index')->with('success', 'Estado actualizado correctamente.');
    }

    public function destroy(EstadoTarjeta $estadosTarjeta)
    {
        $estadosTarjeta->delete();
        return redirect()->route('estados-tarjetas.index')->with('success', 'Estado eliminado correctamente.');
    }
}
