<?php

namespace App\Http\Controllers\Api\V1\Ingresos;

use App\Http\Controllers\Api\Concerns\ScopesEntidadApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AforoResource;
use App\Models\Aforo;
use Illuminate\Http\Request;

/**
 * Ingresos · Indicadores de explotación (API móvil). Resumen por aforo del mes
 * de operaciones, filtrado por la entidad de la carta de porte.
 */
class IndicadorController extends Controller
{
    use ScopesEntidadApi;

    public function index(Request $request)
    {
        $fecha = $this->fechaOperaciones($request);
        $entidades = $this->entidadesPermitidas($request);

        $query = Aforo::query()
            ->with([
                'cartaPorte:id,numero,id_hoja_ruta,id_solicitud',
                'cartaPorte.cliente:id,nombre',
                'cartaPorte.tractivo:id,codigo,placa',
                'indicadoresFilas',
            ])
            ->whereYear('fecha_parte', $fecha->year)
            ->whereMonth('fecha_parte', $fecha->month)
            ->when(! empty($entidades), fn ($q) => $q->whereHas('cartaPorte', fn ($c) => $this->whereCartaEnEntidades($c, $entidades)), fn ($q) => $q->whereRaw('1 = 0'))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = (string) $request->string('search');
                $q->where(function ($w) use ($s) {
                    $w->whereHas('cartaPorte', fn ($c) => $c->where('numero', 'like', "%{$s}%"))
                        ->orWhereHas('cartaPorte.cliente', fn ($c) => $c->where('nombre', 'like', "%{$s}%"));
                });
            })
            ->orderByDesc('fecha_parte')
            ->orderByDesc('id');

        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);

        return AforoResource::collection($query->paginate($perPage));
    }
}
