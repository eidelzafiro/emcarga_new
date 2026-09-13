<?php

namespace App\Services\Reports;

use App\Models\Entidad;
use App\Services\Reports\Fpdf\FacturacionFpdfReport;

/**
 * Reportes agrupados del módulo FACTURACIÓN replicando el layout exacto del
 * legacy `Reportes2.php`. Delega en `FacturacionFpdfReport`.
 */
class FacturacionAgrupadaService extends BaseReportService
{
    private function fpdf(string $orientation, string $paper): FacturacionFpdfReport
    {
        $entidadId = (int) entidadActivaId();
        $entidad = $entidadId ? Entidad::find($entidadId) : null;
        $ids = $entidadId ? Entidad::idsPermitidos($entidadId) : [23];

        return new FacturacionFpdfReport(
            $orientation,
            $paper,
            $entidad,
            Entidad::query()->count() > 1,
            (string) (session('fecha_operaciones') ?? now()->toDateString()),
            $ids,
        );
    }

    // 13 — IMPRESION DE FACTURAS (una factura)
    public function facturasImpresion(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfFacturaImpresion((int) ($f['factura'] ?? 0));
    }

    // 14 — LISTADO CARTAS DE PORTE A FACTURAR
    public function cartasPorteAFacturar(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfCartasPorteAFacturar($f['fecha'] ?? null);
    }

    // 16 — REGISTRO DE FACTURACION DEL MES
    public function registroFacturacionMes(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'A4')->pdfRegistroFacturacionMes($f['mes'] ?? null);
    }

    // 17 — RESUMEN MENSUAL FACTURACION CLIENTES
    public function resumenMensualClientes(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfResumenMensual($f['mes'] ?? null, 'clientes');
    }

    // 18 — RESUMEN MENSUAL FACTURACION ORGANISMOS
    public function resumenMensualOrganismos(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfResumenMensual($f['mes'] ?? null, 'abreviatura');
    }

    // 59 — IMPRESION DE COMPROBANTES
    public function impresionComprobantes(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfImpresionComprobantes($f['fecha'] ?? null);
    }

    // 1003 — FACTURAS FIRMADAS POR CLIENTES
    public function facturasFirmadasClientes(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfFacturasFirmadas($f['mes'] ?? null, 1);
    }

    // 1005 — FACTURAS PENDIENTES DE FIRMA POR CLIENTES
    public function facturasPendientesFirma(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfFacturasFirmadas($f['mes'] ?? null, 3);
    }

    // 1007 — LISTADO DE CONCILIACIONES
    public function listadoConciliaciones(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfListadoConciliaciones($f['mes1'] ?? null);
    }

    // 4069 — RESUMEN MENSUAL FACTURACION CLIENTES OTRAS VENTAS
    public function resumenMensualClientesOtrasVentas(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfResumenMensual($f['mes'] ?? null, 'clientesov');
    }
}
