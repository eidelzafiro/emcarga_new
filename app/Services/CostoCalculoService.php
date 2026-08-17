<?php

namespace App\Services;

use App\Models\Aforo;
use App\Models\CombustibleDescarga;
use App\Models\Dieta;
use App\Models\HojasRuta;
use App\Models\ReporteCosto;
use App\Models\Tractivo;
use Illuminate\Support\Carbon;

/**
 * Cálculo de costos e indicadores por tractivo y mes.
 *
 * Recalcular básico: agrega descargas de combustible, dietas, kms y
 * toneladas/ingresos del mes desde las tablas nuevas y rellena (o crea)
 * la fila correspondiente de `reportes_costos`. La amortización y la chapa
 * se toman de la ficha del tractivo (amortmn / vchapa).
 */
class CostoCalculoService
{
    public function recalcular(int|Tractivo $tractivo, string|Carbon $fecha): ReporteCosto
    {
        $tractivo = $tractivo instanceof Tractivo ? $tractivo : Tractivo::findOrFail($tractivo);
        $fecha = $fecha instanceof Carbon ? $fecha : Carbon::parse($fecha);
        $inicio = $fecha->copy()->startOfMonth();
        $fin = $fecha->copy()->endOfMonth();

        // Kms del mes: suma de kms_totales de las hojas de ruta del tractivo
        $kmsTotal = (float) HojasRuta::where('id_tractivo', $tractivo->id)
            ->whereBetween('fecha_emision', [$inicio, $fin])
            ->sum('kms_totales');

        // Descargas de combustible del mes: vía hoja de ruta → tractivo
        $combustibleMn = (float) CombustibleDescarga::whereHas('hojaRuta', fn ($q) => $q->where('id_tractivo', $tractivo->id))
            ->whereBetween('fdescarga', [$inicio, $fin])
            ->sum('saldo_mon');

        // Dietas del mes del tractivo
        $dietas = (float) Dieta::where('id_tractivo', $tractivo->id)
            ->where('cancelada', false)
            ->whereBetween('fecha', [$inicio, $fin])
            ->sum('monto');

        // Aforos del mes (por tractivo de la CP)
        $aforos = Aforo::whereHas('cartaPorte', fn ($q) => $q->whereHas('hojaRuta', fn ($q2) => $q2->where('id_tractivo', $tractivo->id)))
            ->whereBetween('fecha_parte', [$inicio, $fin]);

        $ingresosMn = (float) (clone $aforos)->sum('ingreso_mt');
        $toneladas = (float) (clone $aforos)->sum('tn_pos_total');

        // Amortización y chapa mensual desde la ficha del tractivo
        $amortizacionMn = (float) $tractivo->amortmn;
        $chapa = (float) $tractivo->vchapa;

        $salario = 0.0;
        $lubricanteMn = 0.0;
        $piezasMn = 0.0;

        $gastosMn = round(
            $combustibleMn + $lubricanteMn + $piezasMn + $salario
            + $dietas + $amortizacionMn + $chapa,
            2
        );

        $utilidadMn = round($ingresosMn - $gastosMn, 2);
        $costoTnKms = $kmsTotal > 0 && $toneladas > 0 ? round($gastosMn / ($kmsTotal * $toneladas), 4) : 0;

        return ReporteCosto::updateOrCreate(
            [
                'id_tractivo' => $tractivo->id,
                'fecha_reporte' => $inicio->toDateString(),
            ],
            [
                'combustible_mn' => $combustibleMn,
                'lubricante_mn' => $lubricanteMn,
                'piezas_mn' => $piezasMn,
                'salario' => $salario,
                'salario_total' => $salario,
                'dietas' => $dietas,
                'amortizacion_mn' => $amortizacionMn,
                'chapa' => $chapa,
                'kms_total' => $kmsTotal,
                'toneladas' => $toneladas,
                'ingresos_mn' => $ingresosMn,
                'gastos_mn' => $gastosMn,
                'utilidad_mn' => $utilidadMn,
                'costo_tn_kms' => $costoTnKms,
                'estado' => 'calculado',
                'id_user' => auth()->id(),
            ]
        );
    }
}