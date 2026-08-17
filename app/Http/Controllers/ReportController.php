<?php

namespace App\Http\Controllers;

use App\Services\Reports\AforoReportService;
use App\Services\Reports\CatalogoReportService;
use App\Services\Reports\CartaPorteReportService;
use App\Services\Reports\FacturaReportService;
use App\Services\Reports\HojaRutaReportService;
use App\Services\Reports\NominaReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // === Catálogos PDF ===

    public function pdfMarcas()
    {
        return app(CatalogoReportService::class)->pdfMarcas();
    }

    public function pdfModelos()
    {
        return app(CatalogoReportService::class)->pdfModelos();
    }

    public function pdfPaises()
    {
        return app(CatalogoReportService::class)->pdfPaises();
    }

    // === Nóminas RRHH ===

    public function pdfSalarioPrenomina(Request $request)
    {
        return app(NominaReportService::class)->pdfPrenomina($request);
    }

    public function pdfSalarioChoferes(Request $request)
    {
        return app(NominaReportService::class)->pdfSalarioChoferes($request);
    }

    // === Facturación ===

    public function pdfFactura(int $id)
    {
        return app(FacturaReportService::class)->pdfFactura($id);
    }

    public function pdfPrefactura(int $id)
    {
        return app(FacturaReportService::class)->pdfPrefactura($id);
    }

    public function pdfCartaPorte(int $id)
    {
        return app(CartaPorteReportService::class)->pdfCartaPorte($id);
    }

    public function pdfHojaRuta(int $id)
    {
        return app(HojaRutaReportService::class)->pdfHojaRuta($id);
    }

    public function pdfAforo(int $id)
    {
        return app(AforoReportService::class)->pdfAforo($id);
    }
}
