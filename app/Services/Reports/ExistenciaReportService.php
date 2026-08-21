<?php

namespace App\Services\Reports;

use App\Models\Aforo;
use Illuminate\Support\Facades\DB;

/**
 * Fase D — EXISTENCIA (1 reporte legacy, fuente Reportes2):
 *  374 CONTROL DIARIO EXTRACCION CONTENEDORES POR TIPO (mes).
 * Versión funcional: agrupa aforos por tipo de carga (contenedor) por mes.
 */
class ExistenciaReportService extends BaseReportService
{
    public function extraccionContenedoresTipo(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("COALESCE(tipo_indicadores,0) as tipo, COUNT(*) as extracciones, SUM(COALESCE(tn_real_total,0)) as toneladas")
            ->groupBy('tipo_indicadores')->orderBy('tipo_indicadores')->get();

        return $this->reporteTablaPdf('Control Diario Extracción Contenedores por Tipo',
            [
                ['key' => 'tipo', 'label' => 'Tipo', 'num' => true],
                ['key' => 'extracciones', 'label' => 'Extracciones', 'num' => true],
                ['key' => 'toneladas', 'label' => 'Toneladas', 'num' => true],
            ],
            $rows->toArray(), ['periodo' => $mes ? "Mes: $mes" : 'Todos', 'landscape' => true]);
    }
}
