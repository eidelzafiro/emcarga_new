<?php

namespace App\Http\Controllers;

use App\Models\Aforo;
use App\Models\Cliente;
use App\Models\Prefactura;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrefacturasController extends Controller
{
    public function index(Request $request)
    {
        $prefacturas = Prefactura::with('cliente:id,nombre')
            ->when($request->search, fn ($q, $s) => $q->whereHas('cliente', fn ($q) => $q->where('nombre', 'like', "%{$s}%")))
            ->when($request->estado, fn ($q, $v) => $q->where('estado', $v))
            ->orderBy('fecha', 'desc')
            ->paginate(20);

        return Inertia::render('Prefacturas/Index', [
            'title' => 'Prefacturas',
            'prefacturas' => $prefacturas,
            'filters' => $request->only(['search', 'estado']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Prefacturas/Form', [
            'title' => 'Nueva Prefactura',
            'clientes' => Cliente::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'codigo']),
            'aforos_pendientes' => Aforo::with('cartaPorte:id,numero_carta_porte')
                ->whereNull('id_prefactura')
                ->whereNull('id_factura')
                ->orderBy('fecha_parte')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero' => 'required|unique:prefacturas,numero|max:50',
            'id_cliente' => 'required|exists:clientes,id',
            'fecha' => 'required|date',
            'flete_mt' => 'required|numeric|min:0',
            'flete_mlc' => 'required|numeric|min:0',
            'flete_demora' => 'required|numeric|min:0',
            'otros_mt' => 'required|numeric|min:0',
            'ingreso_mt' => 'required|numeric|min:0',
            'notas' => 'nullable|string',
            'aforos_ids' => 'nullable|array',
            'aforos_ids.*' => 'exists:aforos,id',
        ]);

        $validated['id_user'] = auth()->id();
        $validated['estado'] = 'pendiente';

        $prefactura = Prefactura::create($validated);

        if ($request->filled('aforos_ids')) {
            Aforo::whereIn('id', $request->aforos_ids)
                ->whereNull('id_prefactura')
                ->update(['id_prefactura' => $prefactura->id]);
        }

        return redirect()->route('prefacturas.index')->with('success', 'Prefactura creada correctamente.');
    }

    public function update(Request $request, Prefactura $prefactura)
    {
        $validated = $request->validate([
            'fecha' => 'required|date',
            'notas' => 'nullable|string',
            'estado' => 'required|in:pendiente,procesada,cancelada',
        ]);

        $prefactura->update($validated);

        return redirect()->route('prefacturas.index')->with('success', 'Prefactura actualizada correctamente.');
    }

    public function destroy(Prefactura $prefactura)
    {
        Aforo::where('id_prefactura', $prefactura->id)->update(['id_prefactura' => null]);
        $prefactura->delete();

        return redirect()->route('prefacturas.index')->with('success', 'Prefactura eliminada correctamente.');
    }
}
