<?php

namespace App\Services\Reports;

use App\Models\Entidad;
use App\Services\Reports\Fpdf\DocumentosFpdfReport;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reportes del grupo DOCUMENTOS del legacy (`Reportes2.php`): cartas de porte y
 * hojas de ruta. Delega en `DocumentosFpdfReport`, que reproduce el layout
 * exacto del legacy (FPDF, coordenadas, anchos, firmas y pie de página).
 *
 * Variables legacy:
 *   - `mes`         → mes de emisión (YYYY-MM)
 *   - `fecha`       → fecha única (parte diario)
 *   - `consecutivo` → rango de folios [desde, hasta]
 */
class DocumentosReportService extends BaseReportService
{
    // =====================================================================
    // CARTAS DE PORTE
    // =====================================================================

    public function cartaPorteCanceladas(array $filtros): Response
    {
        return $this->report('P', 'Letter')->pdfCpCancelada($this->mesDeFiltro($filtros));
    }

    public function cartaPorteConsecutivo(array $filtros): Response
    {
        [$inicio, $fin] = $this->rangoConsecutivo($filtros);

        return $this->report('L', 'Legal')->pdfCpConsecutivo($inicio, $fin);
    }

    public function cartaPorteControlEstado(array $filtros): Response
    {
        return $this->report('L', 'Letter')->pdfCpEstado($this->mesDeFiltro($filtros));
    }

    public function cartaPorteParteDiarioEmision(array $filtros): Response
    {
        return $this->report('L', 'Letter')->pdfCpPdEmision($this->fechaDeFiltro($filtros));
    }

    public function cartaPorteParteDiarioRecepcion(array $filtros): Response
    {
        return $this->report('P', 'Letter')->pdfCpRecepcion($this->fechaDeFiltro($filtros));
    }

    public function cartaPorteRegistroRes2132019(array $filtros): Response
    {
        return $this->report('L', 'Legal')->pdfCpFolios($this->mesDeFiltro($filtros));
    }

    // =====================================================================
    // HOJAS DE RUTA
    // =====================================================================

    public function hojaRutaCanceladas(array $filtros): Response
    {
        return $this->report('P', 'Letter')->pdfHrCancelada($this->mesDeFiltro($filtros));
    }

    public function hojaRutaConsecutivo(array $filtros): Response
    {
        [$inicio, $fin] = $this->rangoConsecutivo($filtros);

        return $this->report('L', 'Letter')->pdfHrConsecutivo($inicio, $fin);
    }

    public function hojaRutaControlEstado(array $filtros): Response
    {
        return $this->report('L', 'Letter')->pdfHrEstado($this->mesDeFiltro($filtros));
    }

    public function hojaRutaParteDiarioCierre(array $filtros): Response
    {
        return $this->report('L', 'Letter')->pdfHrPdCierre($this->fechaDeFiltro($filtros));
    }

    public function hojaRutaParteDiarioEmision(array $filtros): Response
    {
        return $this->report('P', 'Letter')->pdfHrPdEmision($this->fechaDeFiltro($filtros));
    }

    public function hojaRutaRegistroRes184(array $filtros): Response
    {
        return $this->report('L', 'Letter')->pdfHrFolios($this->mesDeFiltro($filtros));
    }

    public function hojaRutaAnalisisDocumentacion(array $filtros): Response
    {
        return $this->report('P', 'Letter')->pdfHrAnalisis($this->mesDeFiltro($filtros));
    }

    // =====================================================================
    // Utilidades
    // =====================================================================

    private function report(string $orientation, string $paper): DocumentosFpdfReport
    {
        $entidadId = (int) entidadActivaId();
        $entidad = $entidadId ? Entidad::find($entidadId) : null;
        $ids = $entidadId ? Entidad::idsPermitidos($entidadId) : [23];

        return new DocumentosFpdfReport(
            $orientation,
            $paper,
            $entidad,
            Entidad::query()->count() > 1,
            (string) (session('fecha_operaciones') ?? now()->toDateString()),
            $ids,
        );
    }

    private function mesDeFiltro(array $filtros): string
    {
        return (string) ($filtros['mes'] ?? '');
    }

    private function fechaDeFiltro(array $filtros): string
    {
        $fecha = $filtros['fecha'] ?? $filtros['desde'] ?? null;
        if (! $fecha) {
            return (string) (session('fecha_operaciones') ?? now()->toDateString());
        }

        try {
            return \Carbon\Carbon::parse($fecha)->toDateString();
        } catch (\Exception) {
            return (string) (session('fecha_operaciones') ?? now()->toDateString());
        }
    }

    private function rangoConsecutivo(array $filtros): array
    {
        $inicio = (int) ($filtros['consecutivo_desde'] ?? $filtros['consecutivo'] ?? 0);
        $fin = (int) ($filtros['consecutivo_hasta'] ?? $inicio);
        if ($fin < $inicio) {
            $fin = $inicio;
        }

        return [$inicio, $fin];
    }
}
