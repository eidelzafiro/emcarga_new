<?php

namespace App\Http\Controllers;

use App\Models\Bolsa;
use App\Models\CombustibleCarga;
use App\Models\DetalleCargaCombustible;
use App\Models\Tractivo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DetallesCargaCombustibleController extends Controller
{
    public function index(Request $request)
    {
        $detalles = DetalleCargaCombustible::with(['carga', 'tractivo', 'bolsa'])
            ->when($request->id_carga, fn ($q, $v) => $q->where('id_carga', $v))
            ->orderBy('fecha_movimiento', 'desc')
            ->paginate(20);

        $cargas = CombustibleCarga::select('id', 'numero')->orderBy('numero')->get();
        $tractivos = Tractivo::select('id', 'codigo')->orderBy('codigo')->get();
        $bolsas = Bolsa::select('id', 'nombre')->orderBy('nombre')->get();

        return Inertia::render('DetallesCargaCombustible/Index', [
            'detalles' => $detalles,
            'cargas' => $cargas,
            'tractivos' => $tractivos,
            'bolsas' => $bolsas,
            'filters' => $request->only(['id_carga']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_carga' => 'required|exists:combustible_cargas,id',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'id_bolsa' => 'nullable|exists:bolsa,id',
            'fecha_movimiento' => 'required|date',
            'comprobante' => 'nullable|max:50',
            'importe_mn' => 'required|numeric|min:0',
            'importe_mlc' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);
        DetalleCargaCombustible::create($validated);

        return redirect()->route('detalles-carga-combustible.index')->with('success', 'Detalle creado correctamente.');
    }

    public function update(Request $request, DetalleCargaCombustible $detallesCargaCombustible)
    {
        $validated = $request->validate([
            'id_carga' => 'required|exists:combustible_cargas,id',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'id_bolsa' => 'nullable|exists:bolsa,id',
            'fecha_movimiento' => 'required|date',
            'comprobante' => 'nullable|max:50',
            'importe_mn' => 'required|numeric|min:0',
            'importe_mlc' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);
        $detallesCargaCombustible->update($validated);

        return redirect()->route('detalles-carga-combustible.index')->with('success', 'Detalle actualizado correctamente.');
    }

    public function destroy(DetalleCargaCombustible $detallesCargaCombustible)
    {
        $detallesCargaCombustible->delete();

        return redirect()->route('detalles-carga-combustible.index')->with('success', 'Detalle eliminado correctamente.');
    }
}
