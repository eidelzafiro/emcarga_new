<?php

namespace App\Services\Reports;

use App\Models\HojasRuta;
use Illuminate\Http\Response;

/**
 * Reporte PDF de una hoja de ruta (impresión del documento emitido).
 */
class HojaRutaReportService extends BaseReportService
{
    public function pdfHojaRuta(int $id): Response
    {
        $hoja = HojasRuta::with([
            'entidad:id,nombre,abreviatura',
            'tractivo:id,codigo,marca,modelo,placa,id_tipo_vehiculo,capacidad_toneladas',
            'arrastre:id,codigo,placa',
            'chofer:id,nombre,apellidos,ci,categorias_licencia',
            'chofer2:id,nombre,apellidos,ci,categorias_licencia',
            'parqueo:id,nombre',
            'grupo:id,nombre',
            'user:id,name',
        ])->findOrFail($id);

        $this->setTitle('Hoja de Ruta '.$hoja->numero);

        return $this->streamPdf('reports.pdf.hoja_ruta', [
            'hoja' => $hoja,
        ], 'Hoja_Ruta_'.$hoja->numero.'.pdf');
    }
}
