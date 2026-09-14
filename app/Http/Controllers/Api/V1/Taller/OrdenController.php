<?php

namespace App\Http\Controllers\Api\V1\Taller;

use App\Http\Controllers\Api\Concerns\ScopesEntidadApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrdenTallerResource;
use App\Models\OrdenesTaller;
use Illuminate\Http\Request;

/**
 * Taller · Órdenes de taller (API móvil). Solo lectura, filtrado por entidad.
 * Por defecto devuelve las abiertas (todas) más las cerradas del mes de
 * operaciones; si se filtra por estado, se respeta.
 */
class OrdenController extends Controller
{
    use ScopesEntidadApi;

    public function index(Request $request)
    {
        $fecha = $this->fechaOperaciones($request);
        $entidades = $this->entidadesPermitidas($request);

        $query = OrdenesTaller::query()
            ->when(
                ! empty($entidades),
                fn ($q) => $q->whereIn('id_entidad', $entidades),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->with(['tractivo:id,codigo,placa', 'tipoMantenimiento:id,nombre', 'motivoEntrada:id,nombre', 'clasificacion:id,nombre'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = (string) $request->string('search');
                $q->where(fn ($w) => $w->where('numero', 'like', "%{$s}%")->orWhere('diagnostico', 'like', "%{$s}%"));
            })
            ->when($request->filled('id_tractivo'), fn ($q) => $q->where('id_tractivo', $request->integer('id_tractivo')))
            ->when($request->filled('id_motivo_entrada'), fn ($q) => $q->where('id_motivo_entrada', $request->integer('id_motivo_entrada')))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')), function ($q) use ($fecha) {
                $q->where(function ($w) use ($fecha) {
                    $w->where('estado', 'abierta')
                        ->orWhere(function ($c) use ($fecha) {
                            $c->where('estado', 'cerrada')
                                ->whereYear('fecha_salida', $fecha->year)
                                ->whereMonth('fecha_salida', $fecha->month);
                        });
                });
            })
            ->orderByDesc('fecha_ingreso');

        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);

        return OrdenTallerResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, OrdenesTaller $orden)
    {
        $this->autorizarEntidad($request, $orden->id_entidad);

        return new OrdenTallerResource($orden->load([
            'tractivo:id,codigo,placa', 'tipoMantenimiento:id,nombre', 'motivoEntrada:id,nombre',
            'clasificacion:id,nombre', 'operaciones', 'gastos', 'movimientos',
        ]));
    }
}
