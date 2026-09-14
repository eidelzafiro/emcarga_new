<?php

namespace App\Http\Controllers\Api\V1\Combustible;

use App\Http\Controllers\Api\Concerns\ScopesEntidadApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CombustibleDescargaResource;
use App\Models\CombustibleDescarga;
use Illuminate\Http\Request;

/**
 * Combustible · Descargas (API móvil). Solo lectura, anclado al mes de
 * operaciones y filtrado por entidad.
 */
class DescargaController extends Controller
{
    use ScopesEntidadApi;

    public function index(Request $request)
    {
        $fecha = $this->fechaOperaciones($request);
        $entidades = $this->entidadesPermitidas($request);

        $query = CombustibleDescarga::query()
            ->when(
                ! empty($entidades),
                fn ($q) => $q->whereIn('id_entidad', $entidades),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->with([
                'tarjeta:id,numero',
                'hojaRuta:id,numero,id_tractivo',
                'hojaRuta.tractivo:id,codigo',
                'tractivo:id,codigo',
                'empleado:id,nombre,apellidos',
                'servicentro:id,nombre',
            ])
            ->whereYear('fdescarga', $fecha->year)
            ->whereMonth('fdescarga', $fecha->month)
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = (string) $request->string('search');
                $q->where(fn ($w) => $w->where('folio', 'like', "%{$s}%")
                    ->orWhereHas('tarjeta', fn ($t) => $t->where('numero', 'like', "%{$s}%"))
                    ->orWhereHas('hojaRuta', fn ($h) => $h->where('numero', 'like', "%{$s}%"))
                    ->orWhereHas('tractivo', fn ($t) => $t->where('codigo', 'like', "%{$s}%")));
            })
            ->when($request->filled('id_tarjeta'), fn ($q) => $q->where('id_tarjeta', $request->integer('id_tarjeta')))
            ->when($request->filled('id_servicentro'), fn ($q) => $q->where('id_servicentro', $request->integer('id_servicentro')))
            ->orderByDesc('fdescarga')
            ->orderByDesc('id');

        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);

        return CombustibleDescargaResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, CombustibleDescarga $descarga)
    {
        $this->autorizarEntidad($request, $descarga->id_entidad);

        return new CombustibleDescargaResource($descarga->load([
            'tarjeta:id,numero', 'hojaRuta:id,numero,id_tractivo', 'hojaRuta.tractivo:id,codigo',
            'tractivo:id,codigo', 'empleado:id,nombre,apellidos', 'servicentro:id,nombre',
        ]));
    }
}
