<?php

namespace App\Http\Controllers;

use App\Models\Aforo;
use App\Models\Cliente;
use App\Models\Entidad;
use App\Models\Factura;
use App\Models\Prefactura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PrefacturasController extends Controller
{
    public function index(Request $request)
    {
        $prefacturas = Prefactura::with('cliente:id,nombre')
            ->when($request->search, fn ($q, $s) => $q->whereHas('cliente', fn ($q) => $q->where('nombre', 'like', "%{$s}%")))
            ->when($request->estado, fn ($q, $v) => $q->where('estado', $v))
            ->when(true, function ($q) {
                $entidadId = (int) session('entidad_activa_id');
                if ($entidadId) {
                    $ids = collect(Entidad::subEntidadesIds($entidadId))
                        ->push($entidadId)
                        ->unique()
                        ->values()
                        ->all();
                    $q->whereIn('id_entidad', $ids);
                }

                return $q;
            })
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
            'siguiente_numero' => $this->siguienteNumero(),
            'aforos_pendientes' => Aforo::with('cartaPorte:id,numero')
                ->whereNull('id_prefactura')
                ->whereNull('id_factura')
                ->orderBy('fecha_parte')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero' => 'nullable|unique:prefacturas,numero|max:50',
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

        $validated['numero'] ??= $this->siguienteNumero();
        $validated['id_entidad'] = session('entidad_activa_id') ?: null;
        $validated['id_user'] = auth()->id();
        $validated['estado'] = 'pendiente';

        $prefactura = Prefactura::create($validated);

        if ($request->filled('aforos_ids')) {
            Aforo::whereIn('id', $request->aforos_ids)
                ->whereNull('id_prefactura')
                ->whereNull('id_factura')
                ->update(['id_prefactura' => $prefactura->id]);
        }

        return redirect()->route('prefacturas.index')->with('success', 'Prefactura creada correctamente.');
    }

    /**
     * Correlativo de prefactura. Prefijo por año: "PF-2026-0001".
     */
    private function siguienteNumero(?int $anio = null): string
    {
        $anio ??= (int) date('Y');
        $prefijo = "PF-{$anio}-";
        $max = Prefactura::where('numero', 'like', "{$prefijo}%")
            ->orderByDesc('numero')
            ->value('numero');

        $sec = $max ? (int) substr($max, strlen($prefijo)) + 1 : 1;

        return $prefijo.str_pad((string) $sec, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Convierte una prefactura pendiente en factura: crea la factura a partir de
     * los totales de la prefactura, vincula sus aforos (id_prefactura → id_factura)
     * y marca la prefactura como procesada. Todo en una transacción.
     */
    public function facturar(Request $request, Prefactura $prefactura)
    {
        if ($prefactura->estado !== 'pendiente') {
            return back()->withErrors(['error' => "Solo se pueden facturar prefacturas pendientes (estado actual: {$prefactura->estado})."]);
        }

        $validated = $request->validate([
            'fecha_emision' => 'nullable|date',
        ]);

        try {
            $factura = DB::transaction(function () use ($prefactura, $validated) {
                $factura = Factura::create([
                    'numero' => $this->siguienteNumeroFactura(),
                    'fecha_emision' => $validated['fecha_emision'] ?? now()->toDateString(),
                    'id_cliente' => $prefactura->id_cliente,
                    'id_entidad' => $prefactura->id_entidad,
                    'id_user' => auth()->id(),
                    'flete_mt' => $prefactura->flete_mt,
                    'flete_mlc' => $prefactura->flete_mlc,
                    'flete_demora' => $prefactura->flete_demora,
                    'otros_mt' => $prefactura->otros_mt,
                    'ingreso_mt' => $prefactura->ingreso_mt,
                    'notas' => $prefactura->notas,
                    'cancelada' => false,
                    'refacturada' => false,
                    'estado' => 'emitida',
                ]);

                Aforo::where('id_prefactura', $prefactura->id)
                    ->update(['id_factura' => $factura->id]);

                $prefactura->update(['estado' => 'procesada']);

                return $factura;
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'No se pudo facturar la prefactura: '.$e->getMessage()]);
        }

        return redirect()->route('facturas.show', $factura)
            ->with('success', "Prefactura {$prefactura->numero} facturada correctamente (Factura {$factura->numero}).");
    }

    /**
     * Correlativo de factura, mismo formato que FacturasController (año + secuencia).
     */
    private function siguienteNumeroFactura(?int $anio = null): int
    {
        $anio ??= (int) date('Y');
        $base = $anio * 100000;
        $max = Factura::where('numero', '>=', $base + 1)
            ->where('numero', '<', ($anio + 1) * 100000)
            ->max('numero');

        return $max ? $max + 1 : $base + 1;
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
