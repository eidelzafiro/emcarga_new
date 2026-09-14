<?php

namespace App\Http\Controllers\Api\V1\Combustible;

use App\Http\Controllers\Api\Concerns\ScopesEntidadApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CombustibleCargaResource;
use App\Models\CombustibleCarga;
use Illuminate\Http\Request;

/**
 * Combustible · Cargas (API móvil). Solo lectura, anclado al mes de operaciones
 * y filtrado por entidad.
 */
class CargaController extends Controller
{
    use ScopesEntidadApi;

    public function index(Request $request)
    {
        $fecha = $this->fechaOperaciones($request);
        $entidades = $this->entidadesPermitidas($request);

        $query = CombustibleCarga::query()
            ->when(
                ! empty($entidades),
                fn ($q) => $q->whereIn('id_entidad', $entidades),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->with([
                'moneda:id,codigo',
                'tipoCombustible:id,nombre',
                'responsable:id,nombre,apellidos',
                'detalles.tarjeta:id,numero',
            ])
            ->whereYear('fcarga', $fecha->year)
            ->whereMonth('fcarga', $fecha->month)
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = (string) $request->string('search');
                $q->where(fn ($w) => $w->where('folio', 'like', "%{$s}%")
                    ->orWhereHas('responsable', fn ($r) => $r->where('nombre', 'like', "%{$s}%")->orWhere('apellidos', 'like', "%{$s}%"))
                    ->orWhereHas('detalles.tarjeta', fn ($t) => $t->where('numero', 'like', "%{$s}%")));
            })
            ->when($request->filled('id_tipo_combustible'), fn ($q) => $q->where('id_tipo_combustibles', $request->integer('id_tipo_combustible')))
            ->orderByDesc('fcarga')
            ->orderByDesc('id');

        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);

        return CombustibleCargaResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, CombustibleCarga $carga)
    {
        $this->autorizarEntidad($request, $carga->id_entidad);

        return new CombustibleCargaResource($carga->load([
            'moneda:id,codigo', 'tipoCombustible:id,nombre', 'responsable:id,nombre,apellidos', 'detalles.tarjeta:id,numero',
        ]));
    }
}
