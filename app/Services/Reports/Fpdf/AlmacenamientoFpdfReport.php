<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * CERTIFICO DE INGRESOS POR ALMACENAMIENTO — réplica FPDF del legacy.
 * Reportes2.php (pdf_certifico_almacenamiento) +
 * ModAforo::reporte_choferes_almacenamiento.
 *
 * Por chofer de transportación: importe por almacenamiento del mes.
 * Columnas: VERSAT(20), CHOFER(80), IMPORTE(30), % A PAGAR(30).
 * Formato: Letter vertical.
 */
class AlmacenamientoFpdfReport extends ReportesnewFpdfBase
{
    public function generate(): string
    {
        $data = app(ReportePrenominaService::class)
            ->almacenamientoChoferes((int) $this->mes, (int) $this->ano, $this->entidadId);

        $titulo = 'CERTIFICO MENSUAL DE INGRESOS POR ALMACENAMIENTO';

        $campos = [
            ['titulo' => 'VERSAT',    'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'CHOFER',    'ancho' => 80, 'direccion' => 'C'],
            ['titulo' => 'IMPORTE',   'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => '% A PAGAR', 'ancho' => 30, 'direccion' => 'C'],
        ];

        $registros = $data['registros'] ?? [];

        if (empty($registros)) {
            $this->inicio($this->latin1($titulo), 50, 5);
            $this->SetFont('Arial', 'B', 18);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');

            return $this->Output('S');
        }

        $this->inicio($this->latin1($titulo), 50, 5);
        $this->titulos($campos, [], [], 10, 10, 30);

        $posY = 40;
        $max = 30;
        $i = 0;

        foreach ($registros as $r) {
            if ($i >= $max) {
                $this->inicio($this->latin1($titulo), 50, 5);
                $this->titulos($campos, [], [], 10, 10, 35);
                $posY = 45;
                $i = 0;
            }

            $this->SetFont('Arial', 'B', 11);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, $posY);
            $this->Cell(20, 8, $this->latin1((string) $r['versat']), 1, 0, 'R', 1);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(80, 8, $this->latin1(ucwords(mb_strtolower((string) $r['nombrecompleto']))), 1, 0, 'L', 1);
            $this->Cell(30, 8, $this->fmtVar($r['importe'], 2), 1, 0, 'R', 1);
            $this->Cell(30, 8, '', 1, 0, 'R', 1);

            $posY += 8;
            $i++;
        }

        $this->SetXY(10, $posY);
        $this->SetFillColor(999, 999, 999);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(100, 10, 'TOTAL', 1, 0, 'L', 1);
        $this->Cell(30, 10, $this->fmtVar($data['total'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(30, 10, '', 1, 0, 'R', 1);

        return $this->Output('S');
    }
}
