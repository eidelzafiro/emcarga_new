<?php

namespace App\Http\Controllers\Api\V1\Taller;

use App\Http\Controllers\Api\Concerns\ScopesEntidadApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ControlLubricanteResource;
use App\Models\ControlLubricante;
use Illuminate\Http\Request;

/**
 * Taller · Control de lubricantes CT-7 (API móvil). Solo lectura, por entidad.
 */
class ControlLubricanteController extends Controller
{
    use ScopesEntidadApi;

    public function index(Request $request)
    {
        $entidades = $this->entidadesPermitidas($request);

        $query = ControlLubricante::query()
            ->when(
                ! empty($entidades),
                fn ($q) => $q->whereIn('id_entidad', $entidades),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->with(['tractivo:id,descripcion,placa', 'entidad:id,nombre'])
            ->when($request->filled('id_tractivo'), fn ($q) => $q->where('id_tractivo', $request->integer('id_tractivo')))
            ->when($request->filled('tipo_operacion'), fn ($q) => $q->where('tipo_operacion', $request->string('tipo_operacion')))
            ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha_cambio', '>=', $request->date('desde')))
            ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha_cambio', '<=', $request->date('hasta')))
            ->orderByDesc('fecha_cambio')
            ->orderByDesc('id');

        $perPage = min(max((int) $request->integer('per_page', 50), 1), 100);

        return ControlLubricanteResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, ControlLubricante $registro)
    {
        $this->autorizarEntidad($request, $registro->id_entidad);

        return new ControlLubricanteResource($registro->load(['tractivo:id,descripcion,placa', 'entidad:id,nombre']));
    }
}
