<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * SC-4-05 CONTROL DIARIO TIEMPO DE TRABAJO CHOFERES DE TRANSPORTACION
 * (reporte 11) — réplica FPDF de Reportes.php:3828
 * (pdf_salario_control_diario_choferes). Igual al reporte 10 pero:
 * DT = días trabajados ('T') del mes, sin filas de área, max 180.
 */
class ControlDiarioChoferesFpdfReport extends ControlDiarioAdministrativoFpdfReport
{
    public function __construct(?int $entidadId, string $mes, string $ano)
    {
        parent::__construct($entidadId, $mes, $ano);
    }

    public function generate(): string
    {
        $service = app(ReportePrenominaService::class);
        $data = $service->controlDiarioChoferes((int) $this->mes, (int) $this->ano, $this->entidadId);

        $titulo = 'SC-4-05 CONTROL DIARIO TIEMPO DE TRABAJO CHOFERES DE TRANSPORTACION';
        $dias = \Carbon\Carbon::createFromDate((int) $this->ano, (int) $this->mes, 1)->daysInMonth;

        $registros = $data['registros'] ?? [];

        if (empty($registros)) {
            $this->inicio($this->latin1($titulo), 50, 5);
            $this->SetFont('Arial', 'B', 35);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
            return $this->Output('S');
        }

        $this->paginaBase($titulo, $dias);

        $posY = 51;
        $max = 180;

        foreach ($registros as $r) {
            if ($posY >= $max) {
                $this->paginaBase($titulo, $dias);
                $posY = 51;
            }

            $posY = $this->filaTrabajador($r, $posY, $dias);
        }

        return $this->Output('S');
    }

    /**
     * En choferes la celda DT muestra los días trabajados del mes.
     */
    protected function celdaDt(array $r): string
    {
        return (string) ($r['dias_trabajados'] ?? '');
    }
}
