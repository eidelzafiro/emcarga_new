<?php

namespace App\Http\Controllers\Api\V1\Rrhh;

use App\Http\Controllers\Api\Concerns\ScopesEntidadApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SalarioResource;
use App\Models\Salario;
use Illuminate\Http\Request;

/**
 * RRHH · Salarios (API móvil). Solo lectura, anclado al mes de operaciones del
 * token y filtrado por entidad.
 */
class SalarioController extends Controller
{
    use ScopesEntidadApi;

    public function index(Request $request)
    {
        $fecha = $this->fechaOperaciones($request);
        $entidades = $this->entidadesPermitidas($request);

        $query = Salario::query()
            ->when(
                ! empty($entidades),
                fn ($q) => $q->whereIn('id_entidad', $entidades),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->with(['bolsa:id,nombre,apellidos,ci', 'cargo:id,nombre', 'area:id,nombre'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = (string) $request->string('search');
                $q->where(fn ($w) => $w->where('numero_nomina', 'like', "%{$s}%")
                    ->orWhereHas('bolsa', fn ($b) => $b->where('nombre', 'like', "%{$s}%")
                        ->orWhere('apellidos', 'like', "%{$s}%")
                        ->orWhere('ci', 'like', "%{$s}%")));
            })
            ->when($request->filled('id_bolsa'), fn ($q) => $q->where('id_bolsa', $request->integer('id_bolsa')))
            ->when($request->filled('id_area'), fn ($q) => $q->where('id_area', $request->integer('id_area')))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            // Ancla al mes de operaciones salvo que el usuario pase mes/ano explícitos.
            ->when(! $request->filled('mes'), fn ($q) => $q->where('mes', $fecha->month))
            ->when(! $request->filled('ano'), fn ($q) => $q->where('ano', $fecha->year))
            ->when($request->filled('mes'), fn ($q) => $q->where('mes', $request->integer('mes')))
            ->when($request->filled('ano'), fn ($q) => $q->where('ano', $request->integer('ano')))
            ->orderByDesc('id');

        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);

        return SalarioResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, Salario $salario)
    {
        $this->autorizarEntidad($request, $salario->id_entidad);

        return new SalarioResource($salario->load(['bolsa:id,nombre,apellidos,ci', 'cargo:id,nombre', 'area:id,nombre']));
    }
}
