<?php

namespace App\Http\Controllers;

use App\Exports\CertificacionComercialExport;
use App\Exports\CertificacionTnkmsExport;
use App\Exports\OperacionesEmcargaExport;
use App\Models\Aforo;
use App\Models\CartaPorte;
use App\Models\CatalogoItem;
use App\Models\Entidad;
use App\Models\HojasRuta;
use App\Services\Reports\AforoReportService;
use App\Services\Reports\CartaPorteReportService;
use App\Services\Reports\CatalogoReportService;
use App\Services\Reports\ControlLubricanteReportService;
use App\Services\Reports\FacturaReportService;
use App\Services\Reports\HojaRutaReportService;
use App\Services\Reports\ImpresionCoordenadasService;
use App\Services\Reports\IngresosReportService;
use App\Services\Reports\NominaReportService;
use App\Services\Reports\OrdenTallerReportService;
use App\Services\Reports\PlanBajasNeumaticoReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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

    public function pdfPenalizaciones(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfPenalizaciones($request);
    }

    public function pdfDatosTrabajadores(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfDatosTrabajadores($request);
    }

    public function pdfGarantiaSalarial(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfGarantiaSalarial($request);
    }

    public function pdfGarantiaChoferes(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfGarantiaChoferes($request);
    }

    public function pdfResumenConceptosAdmin(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfResumenConceptosAdmin($request);
    }

    public function pdfCertificacionComercial(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfCertificacionComercial($request);
    }

    public function excelCertificacionComercial(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        $mes = (int) $request->input('mes', now()->format('m'));
        $ano = (int) $request->input('ano', now()->format('Y'));
        $entidadId = (int) session('entidad_activa_id') ?: null;
        $mesPad = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);

        return Excel::download(
            new CertificacionComercialExport($mes, $ano, $entidadId),
            'certificacion_comercial_'.$ano.'-'.$mesPad.'.xlsx'
        );
    }

    public function pdfCertificacionTnkms(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfCertificacionTnkms($request);
    }

    public function excelCertificacionTnkms(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        $mes = (int) $request->input('mes', now()->format('m'));
        $ano = (int) $request->input('ano', now()->format('Y'));
        $entidadId = (int) session('entidad_activa_id') ?: null;
        $mesPad = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);

        return Excel::download(
            new CertificacionTnkmsExport($mes, $ano, $entidadId),
            'certificacion_tnkms_'.$ano.'-'.$mesPad.'.xlsx'
        );
    }

    public function pdfPrenominaResultados(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfPrenominaResultados($request);
    }

    public function pdfAlmacenamiento(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfAlmacenamiento($request);
    }

    public function pdfResumenDietas(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfResumenDietas($request);
    }

    public function pdfCertificacionCdt(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        return app(NominaReportService::class)->pdfCertificacionCdt($request);
    }

    /**
     * #4060 REPORTE DE OPERACIONES EMCARGA (Excel detallado por aforo).
     */
    public function excelOperacionesEmcarga(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-ingresos.ver'), 403);

        $mes = (int) $request->input('mes', now()->format('m'));
        $ano = (int) $request->input('ano', now()->format('Y'));
        $entidadId = (int) session('entidad_activa_id') ?: null;
        $entidades = $entidadId ? Entidad::idsPermitidos($entidadId) : [];

        $mesPad = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);

        return Excel::download(
            new OperacionesEmcargaExport($mes, $ano, $entidades),
            'operaciones_emcarga_'.$ano.'-'.$mesPad.'.xlsx'
        );
    }

    /**
     * #91 SALARIOS X SISTEMA DE PAGO (legacy pdf_salario_prenomina_sistema):
     * sistema 2/3 (choferes) → prenómina de choferes; resto → administrativa.
     */
    public function pdfSalariosSistema(Request $request)
    {
        abort_unless(auth()->user()->can('reportes-nomina.ver'), 403);

        $idSistema = (int) $request->input('sistema', 0);
        $origen = CatalogoItem::where('id', $idSistema)->value('origen_id');

        if (in_array((int) $origen, [2, 3], true)) {
            return app(NominaReportService::class)->pdfPrenominaChoferes($request);
        }

        return app(NominaReportService::class)->pdfPrenominaAdministrativo($request);
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

    // === Impresión sobre formato impreso (pre-impreso), coordenadas configurables ===

    public function pdfCpEmision(int $id)
    {
        abort_unless(auth()->user()->can('carta-porte.ver'), 403);

        $carta = CartaPorte::findOrFail($id);

        return app(ImpresionCoordenadasService::class)->pdfCpEmision($carta);
    }

    public function pdfCpAforo(int $id)
    {
        abort_unless(auth()->user()->can('facturas.ver'), 403);

        $aforo = Aforo::findOrFail($id);

        return app(ImpresionCoordenadasService::class)->pdfCpAforo($aforo);
    }

    public function pdfHrEmision(int $id)
    {
        abort_unless(auth()->user()->can('hojas-ruta.ver'), 403);

        $hoja = HojasRuta::findOrFail($id);

        return app(ImpresionCoordenadasService::class)->pdfHrEmision($hoja);
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
