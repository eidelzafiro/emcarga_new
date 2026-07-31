<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Demanda;
use App\Models\Embalaje;
use App\Models\Lugare;
use App\Models\Producto;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DemandasController extends Controller
{
    public function index(Request $request)
    {
        $items = Demanda::with(['cliente', 'producto', 'origen', 'destino', 'embalaje'])
            ->when($request->search, fn ($q, $s) => $q->where('observaciones', 'like', "%{$s}%"))
            ->when($request->id_cliente, fn ($q, $v) => $q->where('id_cliente', $v))
            ->orderBy('fecha_demanda', 'desc')
            ->paginate(20);

        $clientes = Cliente::select('id', 'nombre')->orderBy('nombre')->get();
        $productos = Producto::select('id', 'nombre')->orderBy('nombre')->get();
        $lugares = Lugare::select('id', 'nombre')->orderBy('nombre')->get();
        $embalajes = Embalaje::select('id', 'nombre')->orderBy('nombre')->get();

        return Inertia::render('Demandas/Index', [
            'title' => 'Demandas',
            'items' => $items,
            'clientes' => $clientes,
            'productos' => $productos,
            'lugares' => $lugares,
            'embalajes' => $embalajes,
            'filters' => $request->only(['search', 'id_cliente']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha_demanda' => 'required|date',
            'id_cliente' => 'required|exists:clientes,id',
            'id_producto' => 'required|exists:productos,id',
            'id_origen' => 'required|exists:lugares,id',
            'id_destino' => 'required|exists:lugares,id',
            'id_embalaje' => 'required|exists:embalajes,id',
            'viajes' => 'required|integer|min:0',
            'kms_totales' => 'required|numeric|min:0',
            'kms_carga' => 'required|numeric|min:0',
            'tiempo_demanda' => 'required|numeric|min:0',
            'tiempo_aceptacion' => 'required|numeric|min:0',
            'datos_mensuales' => 'nullable|json',
            'observaciones' => 'nullable|string',
            'estado' => 'required|in:activa,completada,cancelada',
        ]);
        $validated['id_user'] = auth()->id();
        Demanda::create($validated);

        return redirect()->route('demandas.index')->with('success', 'Demanda creada correctamente.');
    }

    public function update(Request $request, Demanda $demanda)
    {
        $validated = $request->validate([
            'fecha_demanda' => 'required|date',
            'id_cliente' => 'required|exists:clientes,id',
            'id_producto' => 'required|exists:productos,id',
            'id_origen' => 'required|exists:lugares,id',
            'id_destino' => 'required|exists:lugares,id',
            'id_embalaje' => 'required|exists:embalajes,id',
            'viajes' => 'required|integer|min:0',
            'kms_totales' => 'required|numeric|min:0',
            'kms_carga' => 'required|numeric|min:0',
            'tiempo_demanda' => 'required|numeric|min:0',
            'tiempo_aceptacion' => 'required|numeric|min:0',
            'datos_mensuales' => 'nullable|json',
            'observaciones' => 'nullable|string',
            'estado' => 'required|in:activa,completada,cancelada',
        ]);
        $demanda->update($validated);

        return redirect()->route('demandas.index')->with('success', 'Demanda actualizada correctamente.');
    }

    public function destroy(Demanda $demanda)
    {
        $demanda->delete();

        return redirect()->route('demandas.index')->with('success', 'Demanda eliminada correctamente.');
    }
}
