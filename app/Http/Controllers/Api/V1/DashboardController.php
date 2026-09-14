<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\ScopesEntidadApi;
use App\Http\Controllers\Controller;
use App\Models\Aforo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Dashboard (API móvil). Resumen de ingresos por aforos anclado al mes de
 * operaciones y filtrado por la entidad de la carta de porte.
 */
class DashboardController extends Controller
{
    use ScopesEntidadApi;

    public function resumen(Request $request)
    {
        $fecha = $this->fechaOperaciones($request);
        $entidades = $this->entidadesPermitidas($request);

        return response()->json([
            'periodo' => $fecha->format('Y-m'),
            'entidad' => $this->entidadActivaId($request),
            'serie_mensual' => $this->serieMensual($fecha, $entidades),
            'serie_diaria' => $this->serieDiaria($fecha, $entidades),
        ]);
    }

    /**
     * Serie de los últimos 12 meses (incluido el de operaciones).
     *
     * @param  array<int,int>  $entidades
     * @return array<int,array{mes:string,etiqueta:string,aforos:int,ingreso_mt:float}>
     */
    private function serieMensual(Carbon $fecha, array $entidades): array
    {
        $inicio = $fecha->copy()->subMonths(11)->startOfMonth();
        $fin = $fecha->copy()->startOfMonth();

        $rows = $this->scoped($inicio, $fin, $entidades)
            ->selectRaw("DATE_FORMAT(fecha_parte, '%Y-%m') as clave")
            ->selectRaw('COUNT(*) as aforos')
            ->selectRaw('COALESCE(SUM(ingreso_mt), 0) as ingreso_mt')
            ->groupBy('clave')
            ->orderBy('clave')
            ->get()
            ->keyBy('clave');

        $serie = [];
        foreach (range(0, 11) as $i) {
            $mes = $inicio->copy()->addMonths($i);
            $clave = $mes->format('Y-m');
            $fila = $rows[$clave] ?? null;
            $serie[] = [
                'mes' => $clave,
                'etiqueta' => ucfirst($mes->translatedFormat('M Y')),
                'aforos' => (int) ($fila->aforos ?? 0),
                'ingreso_mt' => round((float) ($fila->ingreso_mt ?? 0), 2),
            ];
        }

        return $serie;
    }

    /**
     * Serie diaria del mes de operaciones.
     *
     * @param  array<int,int>  $entidades
     * @return array<int,array{dia:string,aforos:int,ingreso_mt:float}>
     */
    private function serieDiaria(Carbon $fecha, array $entidades): array
    {
        $inicio = $fecha->copy()->startOfMonth();
        $fin = $fecha->copy()->endOfMonth();

        $rows = $this->scoped($inicio, $fin, $entidades)
            ->selectRaw("DATE_FORMAT(fecha_parte, '%Y-%m-%d') as clave")
            ->selectRaw('COUNT(*) as aforos')
            ->selectRaw('COALESCE(SUM(ingreso_mt), 0) as ingreso_mt')
            ->groupBy('clave')
            ->orderBy('clave')
            ->get()
            ->keyBy('clave');

        $serie = [];
        for ($dia = $inicio->copy(); $dia->lte($fin); $dia->addDay()) {
            $clave = $dia->toDateString();
            $fila = $rows[$clave] ?? null;
            $serie[] = [
                'dia' => $clave,
                'aforos' => (int) ($fila->aforos ?? 0),
                'ingreso_mt' => round((float) ($fila->ingreso_mt ?? 0), 2),
            ];
        }

        return $serie;
    }

    /**
     * Consulta de aforos entre fechas, restringida a las entidades permitidas.
     *
     * @param  array<int,int>  $entidades
     */
    private function scoped(Carbon $desde, Carbon $hasta, array $entidades)
    {
        return Aforo::query()
            ->whereBetween('fecha_parte', [$desde->toDateString(), $hasta->toDateString()])
            ->when(!empty($entidades), fn ($q) => $q->whereHas('cartaPorte', fn ($c) => $this->whereCartaEnEntidades($c, $entidades)), fn ($q) => $q->whereRaw('1 = 0'))
            ->toBase();
    }
}