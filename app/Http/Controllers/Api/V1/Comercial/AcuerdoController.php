<?php

namespace App\Http\Controllers\Api\V1\Comercial;

use App\Http\Controllers\Api\Concerns\ScopesEntidadApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AcuerdoResource;
use App\Models\Acuerdo;
use Illuminate\Http\Request;

/**
 * Comercial · Precios por acuerdo (API móvil). Solo lectura, por entidad.
 */
class AcuerdoController extends Controller
{
    use ScopesEntidadApi;

    public function index(Request $request)
    {
        $entidades = $this->entidadesPermitidas($request);

        $query = Acuerdo::query()
            ->when(
                ! empty($entidades),
                fn ($q) => $q->whereIn('id_entidad', $entidades),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->with([
                'cliente:id,nombre',
                'origen:id,nombre',
                'destino:id,nombre',
                'producto:id,nombre',
            ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = (string) $request->string('search');
                $q->where(fn ($w) => $w->whereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$s}%"))
                    ->orWhereHas('origen', fn ($c) => $c->where('nombre', 'like', "%{$s}%"))
                    ->orWhereHas('destino', fn ($c) => $c->where('nombre', 'like', "%{$s}%"))
                    ->orWhereHas('producto', fn ($c) => $c->where('nombre', 'like', "%{$s}%")));
            })
            ->when($request->filled('id_cliente'), fn ($q) => $q->where('id_cliente', $request->integer('id_cliente')))
            ->when($request->has('activo'), fn ($q) => $q->where('activo', $request->boolean('activo')))
            ->orderByDesc('id');

        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);

        return AcuerdoResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, Acuerdo $acuerdo)
    {
        $this->autorizarEntidad($request, $acuerdo->id_entidad);

        return new AcuerdoResource($acuerdo->load([
            'cliente:id,nombre', 'origen:id,nombre', 'destino:id,nombre', 'producto:id,nombre',
        ]));
    }
}
