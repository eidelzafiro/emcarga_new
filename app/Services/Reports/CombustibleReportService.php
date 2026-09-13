<?php

namespace App\Services\Reports;

use App\Models\CombustibleCarga;
use App\Models\CombustibleDescarga;
use App\Models\Entidad;
use App\Models\Tractivo;
use App\Models\Tarjeta;
use App\Services\Reports\Fpdf\CombustibleFpdfReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fase B — COMBUSTIBLE (21 reportes legacy). Versiones funcionales sobre
 * combustible_cargas/descargas, tarjetas y tractivos.
 */
class CombustibleReportService extends BaseReportService
{
    protected function mesFiltro(array $filtros): ?string
    {
        if (empty($filtros['mes'])) return null;
        try { return Carbon::parse($filtros['mes'])->format('Y-m'); } catch (\Exception) { return null; }
    }

    protected function rangoFiltros(array $filtros): array
    {
        $d = $filtros['desde'] ?? null;
        $h = $filtros['hasta'] ?? null;
        if (! $d && ! $h && ! empty($filtros['mes'])) {
            try { $m = Carbon::parse($filtros['mes']); $d = $m->copy()->startOfMonth()->toDateString(); $h = $m->copy()->endOfMonth()->toDateString(); } catch (\Exception) {}
        }
        return [$d, $h];
    }

    /**
     * Construye el generador FPDF de reportes de combustible (layout legacy
     * exacto) con la entidad activa y la fecha de operaciones de la sesión.
     */
    private function fpdf(string $orientation, string $paper): CombustibleFpdfReport
    {
        $entidadId = (int) entidadActivaId();
        $entidad = $entidadId ? Entidad::find($entidadId) : null;
        $ids = $entidadId ? Entidad::idsPermitidos($entidadId) : [23];

        return new CombustibleFpdfReport(
            $orientation,
            $paper,
            $entidad,
            Entidad::query()->count() > 1,
            (string) (session('fecha_operaciones') ?? now()->toDateString()),
            $ids,
        );
    }

    // 28 · PARTE RESUMEN DIFERENCIAS CON INDICADORES
    public function indicadoresDiferencias(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfIndicadoresDiferencias($this->mesFiltro($filtros));
    }

    // 23 · CONCILIACION COMBUSTIBLE (HABILITADO SEGUN HR / CONTABILIDAD)
    public function conciliacionCombustible(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfCombustibleConciliacion($this->mesFiltro($filtros));
    }

    // 39 · RESUMEN CARGAS DEL MES
    public function cargasResumen(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfResumenCargas($this->mesFiltro($filtros));
    }

    // 40 · SUBMAYOR TARJETAS COMBUSTIBLE (TODAS)
    public function submayorTodas(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfSubmayorTodas($this->mesFiltro($filtros));
    }

    // 42 · SUBMAYOR TARJETAS COMBUSTIBLE (DETALLE)
    public function submayorDetalle(array $filtros): \Illuminate\Http\Response
    {
        $id = $filtros['tarjeta'] ?? null;

        return $this->fpdf('L', 'Letter')->pdfSubmayor($id !== null ? (int) $id : null, $this->mesFiltro($filtros));
    }

    // 41 · DETALLES DESCARGAS P/TECNICA
    public function descargasTecnica(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfDescargasTecnica($filtros['fecha'] ?? null);
    }

    // 43 · TARJETAS CON SALDO
    public function tarjetasConSaldo(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfTarjetasSaldo(true);
    }

    // 44 / 45 · TARJETAS SIN SALDO
    public function tarjetasSinSaldo(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfTarjetasSaldo(false);
    }

    // 46 · TARJETAS X RESPONSABLE
    public function tarjetasResponsable(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfTarjetasResponsable();
    }

    // 113 · RESUMEN DESCARGAS X VARIABLES
    public function descargasVariables(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfCombustibleResumen(
            $this->mesFiltro($filtros),
            $filtros['descargas1'] ?? null,
        );
    }

    // 114 · RESUMEN DESCARGAS POR GRUPO-FECHAS
    public function descargasGrupo(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfDescargasGrupoFechas($this->mesFiltro($filtros));
    }

    // 126 · RESUMEN TARJETAS DEL MES
    public function tarjetasResumen(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfResumenTarjetas($this->mesFiltro($filtros));
    }

    // 133 · TARJETAS SIN MOVIMIENTO EN 3 DIAS
    public function tarjetas3Dias(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfTarjetas3Dias();
    }

    // 377 · PARTE DIARIO EXISTENCIAS COMBUSTIBLE
    public function parteExistencias(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfParteExistencias($filtros['fecha'] ?? null);
    }

    // 1001 · TARJETAS FECHA VENCIMIENTO
    public function tarjetasVencimiento(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfTarjetasVencimiento($this->mesFiltro($filtros));
    }

    // 1011 · TRACTIVOS CON TANQUE DE COMBUSTIBLE
    public function tractivosTanque(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfTractivosTanque();
    }

    // 1012 · DESCARGAS SUPERIOR AL TANQUE
    public function descargasSuperior(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfDescargasSuperior($this->mesFiltro($filtros));
    }

    // 1013 · DESCARGAS EQUIPOS EN UNA TARJETA
    public function descargasEquiposTarjeta(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfDescargasEquiposTarjeta($this->mesFiltro($filtros));
    }

    // 1020 · ANALISIS COMPORTAMIENTO TARJETAS (ONURE-TODAS)
    public function onureTodas(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Legal')->pdfOnureSubmayorTodas($this->mesFiltro($filtros));
    }

    // 1021 · ANALISIS COMPORTAMIENTO TARJETAS (ONURE-DETALLE)
    public function onureDetalle(array $filtros): \Illuminate\Http\Response
    {
        $id = $filtros['tarjeta'] ?? null;

        return $this->fpdf('L', 'Legal')->pdfOnureSubmayor($id !== null ? (int) $id : null, $this->mesFiltro($filtros));
    }

    // 1023 · ANALISIS COMPORTAMIENTO TARJETAS (VALIDACION)
    public function validacion(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfValidacionOnure($this->mesFiltro($filtros));
    }

    // 1031 · ANALISIS COMPORTAMIENTO TARJETAS (VALIDACION-EQUIPOS)
    public function validacionEquipos(array $filtros): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfValidacionOnure2($this->mesFiltro($filtros));
    }
}
