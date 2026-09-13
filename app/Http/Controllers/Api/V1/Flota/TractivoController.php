<?php

namespace App\Http\Controllers\Api\V1\Flota;

use App\Http\Controllers\Api\Concerns\ScopesEntidadApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TractivoResource;
use App\Models\Tractivo;
use Illuminate\Http\Request;

/**
 * Flota · Tractivos (API móvil). Solo lectura, filtrado por la entidad del token.
 */
class TractivoController extends Controller
{
    use ScopesEntidadApi;

    public function index(Request $request)
    {
        $entidades = $this->entidadesPermitidas($request);

        $query = Tractivo::query()
            ->when(
                ! empty($entidades),
                fn ($q) => $q->whereIn('id_entidad', $entidades),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->with(['tipoVehiculo.tipoEquipo', 'tipoVehiculo.marca', 'tipoVehiculo.modelo'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = (string) $request->string('search');
                $q->where(fn ($w) => $w->where('codigo', 'like', "%{$s}%")->orWhere('placa', 'like', "%{$s}%"));
            })
            ->when($request->filled('id_tipo_vehiculo'), fn ($q) => $q->where('id_tipo_vehiculo', $request->integer('id_tipo_vehiculo')))
            ->orderBy('codigo');

        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);

        return TractivoResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, Tractivo $tractivo)
    {
        $this->autorizarEntidad($request, $tractivo->id_entidad);

        return new TractivoResource($tractivo->load([
            'tipoVehiculo.tipoEquipo', 'tipoVehiculo.marca', 'tipoVehiculo.modelo',
            'motor', 'caja', 'diferencial',
        ]));
    }
}
