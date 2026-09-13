<?php

namespace App\Services\Reports;

use App\Models\Entidad;
use App\Services\Reports\Fpdf\TecnicaFpdfReport;

/**
 * Reportes agrupados del módulo TÉCNICA (Técnica, Baterías, Neumáticos y
 * Control Taller) replicando el layout exacto del legacy `Reportestec.php`.
 * Delega en `TecnicaFpdfReport`.
 */
class TecnicaAgrupadaService extends BaseReportService
{
    private function fpdf(string $orientation, string $paper): TecnicaFpdfReport
    {
        $entidadId = (int) entidadActivaId();
        $entidad = $entidadId ? Entidad::find($entidadId) : null;
        $ids = $entidadId ? Entidad::idsPermitidos($entidadId) : [23];

        return new TecnicaFpdfReport(
            $orientation,
            $paper,
            $entidad,
            Entidad::query()->count() > 1,
            (string) (session('fecha_operaciones') ?? now()->toDateString()),
            $ids,
        );
    }

    // === TECNICA ===
    public function informeEstadoParque(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfInformeEstadoParque($f['mes'] ?? null);
    }

    public function informeAnualTractivo(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfInformeAnualTractivo($f['tractivo'] ?? null);
    }

    public function situacionParque(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfSituacionParque($f['taller'] ?? null);
    }

    public function controlCdt(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfControlCdt($f['mes'] ?? null);
    }

    public function disponibilidadKms(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfDisponibilidadKms();
    }

    public function indicesDeteriorados(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfIndicesDeteriorados($f['mes'] ?? null);
    }

    public function disponibilidadVayas(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfDisponibilidadVayas($f['mes'] ?? null);
    }

    public function operacionesTallerOperarios(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfOperacionesTallerOperarios($f['mes'] ?? null, $f['optaller'] ?? null);
    }

    public function gastoLubricantes(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfGastoLubricantes($f['mes'] ?? null);
    }

    public function conciliacionDisponibilidad(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfConciliacionDisponibilidad();
    }

    public function certificoIndicesConsumo(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfCertificoIndicesConsumo($f['tractivo'] ?? null);
    }

    // === CONTROL TALLER ===
    public function ct2MttoEventuales(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfCt2MttoEventuales($f['tractivo'] ?? null);
    }

    public function ct5MovimientoMes(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfCt5MovimientoMes($f['mes'] ?? null);
    }

    public function ct8VidaUtilBateria(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfCt8VidaUtilBateria();
    }

    public function ct3TiempoAgregados(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfCt3TiempoAgregados($f['mes'] ?? null);
    }

    public function ct1Expediente(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfCt1Expediente($f['tractivo'] ?? null);
    }

    public function ct7ControlLubricantes(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfCt7ControlLubricantes($f['mes'] ?? null);
    }

    public function ct6AnalisisMotores(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfCt6AnalisisMotores($f['motores'] ?? null);
    }

    public function ct4ReparacionMantenimiento(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfCt4ReparacionMantenimiento($f['ordenes'] ?? null);
    }

    public function ct5MovimientoFecha(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfCt5MovimientoFecha($f['fecha'] ?? null);
    }

    public function datosGeneralesParque(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfDatosGeneralesParque();
    }

    public function crtCirculacion(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfCrtCirculacion();
    }

    public function mttosExterior(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfMttosExterior($f['mes'] ?? null);
    }

    // === BATERIAS ===
    public function controlActivasXTractivos(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfControlBateriasActivas();
    }

    public function planBajas(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfPlanBajasBaterias($f['mes'] ?? null);
    }

    public function informacionBaterias(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfInformacionBaterias($f['mes'] ?? null);
    }

    // === NEUMATICOS ===
    public function planBajasRecauches(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfPlanBajasRecauches($f['mes'] ?? null);
    }

    public function informacionNeumaticos(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfInformacionNeumaticos($f['mes'] ?? null);
    }

    public function cn2Neumatico(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfCn2Neumatico($f['neumatico'] ?? null);
    }

    public function cn3AnalisisBaja(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('L', 'Letter')->pdfCn3AnalisisBaja($f['mes'] ?? null);
    }

    public function listadoBaja(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfListadoNeumaticosBaja($f['mes'] ?? null);
    }

    public function activosXTractivos(array $f): \Illuminate\Http\Response
    {
        return $this->fpdf('P', 'Letter')->pdfNeumaticosActivosXTractivos();
    }
}
