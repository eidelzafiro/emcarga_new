<?php

namespace App\Http\Controllers;

use App\Models\Aforo;
use App\Models\Cliente;
use App\Models\Entidad;
use App\Models\Factura;
use App\Models\TipoIngreso;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FacturasController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\Factura::class);
        $facturas = Factura::with('cliente:id,nombre', 'tipoIngreso:id,nombre')
            ->when($request->search, fn ($q, $s) => $q->whereHas('cliente', fn ($q) => $q->where('nombre', 'like', "%{$s}%"))->orWhere('numero', 'like', "%{$s}%"))
            ->when($request->estado, fn ($q, $v) => $q->where('estado', $v))
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
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
        
        $this->authorize('create', \App\Models\Factura::class);
        $aforosPendientes = Aforo::with('cartaPorte:id,numero,id_solicitud', 'cartaPorte.cliente', 'cartaPorte.solicitud:id,id_cliente')
            ->whereNull('id_factura')
            ->whereNull('id_prefactura')
            ->where('ingreso_mt', '>', 0)
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereHas('cartaPorte', fn ($c) => $this->whereCartaEntidad($c)))
            ->orderBy('fecha_parte')
            ->get();

        // Clientes que tienen CP aforadas pendientes de facturar (de la entidad activa).
        $idsClientes = $aforosPendientes
            ->map(fn ($a) => $a->cartaPorte?->solicitud?->id_cliente ?? $a->cartaPorte?->cliente?->id)
            ->filter()
            ->unique();

        return Inertia::render('Facturas/Form', [
            'title' => 'Nueva Factura',
            'clientes' => Cliente::where('activo', true)
                ->whereIn('id', $idsClientes)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'codigo']),
            'tipos_ingreso' => TipoIngreso::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'siglas']),
            'siguiente_numero' => $this->siguienteNumero(),
            'aforos_pendientes' => $aforosPendientes,
            'fechaOperaciones' => session('fecha_operaciones') ?? now()->toDateString(),
        ]);
    }

    /**
     * Restringe una consulta de cartas de porte a las entidades permitidas
     * (entidad de la CP vía hoja de ruta / tractivo / solicitud).
     */
    private function whereCartaEntidad($q): void
    {
        $ids = $this->entidadesPermitidas();
        $q->where(function ($w) use ($ids) {
            $w->whereHas('hojaRuta', fn ($h) => $h->whereIn('id_entidad', $ids))
                ->orWhereHas('hojaRuta.tractivo', fn ($t) => $t->whereIn('id_entidad', $ids))
                ->orWhereHas('solicitud', fn ($s) => $s->whereIn('id_entidad', $ids));
        });
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\Factura::class);
        $validated = $request->validate([
            'numero' => 'nullable|unique:facturas,numero|integer',
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

        $validated['numero'] ??= $this->siguienteNumero();
        $validated['id_entidad'] = entidadActivaId() ?: null;
        $validated['id_user'] = auth()->id();
        $validated['cancelada'] = false;
        $validated['refacturada'] = false;
        $validated['estado'] = 'emitida';

        $factura = Factura::create($validated);

        if ($request->filled('aforos_ids')) {
            Aforo::whereIn('id', $request->aforos_ids)
                ->whereNull('id_factura')
                ->whereNull('id_prefactura')
                ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereHas('cartaPorte.hojaRuta.tractivo', fn ($t) => $t->whereIn('id_entidad', $this->entidadesPermitidas())))
                ->update(['id_factura' => $factura->id]);
        }

        return redirect()->route('facturas.index')->with('success', 'Factura creada correctamente.');
    }

    private function siguienteNumero(?int $anio = null): int
    {
        $anio ??= (int) date('Y');
        $base = $anio * 100000;
        $max = Factura::where('numero', '>=', $base + 1)
            ->where('numero', '<', ($anio + 1) * 100000)
            ->max('numero');

        return $max ? $max + 1 : $base + 1;
    }

    public function show(Factura $factura)
    {
        
        $this->authorize('view', $factura);
        $this->autorizarEntidad($factura->id_entidad);

        $factura->load('cliente', 'tipoIngreso', 'aforos.cartaPorte', 'user', 'pagos.moneda');

        return Inertia::render('Facturas/Show', [
            'title' => "Factura {$factura->numero}",
            'factura' => $factura,
        ]);
    }

    public function update(Request $request, Factura $factura)
    {
        
        $this->authorize('update', $factura);
        $this->autorizarEntidad($factura->id_entidad);

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
        
        $this->authorize('delete', $factura);
        $this->autorizarEntidad($factura->id_entidad);

        Aforo::where('id_factura', $factura->id)->update(['id_factura' => null]);
        $factura->delete();

        return redirect()->route('facturas.index')->with('success', 'Factura eliminada correctamente.');
    }

    public function cancelar(Factura $factura)
    {
        $this->autorizarEntidad($factura->id_entidad);

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
        $this->autorizarEntidad($factura->id_entidad);

        $factura->update(['refacturada' => true, 'estado' => 'refacturada']);

        Aforo::where('id_factura', $factura->id)->update([
            'id_factura' => null,
            'refactura' => true,
        ]);

        return redirect()->route('facturas.index')->with('success', 'Factura refacturada correctamente.');
    }

    public function firmar(Factura $factura)
    {
        $this->autorizarEntidad($factura->id_entidad);

        $factura->update([
            'fecha_firma' => now(),
            'estado' => 'firmada',
        ]);

        return redirect()->route('facturas.index')->with('success', 'Factura marcada como firmada.');
    }

    public function cobrar(Request $request, Factura $factura)
    {
        $this->autorizarEntidad($factura->id_entidad);

        $validated = $request->validate([
            'fecha_cobro_mn' => 'nullable|date',
            'fecha_cobro_mlc' => 'nullable|date',
            'doc_pago_mn' => 'nullable|max:100',
        ]);

        if (! $request->has('fecha_cobro_mn') && ! $request->has('fecha_cobro_mlc') && ! $request->has('doc_pago_mn')) {
            $validated['fecha_cobro_mn'] = now()->toDateString();
        }

        $validated['estado'] = 'cobrada';

        $factura->update($validated);

        return redirect()->route('facturas.index')->with('success', 'Factura marcada como cobrada.');
    }

    public function aforosPendientes(Request $request)
    {
        $query = Aforo::with('cartaPorte.cliente')
            ->whereNull('id_factura')
            ->whereNull('id_prefactura')
            ->where('ingreso_mt', '>', 0)
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereHas('cartaPorte.hojaRuta.tractivo', fn ($t) => $t->whereIn('id_entidad', $this->entidadesPermitidas())));

        if ($request->id_cliente) {
            $query->whereHas('cartaPorte.solicitud', fn ($q) => $q->where('id_cliente', $request->id_cliente));
        }

        return response()->json($query->orderBy('fecha_parte')->get());
    }

    public function exportar(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Factura::class);

        $ids = array_filter((array) $request->input('ids', []), fn ($v) => $v !== null && $v !== '');

        $facturas = Factura::with('cliente:id,nombre', 'entidad:id,nombre,abreviatura,talon_versat')
            ->when(! empty($ids), fn ($q) => $q->whereIn('id', $ids))
            ->when(empty($ids) && $request->search, fn ($q, $s) => $q->whereHas('cliente', fn ($q) => $q->where('nombre', 'like', "%{$s}%"))->orWhere('numero', 'like', "%{$s}%"))
            ->when(empty($ids) && $request->estado, fn ($q, $v) => $q->where('estado', $v))
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
            ->orderBy('fecha_emision', 'desc')
            ->orderBy('numero', 'desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="facturas_' . date('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($facturas) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, ['No. Factura', 'Fecha Emisión', 'Cliente', 'Entidad', 'Talón Versat', 'Flete MT', 'Flete MLC', 'Demora', 'Otros MT', 'Ingreso MT', 'Estado', 'Firma', 'Cobro MN', 'Conciliación']);

            foreach ($facturas as $f) {
                fputcsv($handle, [
                    $f->numero,
                    optional($f->fecha_emision)?->format('d/m/Y'),
                    optional($f->cliente)->nombre ?? '',
                    optional($f->entidad)->abreviatura ?? '',
                    optional($f->entidad)->talon_versat ?? '',
                    number_format((float) $f->flete_mt, 2, '.', ''),
                    number_format((float) $f->flete_mlc, 2, '.', ''),
                    number_format((float) $f->flete_demora, 2, '.', ''),
                    number_format((float) $f->otros_mt, 2, '.', ''),
                    number_format((float) $f->ingreso_mt, 2, '.', ''),
                    $f->estado,
                    optional($f->fecha_firma)?->format('d/m/Y') ?? '',
                    optional($f->fecha_cobro_mn)?->format('d/m/Y') ?? '',
                    optional($f->fecha_conciliacion)?->format('d/m/Y') ?? '',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
