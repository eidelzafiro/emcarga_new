<?php

namespace App\Http\Controllers;

use App\Services\Reports\AforoReportService;
use App\Services\Reports\CatalogoReportService;
use App\Services\Reports\CartaPorteReportService;
use App\Services\Reports\ControlLubricanteReportService;
use App\Services\Reports\FacturaReportService;
use App\Services\Reports\HojaRutaReportService;
use App\Services\Reports\IngresosReportService;
use App\Services\Reports\NominaReportService;
use App\Services\Reports\OrdenTallerReportService;
use App\Services\Reports\PlanBajasNeumaticoReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // === Catálogos PDF (requiere reportes.ver) ===

    public function pdfMarcas()
    {
        abort_unless(auth()->user()->can('reportes.ver'), 403);

        return app(CatalogoReportService::class)->pdfMarcas();
    }

    public function pdfModelos()
    {
        abort_unless(auth()->user()->can('reportes.ver'), 403);

        return app(CatalogoReportService::class)->pdfModelos();
    }

    public function pdfPaises()
    {
        abort_unless(auth()->user()->can('reportes.ver'), 403);

        return app(CatalogoReportService::class)->pdfPaises();
    }

    // === Nóminas RRHH (requiere reportes-nomina.ver) ===

    public function pdfSalarioPrenomina(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfPrenomina($request);
    }

    public function pdfSalarioChoferes(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfSalarioChoferes($request);
    }

    public function pdfPrenominaChoferes(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfPrenomina($request->merge(['tipo' => 'choferes']));
    }

    public function pdfPrenominaAdministrativo(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfPrenomina($request->merge(['tipo' => 'administrativo']));
    }

    public function pdfCumpleanos(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfCumpleanos($request);
    }

    public function pdfLicenciaConduccion(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfLicenciaConduccion($request);
    }

    public function pdfAdicionales(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfAdicionales($request);
    }

    public function pdfNocturnidad(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfNocturnidad($request);
    }

    public function pdfPagoAdministrativo(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfPagoAdministrativo($request);
    }

    public function pdfResumenTiemposChoferes(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfResumenTiemposChoferes($request);
    }

    public function pdfAnalisisSalarioTransportacion(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfAnalisisSalarioTransportacion($request);
    }

    public function pdfControlDiarioAdministrativo(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfControlDiarioAdministrativo($request);
    }

    public function pdfControlDiarioChoferes(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfControlDiarioChoferes($request);
    }

    public function exportarVersat(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->exportarVersat($request);
    }

    public function pdfIncidencias(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfIncidencias($request);
    }

    public function pdfModelo1(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfModelo1($request);
    }

    public function excelPrenominaChoferes(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->excelPrenomina($request->merge(['tipo' => 'choferes']));
    }

    public function excelPrenominaAdministrativo(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->excelPrenomina($request->merge(['tipo' => 'administrativo']));
    }

    public function excelModelo1(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->excelModelo1($request);
    }

    // === Facturación (requiere reportes-ingresos.ver) ===

    public function pdfIngresosTractivos(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-ingresos.ver'), 403);

        return app(IngresosReportService::class)->ingresosPorTractivos($request->all());
    }

    public function pdfIngresosChoferes(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-ingresos.ver'), 403);

        return app(IngresosReportService::class)->ingresosPorChoferes($request->all());
    }

    public function pdfFactura(int $id)
    {
        abort_unless(auth()->user()->can('facturas.ver'), 403);

        return app(FacturaReportService::class)->pdfFactura($id);
    }

    public function pdfPrefactura(int $id)
    {
        abort_unless(auth()->user()->can('prefacturas.ver'), 403);

        return app(FacturaReportService::class)->pdfPrefactura($id);
    }

    public function pdfCartaPorte(int $id)
    {
        abort_unless(auth()->user()->can('carta-porte.ver'), 403);

        return app(CartaPorteReportService::class)->pdfCartaPorte($id);
    }

    public function pdfHojaRuta(int $id)
    {
        abort_unless(auth()->user()->can('hojas-ruta.ver'), 403);

        return app(HojaRutaReportService::class)->pdfHojaRuta($id);
    }

    public function pdfAforo(int $id)
    {
        abort_unless(auth()->user()->can('facturas.ver'), 403);

        return app(AforoReportService::class)->pdfAforo($id);
    }

    // === Módulo Técnico (requiere reportes-tecnico.ver) ===

    public function pdfPlanBajasNeumaticos(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-tecnico.ver'), 403);

        return app(PlanBajasNeumaticoReportService::class)
            ->pdfPlanBajas($request->integer('tipo', 1), (int) entidadActivaId() ?: null);
    }

    public function pdfControlLubricante(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-tecnico.ver'), 403);

        return app(ControlLubricanteReportService::class)
            ->pdfControlLubricante($request->input('desde'), $request->input('hasta'), (int) entidadActivaId() ?: null);
    }

    public function pdfOrdenTaller(int $id)
    {
        abort_unless(auth()->user()->can('reportes-tecnico.ver'), 403);

        return app(OrdenTallerReportService::class)->pdfOrdenTaller($id);
    }
}
