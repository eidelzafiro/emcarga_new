<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * RESUMEN DE GASTOS DE DIETAS — réplica FPDF del legacy.
 * Reportes2.php (pdf_contabilidad_dietas_resumen) + ModDietas::mostrar_dietas_resumen.
 *
 * Agrupa las dietas del mes por trabajador. Columnas: NRO(15),
 * NOMBRE TRABAJADOR(115), CANT(30), IMPORTE(30). Formato: Letter vertical.
 */
class ResumenDietasFpdfReport extends ReportesnewFpdfBase
{
    public function generate(): string
    {
        $data = app(ReportePrenominaService::class)
            ->resumenDietas((int) $this->mes, (int) $this->ano, $this->entidadId);

        $titulo = 'RESUMEN DE GASTOS DE DIETAS';

        $campos = [
            ['titulo' => 'NRO',                'ancho' => 15,  'direccion' => 'C'],
            ['titulo' => 'NOMBRE TRABAJADOR',  'ancho' => 115, 'direccion' => 'C'],
            ['titulo' => 'CANT',               'ancho' => 30,  'direccion' => 'C'],
            ['titulo' => 'IMPORTE',            'ancho' => 30,  'direccion' => 'C'],
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
        $this->titulos($campos, [], [], 6, 10, 30);

        $posY = 36;
        $max = 33;
        $i = 0;
        $nro = 1;

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
            $this->Cell(15, 6, (string) $nro, 1, 0, 'C', 1);
            $this->Cell(115, 6, $this->latin1(ucwords(mb_strtolower((string) $r['nombrecompleto']))), 1, 0, 'L', 1);
            $this->Cell(30, 6, (string) $r['cant'], 1, 0, 'C', 1);
            $this->Cell(30, 6, $this->fmtVar($r['importe'], 2), 1, 0, 'R', 1);

            $posY += 6;
            $i++;
            $nro++;
        }

        $this->SetXY(10, $posY);
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor($this->getFillColor());
        $this->Cell(160, 10, 'TOTAL GENERAL', 1, 0, 'L', 1);
        $this->Cell(30, 10, $this->fmtVar($data['total'] ?? 0, 2), 1, 0, 'R', 1);

        return $this->Output('S');
    }

    private function getFillColor(): int
    {
        return (int) (session('FillColor') ?? 200);
    }
}
