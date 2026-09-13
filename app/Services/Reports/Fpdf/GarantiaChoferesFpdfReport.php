<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * DATOS P/NOMINA GARANTIA SALARIAL CHOFERES — réplica FPDF del legacy.
 * Reportes.php (npdf_salario_choferes_garantia).
 *
 * Choferes con garantía usada (tgarantia > 0). Columnas: EXP(30),
 * NOMBRE DEL TRABAJADOR(70), GARANTIA(30), TIEMPO(75: DEL MES/UTILIZADO/
 * A PAGAR), SALARIO X GARANTIA(30). Formato: Letter horizontal.
 */
class GarantiaChoferesFpdfReport extends ReportesnewFpdfBase
{
    public function generate(): string
    {
        $data = app(ReportePrenominaService::class)
            ->garantiaChoferes((int) $this->mes, (int) $this->ano, $this->entidadId);

        $titulo = 'DATOS P/NOMINA GARANTIA SALARIAL CHOFERES';

        $campos = [
            ['titulo' => 'EXP',                   'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'NOMBRE DEL TRABAJADOR', 'ancho' => 70, 'direccion' => 'C'],
            ['titulo' => 'GARANTIA',              'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'TIEMPO',                'ancho' => 75, 'direccion' => 'C'],
            ['titulo' => 'SALARIO X',             'ancho' => 30, 'direccion' => 'C'],
        ];
        $campos1 = [
            ['titulo' => '',          'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => '',          'ancho' => 70, 'direccion' => 'C'],
            ['titulo' => 'INICIAL',   'ancho' => 30, 'direccion' => 'C', 'letra' => 10],
            ['titulo' => 'DEL MES',   'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'UTILIZADO', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'A PAGAR',   'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'GARANTIA',  'ancho' => 30, 'direccion' => 'C'],
        ];

        $registros = $data['registros'] ?? [];

        if (empty($registros)) {
            $this->inicio($this->latin1($titulo), 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');

            return $this->Output('S');
        }

        $this->inicio($this->latin1($titulo), 50, 5);
        $this->titulos($campos1, $campos, [], 6, 15, 30);
        $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'L');

        $posY = 42;
        $max = 23;
        $i = 0;
        $totales = $data['totales'] ?? [];

        foreach ($registros as $r) {
            if ($i >= $max) {
                $this->inicio($this->latin1($titulo), 50, 5);
                $this->titulos($campos1, $campos, [], 6, 15, 35);
                $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'L');
                $posY = 45;
                $i = 0;
            }

            $this->SetFont('Arial', '', 12);
            $this->SetXY(15, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->Cell(30, 6, $this->latin1((string) $r['nronomina']), 1, 0, 'C', 1);
            $this->Cell(70, 6, $this->latin1(ucwords(mb_strtolower((string) $r['nombrecompleto']))), 1, 0, 'L', 1);
            $this->Cell(30, 6, $this->fmtVar($r['garantia'], 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->fmtVar($r['tiempo_mes'], 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->fmtVar($r['tincidencias'], 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->fmtVar($r['tgarantia'], 2), 1, 0, 'R', 1);
            $this->Cell(30, 6, $this->fmtVar($r['impgarantia'], 2), 1, 0, 'R', 1);

            $posY += 6;
            $i++;
        }

        // Totales.
        $this->SetFont('Arial', 'B', 14);
        $this->SetXY(15, $posY);
        $this->SetFillColor($this->getFillColor());
        $this->Cell(100, 10, 'TOTALES', 1, 0, 'C', 1);
        $this->Cell(30, 10, $this->fmtVar($totales['garantia'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, '', 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($totales['tincidencias'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($totales['tgarantia'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(30, 10, $this->fmtVar($totales['impgarantia'] ?? 0, 2), 1, 0, 'R', 1);

        return $this->Output('S');
    }

    private function getFillColor(): int
    {
        return (int) (session('FillColor') ?? 200);
    }
}
