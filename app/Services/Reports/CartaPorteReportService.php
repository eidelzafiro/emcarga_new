<?php

namespace App\Services\Reports;

use App\Models\CartaPorte;
use Illuminate\Http\Response;

/**
 * Reporte PDF de una carta de porte (impresión del documento emitido).
 */
class CartaPorteReportService extends BaseReportService
{
    public function pdfCartaPorte(int $id): Response
    {
        $carta = CartaPorte::with([
            'cliente',
            'hojaRuta:id,numero,fecha_emision,fecha_cierre,id_entidad',
            'hojaRuta.entidad:id,nombre,abreviatura',
            'hojaRuta.tractivo:id,codigo',
            'hojaRuta.arrastre:id,codigo',
            'hojaRuta.chofer:id,nombre,apellidos',
            'hojaRuta.chofer2:id,nombre,apellidos',
            'solicitud:id,numero,id_lugar_origen,id_lugar_destino,id_producto,id_producto2,id_tipo_carga,id_tipo_carga2',
            'tractivo',
            'arrastre',
            'chofer',
            'chofer2',
            'lugarOrigen',
            'lugarDestino',
            'producto',
            'tipoCarga',
        ])->findOrFail($id);

        $this->setTitle('Carta de Porte '.$carta->numero);

        return $this->streamPdf('reports.pdf.carta_porte', [
            'carta' => $carta,
        ], 'Carta_Porte_'.$carta->numero.'.pdf');
    }
}
