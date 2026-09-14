<?php

namespace App\Http\Controllers\Api\V1\Ingresos;

use App\Http\Controllers\Api\Concerns\ScopesEntidadApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FacturaResource;
use App\Models\Factura;
use Illuminate\Http\Request;

/**
 * Ingresos · Facturas (API móvil). Solo lectura, anclado al mes de operaciones
 * (fecha de emisión) y filtrado por entidad.
 */
class FacturaController extends Controller
{
    use ScopesEntidadApi;

    public function index(Request $request)
    {
        $fecha = $this->fechaOperaciones($request);
        $entidades = $this->entidadesPermitidas($request);

        $query = Factura::query()
            ->when(
                ! empty($entidades),
                fn ($q) => $q->whereIn('id_entidad', $entidades),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->with(['cliente:id,nombre,codigo', 'tipoIngreso:id,nombre'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = (string) $request->string('search');
                $q->where(fn ($w) => $w->where('numero', 'like', "%{$s}%")
                    ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$s}%")));
            })
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->when($request->filled('id_cliente'), fn ($q) => $q->where('id_cliente', $request->integer('id_cliente')))
            // Ancla al mes de operaciones salvo que se pase mes/ano explícitos.
            ->when(! $request->filled('mes'), fn ($q) => $q->whereMonth('fecha_emision', $fecha->month))
            ->when(! $request->filled('ano'), fn ($q) => $q->whereYear('fecha_emision', $fecha->year))
            ->when($request->filled('mes'), fn ($q) => $q->whereMonth('fecha_emision', $request->integer('mes')))
            ->when($request->filled('ano'), fn ($q) => $q->whereYear('fecha_emision', $request->integer('ano')))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha_emision', '>=', $request->date('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha_emision', '<=', $request->date('hasta')))
            ->orderByDesc('fecha_emision')
            ->orderByDesc('numero');

        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);

        return FacturaResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, Factura $factura)
    {
        $this->autorizarEntidad($request, $factura->id_entidad);

        return new FacturaResource($factura->load(['cliente:id,nombre,codigo', 'tipoIngreso:id,nombre']));
    }
}
