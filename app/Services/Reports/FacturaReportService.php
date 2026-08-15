<?php

namespace App\Services\Reports;

use App\Models\Factura;
use App\Models\Prefactura;
use Illuminate\Http\Response;

/**
 * Reportes PDF de facturación (factura y prefactura).
 * Sigue el esquema de Zafiro: una factura/prefactura agrupa aforos (cartas de porte).
 */
class FacturaReportService extends BaseReportService
{
    public function pdfFactura(int $id): Response
    {
        $factura = Factura::with([
            'cliente',
            'tipoIngreso',
            'entidad',
            'aforos.cartaPorte.cliente',
            'aforos.cartaPorte.tractivo',
            'aforos.cartaPorte.lugarOrigen:id,nombre',
            'aforos.cartaPorte.lugarDestino:id,nombre',
            'aforos.cartaPorte.producto',
        ])->findOrFail($id);

        $this->setTitle('Factura '.$factura->numero);

        return $this->streamPdf('reports.pdf.factura', [
            'factura' => $factura,
        ], 'Factura_'.$factura->numero.'.pdf');
    }

    public function pdfPrefactura(int $id): Response
    {
        $prefactura = Prefactura::with([
            'cliente',
            'entidad',
            'aforos.cartaPorte.cliente',
            'aforos.cartaPorte.tractivo',
            'aforos.cartaPorte.lugarOrigen:id,nombre',
            'aforos.cartaPorte.lugarDestino:id,nombre',
            'aforos.cartaPorte.producto',
        ])->findOrFail($id);

        $this->setTitle('Prefactura '.$prefactura->numero);

        return $this->streamPdf('reports.pdf.prefactura', [
            'prefactura' => $prefactura,
        ], 'Prefactura_'.$prefactura->numero.'.pdf');
    }
}
