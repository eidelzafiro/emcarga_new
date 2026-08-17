<?php

namespace App\Services\Reports;

use App\Models\Aforo;
use Illuminate\Http\Response;

/**
 * Reporte PDF de un aforo (desglose completo de tarifas, demora, recargos,
 * salario e indicadores). Paridad con el PDF legacy de aforo.
 */
class AforoReportService extends BaseReportService
{
    public function pdfAforo(int $id): Response
    {
        $aforo = Aforo::with([
            'cartaPorte.cliente',
            'cartaPorte.tractivo',
            'cartaPorte.arrastre',
            'cartaPorte.chofer',
            'cartaPorte.chofer2',
            'cartaPorte.lugarOrigen',
            'cartaPorte.lugarDestino',
            'cartaPorte.hojaRuta:id,numero,fecha_emision,id_entidad',
            'cartaPorte.hojaRuta.entidad:id,nombre,abreviatura',
            'cartaPorte.solicitud:id,numero',
            'tasa:id,nombre,tasa',
            'user:id,name',
            'lineas.tipoCarga:id,nombre',
        ])->findOrFail($id);

        $this->setTitle('Aforo '.($aforo->cartaPorte?->numero ?? $aforo->id));

        return $this->streamPdf('reports.pdf.aforo', [
            'aforo' => $aforo,
        ], 'Aforo_'.($aforo->cartaPorte?->numero ?? $aforo->id).'.pdf');
    }
}
