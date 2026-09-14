<?php

namespace App\Http\Controllers\Api\V1\Ingresos;

use App\Http\Controllers\Api\Concerns\ScopesEntidadApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AforoResource;
use App\Models\Aforo;
use Illuminate\Http\Request;

/**
 * Ingresos · Aforos (API móvil). Solo lectura, anclado al mes de operaciones y
 * filtrado por la entidad de la carta de porte.
 */
class AforoController extends Controller
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
                'cartaPorte.hojaRuta:id,numero,id_entidad',
                'factura:id,numero',
            ])
            ->whereYear('fecha_parte', $fecha->year)
            ->whereMonth('fecha_parte', $fecha->month)
            ->when(! empty($entidades), fn ($q) => $q->whereHas('cartaPorte', fn ($c) => $this->whereCartaEnEntidades($c, $entidades)), fn ($q) => $q->whereRaw('1 = 0'))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = (string) $request->string('search');
                $q->where(function ($w) use ($s) {
                    $w->whereHas('cartaPorte', fn ($c) => $c->where('numero', 'like', "%{$s}%"))
                        ->orWhereHas('cartaPorte.cliente', fn ($c) => $c->where('nombre', 'like', "%{$s}%"))
                        ->orWhereHas('cartaPorte.tractivo', fn ($c) => $c->where('codigo', 'like', "%{$s}%"));
                });
            })
            ->when($request->filled('estado'), function ($q) use ($request) {
                match ($request->string('estado')->toString()) {
                    'facturado' => $q->whereNotNull('id_factura'),
                    'prefacturado' => $q->whereNotNull('id_prefactura')->whereNull('id_factura'),
                    'pendiente' => $q->whereNull('id_factura')->whereNull('id_prefactura'),
                    default => $q,
                };
            })
            ->orderByDesc('fecha_parte')
            ->orderByDesc('id');

        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);

        return AforoResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, Aforo $aforo)
    {
        $this->autorizarEntidadAforo($request, $aforo);

        return new AforoResource($aforo->load([
            'cartaPorte.cliente:id,nombre',
            'cartaPorte.tractivo:id,codigo,placa',
            'cartaPorte.chofer:id,nombre,apellidos',
            'cartaPorte.chofer2:id,nombre,apellidos',
            'cartaPorte.lugarOrigen:id,nombre',
            'cartaPorte.lugarDestino:id,nombre',
            'factura:id,numero',
            'indicadoresFilas',
        ]));
    }

    private function autorizarEntidadAforo(Request $request, Aforo $aforo): void
    {
        $entidades = array_map('intval', $this->entidadesPermitidas($request));
        $cp = $aforo->cartaPorte;

        $permitido = $cp && (
            in_array((int) $cp->hojaRuta?->id_entidad, $entidades, true)
            || in_array((int) $cp->hojaRuta?->tractivo?->id_entidad, $entidades, true)
            || in_array((int) $cp->solicitud?->id_entidad, $entidades, true)
        );

        abort_unless($permitido, 403);
    }
}
