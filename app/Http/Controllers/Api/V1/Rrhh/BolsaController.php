<?php

namespace App\Http\Controllers\Api\V1\Rrhh;

use App\Http\Controllers\Api\Concerns\ScopesEntidadApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BolsaResource;
use App\Models\Bolsa;
use Illuminate\Http\Request;

/**
 * RRHH · Bolsa de empleados (API móvil). Solo lectura, filtrado por entidad.
 */
class BolsaController extends Controller
{
    use ScopesEntidadApi;

    public function index(Request $request)
    {
        $entidades = $this->entidadesPermitidas($request);

        $query = Bolsa::query()
            ->when(
                ! empty($entidades),
                fn ($q) => $q->whereIn('id_entidad', $entidades),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->with(['cargo:id,nombre', 'area:id,nombre', 'entidad:id,nombre,abreviatura'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = (string) $request->string('search');
                $q->where(fn ($w) => $w->where('nombre', 'like', "%{$s}%")
                    ->orWhere('apellidos', 'like', "%{$s}%")
                    ->orWhere('ci', 'like', "%{$s}%"));
            })
            ->when($request->filled('id_cargo'), fn ($q) => $q->where('id_cargo', $request->integer('id_cargo')))
            ->when($request->filled('id_area'), fn ($q) => $q->where('id_area', $request->integer('id_area')))
            ->when($request->has('activo'), fn ($q) => $q->where('activo', $request->boolean('activo')))
            ->orderBy('nombre')
            ->orderBy('apellidos');

        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);

        return BolsaResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, Bolsa $bolsa)
    {
        $this->autorizarEntidad($request, $bolsa->id_entidad);

        return new BolsaResource($bolsa->load(['cargo:id,nombre', 'area:id,nombre', 'entidad:id,nombre,abreviatura']));
    }
}
