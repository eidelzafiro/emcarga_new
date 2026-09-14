<?php

namespace App\Http\Controllers\Api\V1\Comercial;

use App\Http\Controllers\Api\Concerns\ScopesEntidadApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\Request;

/**
 * Comercial · Clientes (API móvil). Solo lectura, filtrado por entidad.
 */
class ClienteController extends Controller
{
    use ScopesEntidadApi;

    public function index(Request $request)
    {
        $entidades = $this->entidadesPermitidas($request);

        $query = Cliente::query()
            ->when(
                ! empty($entidades),
                fn ($q) => $q->whereIn('id_entidad', $entidades),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->with(['organismo:id,nombre,codigo', 'moneda:id,codigo,nombre'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = (string) $request->string('search');
                $q->where(fn ($w) => $w->where('nombre', 'like', "%{$s}%")
                    ->orWhere('codigo', 'like', "%{$s}%")
                    ->orWhere('nrocontrato', 'like', "%{$s}%"));
            })
            ->when($request->has('activo'), fn ($q) => $q->where('activo', $request->boolean('activo')))
            ->orderBy('nombre');

        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);

        return ClienteResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, Cliente $cliente)
    {
        $this->autorizarEntidad($request, $cliente->id_entidad);

        return new ClienteResource($cliente->load(['organismo:id,nombre,codigo', 'moneda:id,codigo,nombre']));
    }
}
