<?php

namespace App\Http\Controllers;

use App\Models\EstadoTarjeta;
use App\Models\Tarjeta;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EstadosTarjetasController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', EstadoTarjeta::class);

        $estados = EstadoTarjeta::with(['tarjeta', 'entrega', 'recibe'])
            ->when($request->id_tarjeta, fn ($q, $v) => $q->where('id_tarjeta', $v))
            ->orderBy('fecha_movimiento', 'desc')
            ->paginate(20);

        $tarjetas = Tarjeta::select('id', 'numero', 'descripcion')->orderBy('numero')->get();
        $users = User::select('id', 'name', 'username')->orderBy('name')->get();

        return Inertia::render('EstadosTarjetas/Index', [
            'title' => 'Mov. Tarjetas Combustible',
            'estados' => $estados,
            'tarjetas' => $tarjetas,
            'users' => $users,
            'filters' => $request->only(['id_tarjeta']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', EstadoTarjeta::class);

        $validated = $request->validate([
            'id_tarjeta' => 'required|exists:tarjetas,id',
            'fecha_movimiento' => 'required|date',
            'id_entrega' => 'nullable|exists:users,id',
            'id_recibe' => 'nullable|exists:users,id',
            'observaciones' => 'nullable|string',
        ]);

        EstadoTarjeta::create($validated);

        return redirect()->route('estados-tarjetas.index')->with('success', 'Movimiento registrado correctamente.');
    }

    public function update(Request $request, EstadoTarjeta $estadosTarjeta)
    {
        $this->authorize('update', $estadosTarjeta);

        $validated = $request->validate([
            'id_tarjeta' => 'required|exists:tarjetas,id',
            'fecha_movimiento' => 'required|date',
            'id_entrega' => 'nullable|exists:users,id',
            'id_recibe' => 'nullable|exists:users,id',
            'observaciones' => 'nullable|string',
        ]);

        $estadosTarjeta->update($validated);

        return redirect()->route('estados-tarjetas.index')->with('success', 'Movimiento actualizado correctamente.');
    }

    public function destroy(EstadoTarjeta $estadosTarjeta)
    {
        $this->authorize('delete', $estadosTarjeta);
        $estadosTarjeta->delete();

        return redirect()->route('estados-tarjetas.index')->with('success', 'Movimiento eliminado correctamente.');
    }
}
