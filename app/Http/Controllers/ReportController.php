<?php

namespace App\Http\Controllers;

use App\Services\Reports\AforoReportService;
use App\Services\Reports\CatalogoReportService;
use App\Services\Reports\CartaPorteReportService;
use App\Services\Reports\ControlLubricanteReportService;
use App\Services\Reports\FacturaReportService;
use App\Services\Reports\HojaRutaReportService;
use App\Services\Reports\NominaReportService;
use App\Services\Reports\OrdenTallerReportService;
use App\Services\Reports\PlanBajasNeumaticoReportService;
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

    // === Módulo Técnico ===

    public function pdfPlanBajasNeumaticos(Request $request)
    {
        return app(PlanBajasNeumaticoReportService::class)
            ->pdfPlanBajas($request->integer('tipo', 1), (int) session('entidad_activa_id') ?: null);
    }

    public function pdfControlLubricante(Request $request)
    {
        return app(ControlLubricanteReportService::class)
            ->pdfControlLubricante($request->input('desde'), $request->input('hasta'), (int) session('entidad_activa_id') ?: null);
    }

    public function pdfOrdenTaller(int $id)
    {
        return app(OrdenTallerReportService::class)->pdfOrdenTaller($id);
    }
}
