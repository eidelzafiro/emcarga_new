<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * RESUMEN DE SALARIOS ADMINISTRATIVOS — réplica FPDF del legacy.
 * Reportes.php (pdf_salario_resumen_concepto_admin, reporte #1066).
 *
 * Dos bloques de totales por concepto: BASE DE CALCULO (escala, cla,
 * reubicación, feriado) y NO INCLUIDOS (tiempo extra, doblaje, nocturnidad,
 * maestría), más el TOTAL GENERAL. Formato: Letter vertical.
 */
class ResumenConceptosAdminFpdfReport extends ReportesnewFpdfBase
{
    public function generate(): string
    {
        $d = app(ReportePrenominaService::class)
            ->resumenConceptosAdmin((int) $this->mes, (int) $this->ano, $this->entidadId);

        $titulo = 'RESUMEN DE SALARIOS ADMINISTRATIVOS';

        $this->inicio($this->latin1($titulo), 50, 5);
        $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'P');

        $col1 = 130;
        $x = 15;
        $y = 46;

        $fila = function (string $label, float $importe, bool $bold = false, bool $cabecera = false) use (&$y, $x, $col1): void {
            $this->SetXY($x, $y);
            $this->SetFont('Arial', $cabecera ? 'B' : '', 10);
            $this->SetFillColor($cabecera ? $this->getFillColor() : 999);
            $this->Cell($col1, 9, $this->latin1($label), 1, 0, 'L', 1);
            $this->SetFont('Arial', $bold ? 'B' : '', 10);
            $this->Cell(50, 9, $cabecera ? '' : $this->fmtVar($importe, 2), 1, 0, 'R', 1);
            $y += 9;
        };

        // Cabecera.
        $this->SetXY($x, $y);
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor($this->getFillColor());
        $this->Cell($col1, 9, 'CONCEPTO DE SALARIO', 1, 0, 'C', 1);
        $this->Cell(50, 9, 'IMPORTE', 1, 0, 'C', 1);
        $y += 9;

        $fila('SALARIO BASE DE CALCULO', 0, true, true);
        $fila('ESCALA', (float) $d['se']);
        $fila('CLA', (float) $d['scla']);
        $fila('REUBICACION LABORAL', 0);
        $fila('FERIADO TRABAJADO', 0);
        $fila('TOTAL SALARIO BASE DE CALCULO', (float) $d['sbc'], true);

        $y += 8;
        $fila('SALARIO QUE NO SE INCLUYEN EN LA BASE DE CALCULO', 0, true, true);
        $fila('TIEMPO EXTRA', (float) $d['sextra']);
        $fila('DOBLAJE', 0);
        $fila('NOCTURNIDAD', (float) $d['snoct']);
        $fila('MAESTRIA', (float) $d['smaestria']);
        $fila('TOTAL SALARIO QUE NO SE INCLUYE EN LA BASE DE CALCULO', (float) $d['snbc'], true);

        $y += 8;
        $fila('TOTAL GENERAL CONCEPTOS DEL SALARIO', (float) $d['st'], true, true);

        return $this->Output('S');
    }

    private function getFillColor(): int
    {
        return (int) (session('FillColor') ?? 200);
    }
}
