<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * CERTIFICO DEL CUMPLIMIENTO CDT X TRACTIVOS — réplica FPDF del legacy.
 * Reportestec.php (pdf_certifico_cdt_tractivos) + ModTaller::cdt_tractivos.
 *
 * Por tractivo: plan CDT vs CDT real (horas disponibles del mes) y su %.
 * Columnas: # INV(25), MARCA(40), MODELO(25), PLAN(20), REAL(20), %(20).
 * Formato: Letter vertical.
 */
class CertificacionCdtFpdfReport extends ReportesnewFpdfBase
{
    public function generate(): string
    {
        $data = app(ReportePrenominaService::class)
            ->cdtTractivos((int) $this->mes, (int) $this->ano, $this->entidadId);

        $titulo = 'CERTIFICO DEL CUMPLIMIENTO CDT X TRACTIVOS';

        $campos = [
            ['titulo' => '# INV',  'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'MARCA',  'ancho' => 40, 'direccion' => 'L'],
            ['titulo' => 'MODELO', 'ancho' => 25, 'direccion' => 'L'],
            ['titulo' => 'PLAN',   'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'REAL',   'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => '%',      'ancho' => 20, 'direccion' => 'C'],
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
        $this->titulos($campos, [], [], 10, 15, 35);

        $posY = 45;
        $max = 31;
        $i = 0;
        $nro = 1;

        foreach ($registros as $r) {
            if ($i >= $max) {
                $this->inicio($this->latin1($titulo), 50, 5);
                $this->titulos($campos, [], [], 10, 15, 35);
                $posY = 45;
                $i = 0;
            }

            $this->SetFont('Arial', '', 10);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(15, $posY);
            $this->Cell(25, 7, $this->latin1((string) $r['codigo']), 1, 0, 'L', 1);
            $this->Cell(40, 7, $this->latin1((string) $r['marca']), 1, 0, 'L', 1);
            $this->Cell(25, 7, $this->latin1(mb_substr((string) $r['modelo'], 0, 10)), 1, 0, 'L', 1);
            $this->Cell(20, 7, $this->fmtVar($r['plan'], 0), 1, 0, 'C', 1);
            $this->Cell(20, 7, $this->fmtVar($r['real'], 2), 1, 0, 'C', 1);
            $this->Cell(20, 7, $this->fmtVar($r['cumplimiento'], 2), 1, 0, 'C', 1);

            $posY += 7;
            $i++;
            $nro++;
        }

        return $this->Output('S');
    }
}
