<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * CERTIFICACION CHOFERES AREA COMERCIAL — TONELADAS-KILOMETROS (tramos).
 * Legacy Reportes2::pdf_chofer_certificacion_tnskms +
 * ModAforo::reporte_choferes_tnkms (reportes #1072/#1073).
 *
 * Por chofer: ingresos por tramos de distancia (1-30, 31-190, 191-350, 351+)
 * y total. Formato: Letter horizontal.
 */
class CertificacionTnkmsFpdfReport extends ReportesnewFpdfBase
{
    public function generate(): string
    {
        $data = app(ReportePrenominaService::class)
            ->certificacionTnkms((int) $this->mes, (int) $this->ano, $this->entidadId);

        $titulo = 'CERTIFICACION CHOFERES AREA COMERCIAL (TN-KM)';

        $campos = [
            ['titulo' => 'VERSAT',   'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'CHOFER',   'ancho' => 70, 'direccion' => 'C'],
            ['titulo' => '$ 1-30',   'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => '$ 31-190', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => '$ 191-350', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => '$ 351+',   'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => '$ TOTAL',  'ancho' => 35, 'direccion' => 'C'],
        ];

        $registros = $data['registros'] ?? [];

        if (empty($registros)) {
            $this->inicio($this->latin1($titulo), 50, 5);
            $this->SetFont('Arial', 'B', 20);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');

            return $this->Output('S');
        }

        $this->inicio($this->latin1($titulo), 50, 5);
        $this->titulos($campos, [], [], 6, 10, 30);

        $posY = 36;
        $max = 21;
        $i = 0;

        foreach ($registros as $r) {
            if ($i >= $max) {
                $this->inicio($this->latin1($titulo), 50, 5);
                $this->titulos($campos, [], [], 6, 10, 35);
                $posY = 45;
                $i = 0;
            }

            $this->SetFont('Arial', '', 10);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, $posY);
            $this->Cell(25, 6, $this->latin1((string) $r['versat']), 1, 0, 'C', 1);
            $this->Cell(70, 6, $this->latin1(ucwords(mb_strtolower((string) $r['nombrecompleto']))), 1, 0, 'L', 1);
            $this->Cell(30, 6, $this->fmtVar($r['t1'], 2), 1, 0, 'R', 1);
            $this->Cell(30, 6, $this->fmtVar($r['t2'], 2), 1, 0, 'R', 1);
            $this->Cell(30, 6, $this->fmtVar($r['t3'], 2), 1, 0, 'R', 1);
            $this->Cell(30, 6, $this->fmtVar($r['t4'], 2), 1, 0, 'R', 1);
            $this->Cell(35, 6, $this->fmtVar($r['total'], 2), 1, 0, 'R', 1);

            $posY += 6;
            $i++;
        }

        return $this->Output('S');
    }
}
