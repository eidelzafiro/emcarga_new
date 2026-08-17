<?php

namespace App\Services\Reports;

use App\Models\OrdenesTaller;
use Illuminate\Http\Response;

/**
 * Reporte PDF de una Orden de Taller con sus operaciones, piezas y movimientos.
 */
class OrdenTallerReportService extends BaseReportService
{
    public function pdfOrdenTaller(int $id): Response
    {
        $orden = OrdenesTaller::with(
            'tractivo:id,descripcion,placa,marca,modelo',
            'tipoMantenimiento:id,nombre',
            'motivoEntrada:id,nombre',
            'clasificacion:id,nombre',
            'operaciones.tipoOperacion:id,nombre',
            'gastos',
            'movimientos',
        )->findOrFail($id);

        $this->title = 'Orden de Taller '.$orden->numero;

        return $this->streamPdf('reports.pdf.orden_taller', [
            'orden' => $orden,
        ], 'Orden_Taller_'.$orden->numero.'.pdf');
    }
}
