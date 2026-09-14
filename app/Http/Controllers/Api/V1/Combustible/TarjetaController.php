<?php

namespace App\Http\Controllers\Api\V1\Combustible;

use App\Http\Controllers\Api\Concerns\ScopesEntidadApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TarjetaResource;
use App\Models\Tarjeta;
use Illuminate\Http\Request;

/**
 * Combustible · Tarjetas (API móvil). Solo lectura, filtrado por entidad.
 */
class TarjetaController extends Controller
{
    use ScopesEntidadApi;

    public function index(Request $request)
    {
        $entidades = $this->entidadesPermitidas($request);

        $query = Tarjeta::query()
            ->when(
                ! empty($entidades),
                fn ($q) => $q->whereIn('id_entidad', $entidades),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->with(['moneda:id,codigo,nombre', 'tipoCombustible:id,nombre', 'empleado:id,nombre,apellidos', 'tractivo:id,codigo'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = (string) $request->string('search');
                $q->where(fn ($w) => $w->where('numero', 'like', "%{$s}%")
                    ->orWhereHas('empleado', fn ($e) => $e->where('nombre', 'like', "%{$s}%")->orWhere('apellidos', 'like', "%{$s}%")));
            })
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->when($request->boolean('con_saldos'), fn ($q) => $q->where('saldo_actual', '>', 0))
            ->when($request->filled('id_tipo_combustible'), fn ($q) => $q->where('idtipocombustibles', $request->integer('id_tipo_combustible')))
            ->orderBy('numero');

        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);

        return TarjetaResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, Tarjeta $tarjeta)
    {
        $this->autorizarEntidad($request, $tarjeta->id_entidad);

        return new TarjetaResource($tarjeta->load([
            'moneda:id,codigo,nombre', 'tipoCombustible:id,nombre', 'empleado:id,nombre,apellidos', 'tractivo:id,codigo',
        ]));
    }
}
