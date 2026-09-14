<?php

namespace App\Http\Controllers\Api\V1\Combustible;

use App\Http\Controllers\Api\Concerns\ScopesEntidadApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ReporteCostoResource;
use App\Models\ReporteCosto;
use Illuminate\Http\Request;

/**
 * Combustible · Reportes de costos por tractivo (API móvil). Solo lectura,
 * anclado al mes de operaciones y filtrado por la entidad del tractivo.
 */
class ReporteCostoController extends Controller
{
    use ScopesEntidadApi;

    public function index(Request $request)
    {
        $fecha = $this->fechaOperaciones($request);
        $entidades = $this->entidadesPermitidas($request);

        $query = ReporteCosto::query()
            ->when(
                ! empty($entidades),
                fn ($q) => $q->whereHas('tractivo', fn ($t) => $t->whereIn('id_entidad', $entidades)),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->with(['tractivo:id,codigo,placa'])
            ->whereYear('fecha_reporte', $fecha->year)
            ->whereMonth('fecha_reporte', $fecha->month)
            ->when($request->filled('search'), fn ($q) => $q->where('observaciones', 'like', '%'.(string) $request->string('search').'%'))
            ->when($request->filled('id_tractivo'), fn ($q) => $q->where('id_tractivo', $request->integer('id_tractivo')))
            ->orderByDesc('fecha_reporte')
            ->orderByDesc('id');

        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);

        return ReporteCostoResource::collection($query->paginate($perPage));
    }

    public function show(Request $request, ReporteCosto $reporte)
    {
        $this->autorizarEntidad($request, $reporte->tractivo?->id_entidad);

        return new ReporteCostoResource($reporte->load(['tractivo:id,codigo,placa']));
    }
}
