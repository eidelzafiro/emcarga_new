<?php

namespace App\Http\Controllers;

use App\Models\Aforo;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\TipoIngreso;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FacturasController extends Controller
{
    public function index(Request $request)
    {
        $facturas = Factura::with('cliente:id,nombre', 'tipoIngreso:id,nombre')
            ->when($request->search, fn ($q, $s) => $q->whereHas('cliente', fn ($q) => $q->where('nombre', 'like', "%{$s}%"))->orWhere('numero', 'like', "%{$s}%"))
            ->when($request->estado, fn ($q, $v) => $q->where('estado', $v))
            ->when(true, function ($q) {
                $entidadId = (int) session('entidad_activa_id');
                if ($entidadId) {
                    $q->where('id_entidad', $entidadId);
                }
                return $q;
            })
            ->orderBy('fecha_emision', 'desc')
            ->orderBy('numero', 'desc')
            ->paginate(20);

        return Inertia::render('Facturas/Index', [
            'title' => 'Facturas',
            'facturas' => $facturas,
            'filters' => $request->only(['search', 'estado']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Facturas/Form', [
            'title' => 'Nueva Factura',
            'clientes' => Cliente::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'codigo']),
            'tipos_ingreso' => TipoIngreso::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'siglas']),
            'aforos_pendientes' => Aforo::with('cartaPorte:id,numero_carta_porte', 'cartaPorte.cliente:id,nombre')
                ->whereNull('id_factura')
                ->where('ingreso_mt', '>', 0)
                ->orderBy('fecha_parte')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero' => 'required|unique:facturas,numero|integer',
            'fecha_emision' => 'required|date',
            'id_cliente' => 'required|exists:clientes,id',
            'flete_mt' => 'required|numeric|min:0',
            'flete_mlc' => 'required|numeric|min:0',
            'flete_demora' => 'required|numeric|min:0',
            'otros_mt' => 'required|numeric|min:0',
            'ingreso_mt' => 'required|numeric|min:0',
            'oventas' => 'boolean',
            'id_tipo_ingreso' => 'nullable|exists:tipo_ingresos,id',
            'notas' => 'nullable|string',
            'aforos_ids' => 'nullable|array',
            'aforos_ids.*' => 'exists:aforos,id',
        ]);

        $validated['id_entidad'] = (int) session('entidad_activa_id');
        $validated['id_user'] = auth()->id();
        $validated['id_unidad'] = auth()->user()->id_unidad ?? null;
        $validated['cancelada'] = false;
        $validated['refacturada'] = false;
        $validated['estado'] = 'emitida';

        $factura = Factura::create($validated);

        if ($request->filled('aforos_ids')) {
            Aforo::whereIn('id', $request->aforos_ids)
                ->whereNull('id_factura')
                ->update(['id_factura' => $factura->id]);
        }

        return redirect()->route('facturas.index')->with('success', 'Factura creada correctamente.');
    }

    public function show(Factura $factura)
    {
        $factura->load('cliente', 'tipoIngreso', 'aforos.cartaPorte', 'user');

        return Inertia::render('Facturas/Show', [
            'title' => "Factura {$factura->numero}",
            'factura' => $factura,
        ]);
    }

    public function update(Request $request, Factura $factura)
    {
        $validated = $request->validate([
            'fecha_firma' => 'nullable|date',
            'fecha_cobro_mn' => 'nullable|date',
            'fecha_cobro_mlc' => 'nullable|date',
            'fecha_conciliacion' => 'nullable|date',
            'factura_cliente' => 'nullable|max:100',
            'doc_pago_mn' => 'nullable|max:100',
            'notas' => 'nullable|string',
        ]);

        $factura->update($validated);

        return redirect()->route('facturas.index')->with('success', 'Factura actualizada correctamente.');
    }

    public function destroy(Factura $factura)
    {
        Aforo::where('id_factura', $factura->id)->update(['id_factura' => null]);
        $factura->delete();

        return redirect()->route('facturas.index')->with('success', 'Factura eliminada correctamente.');
    }

    public function cancelar(Factura $factura)
    {
        $factura->update([
            'cancelada' => true,
            'estado' => 'cancelada',
            'flete_mt' => 0,
            'flete_mlc' => 0,
            'flete_demora' => 0,
            'otros_mt' => 0,
            'ingreso_mt' => 0,
        ]);

        if (! $factura->oventas) {
            Aforo::where('id_factura', $factura->id)->update(['id_factura' => null]);
        }

        return redirect()->route('facturas.index')->with('success', 'Factura cancelada correctamente.');
    }

    public function refacturar(Factura $factura)
    {
        $factura->update(['refacturada' => true, 'estado' => 'refacturada']);

        Aforo::where('id_factura', $factura->id)->update([
            'id_factura' => null,
            'refactura' => true,
        ]);

        return redirect()->route('facturas.index')->with('success', 'Factura refacturada correctamente.');
    }

    public function firmar(Factura $factura)
    {
        $factura->update(['fecha_firma' => now()]);

        return redirect()->route('facturas.index')->with('success', 'Factura marcada como firmada.');
    }

    public function cobrar(Request $request, Factura $factura)
    {
        $validated = $request->validate([
            'fecha_cobro_mn' => 'nullable|date',
            'fecha_cobro_mlc' => 'nullable|date',
            'doc_pago_mn' => 'nullable|max:100',
        ]);

        $factura->update($validated);

        return redirect()->route('facturas.index')->with('success', 'Factura marcada como cobrada.');
    }

    public function aforosPendientes(Request $request)
    {
        $query = Aforo::with('cartaPorte.cliente:id,nombre')
            ->whereNull('id_factura')
            ->where('ingreso_mt', '>', 0);

        if ($request->id_cliente) {
            $query->whereHas('cartaPorte', fn ($q) => $q->where('id_cliente', $request->id_cliente));
        }

        return response()->json($query->orderBy('fecha_parte')->get());
    }
}
