<?php

namespace App\Services\Reports;

use App\Models\Neumatico;
use Illuminate\Http\Response;

/**
 * Reporte de plan de bajas de neumáticos (réplica del legacy mostrar_plan_bajas).
 *
 * tipo 1 = por vencer con aviso (fecha_plan_aviso <= hoy < fecha_plan_retiro)
 * tipo 2 = vencidos (fecha_plan_retiro <= hoy)
 */
class PlanBajasNeumaticoReportService extends BaseReportService
{
    public function pdfPlanBajas(int $tipo = 1, ?int $idEntidad = null): Response
    {
        $this->title = $tipo === 1 ? 'Plan de bajas de neumáticos (aviso)' : 'Plan de bajas de neumáticos (vencidos)';
        $this->orientation = 'landscape';

        $query = Neumatico::with('tractivo:id,descripcion,placa', 'posicion:id,nombre')
            ->whereNull('fecha_retiro')
            ->whereNotNull('fecha_plan_retiro');

        if ($tipo === 1) {
            $query->whereDate('fecha_plan_aviso', '<=', now()->toDateString())
                ->whereDate('fecha_plan_retiro', '>', now()->toDateString());
        } else {
            $query->whereDate('fecha_plan_retiro', '<=', now()->toDateString());
        }

        if ($idEntidad) {
            $query->where('id_entidad', $idEntidad);
        }

        $neumaticos = $query->orderBy('fecha_plan_retiro')->get();

        return $this->streamPdf('reports.pdf.plan_bajas_neumatico', [
            'neumaticos' => $neumaticos,
            'tipo' => $tipo,
        ], $this->title.'.pdf');
    }
}
