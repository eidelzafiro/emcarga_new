<?php

namespace App\Http\Controllers\Api\V1\Rrhh;

use App\Http\Controllers\Api\Concerns\ScopesEntidadApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CargoResource;
use App\Models\Cargo;
use Illuminate\Http\Request;

/**
 * RRHH · Cargos (API móvil). Solo lectura, filtrado por entidad.
 */
class CargoController extends Controller
{
    use ScopesEntidadApi;

    public function index(Request $request)
    {
        $entidades = $this->entidadesPermitidas($request);

        $query = Cargo::query()
            ->when(
                ! empty($entidades),
                fn ($q) => $q->whereIn('id_entidad', $entidades),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = (string) $request->string('search');
                $q->where(fn ($w) => $w->where('nombre', 'like', "%{$s}%")->orWhere('codigo', 'like', "%{$s}%"));
            })
            ->when($request->filled('id_entidad'), fn ($q) => $q->where('id_entidad', $request->integer('id_entidad')))
            ->when($request->has('activo'), fn ($q) => $q->where('activo', $request->boolean('activo')))
            ->orderBy('nombre');

        $perPage = min(max((int) $request->integer('per_page', 50), 1), 100);

        return CargoResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, Cargo $cargo)
    {
        $this->autorizarEntidad($request, $cargo->id_entidad);

        return new CargoResource($cargo);
    }
}
