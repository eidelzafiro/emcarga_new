<?php

namespace App\Http\Controllers;

use App\Services\Reports\CatalogoReportService;
use App\Services\Reports\FacturaReportService;
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
}
