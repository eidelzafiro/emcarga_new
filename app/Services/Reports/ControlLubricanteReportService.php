<?php

namespace App\Services\Reports;

use App\Models\ControlLubricante;
use Illuminate\Http\Response;

/**
 * Reporte de Control de Lubricantes CT-7 por rango de fechas (réplica del legacy
 * Reportestec). Agrupa por tractivo y tipo de operación.
 */
class ControlLubricanteReportService extends BaseReportService
{
    public function pdfControlLubricante(?string $desde = null, ?string $hasta = null, ?int $idEntidad = null): Response
    {
        $this->title = 'Control de Lubricantes (CT-7)';
        $this->orientation = 'landscape';

        $desde = $desde ?? now()->startOfMonth()->toDateString();
        $hasta = $hasta ?? now()->endOfMonth()->toDateString();

        $query = ControlLubricante::with('tractivo:id,descripcion,placa')
            ->whereBetween('fecha_cambio', [$desde, $hasta]);

        if ($idEntidad) {
            $query->where('id_entidad', $idEntidad);
        }

        $registros = $query->orderBy('fecha_cambio')->orderBy('id_tractivo')->get();

        return $this->streamPdf('reports.pdf.control_lubricante', [
            'registros' => $registros,
            'desde' => $desde,
            'hasta' => $hasta,
        ], 'Control_Lubricantes_CT7.pdf');
    }
}
