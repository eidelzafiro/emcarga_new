<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * Prenómina Salario Administrativo — réplica FPDF exacta del legacy.
 * Reportesh.php:988 pdf_salario_prenomina_sistema($sistema=1)
 */
class PrenominaAdministrativoFpdfReport extends FpdfReportBase
{
    public function generate(): string
    {
        $service = app(ReportePrenominaService::class);
        $data = $service->prenominaAdministrativo((int) $this->mes, (int) $this->ano, $this->entidadId);

        if (empty($data['registros'])) {
            $this->inicio('DATOS P/NOMINAS CALCULADAS  ADMINISTRATIVO', 10, 5);
            $this->SetFont('Arial', 'B', 35);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
            return $this->Output('S');
        }

        $titulo = 'DATOS P/NOMINAS CALCULADAS  ADMINISTRATIVO';
        $this->inicio($titulo, 10, 5);

        // Definición de columnas (replica exacta del legacy líneas 999-1026)
        $vcampo = 10; // nroCI = 0 → 10

        $campos2 = [
            ['titulo' => '',          'ancho' => $vcampo, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'],
            ['titulo' => '',          'ancho' => 75,     'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => '',          'ancho' => 20,     'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'REGULAR',   'ancho' => 20,     'direccion' => 'C'],
            ['titulo' => 'IRREGULAR', 'ancho' => 20,     'direccion' => 'C'],
            ['titulo' => 'TOTAL',     'ancho' => 20,     'direccion' => 'C'],
            ['titulo' => '',          'ancho' => 25,     'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'ADICIONALES','ancho' => 25,    'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'A DEVENGAR','ancho' => 25,     'direccion' => 'C', 'bordes' => 'LR'],
        ];

        $campos = [
            ['titulo' => 'EXP',                   'ancho' => $vcampo, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'NOMBRE DEL TRABAJADOR',  'ancho' => 75,     'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '9'],
            ['titulo' => 'TIEMPO',                 'ancho' => 20,     'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'SALARIO',                'ancho' => 60,     'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'PERFECCIO',              'ancho' => 25,     'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'OTROS',                  'ancho' => 25,     'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'SALARIO',                'ancho' => 25,     'direccion' => 'C', 'bordes' => 'LTR'],
        ];

        $campos1 = [
            ['titulo' => '',         'ancho' => $vcampo, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '9'],
            ['titulo' => '',         'ancho' => 75,     'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => '',         'ancho' => 20,     'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'ESCALA',   'ancho' => 60,     'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'NAMIENTO', 'ancho' => 25,     'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'PAGOS',    'ancho' => 25,     'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'TOTAL',    'ancho' => 25,     'direccion' => 'C', 'bordes' => 'LR'],
        ];

        $this->titulos($campos2, $campos, $campos1, 30);
        $this->firmas(170);

        // Contenido — agrupado por área con subtotales (replica exacta legacy:988)
        $posY = 48;
        $max = 170;
        $i = 1;
        $nro = 1;
        $area = null;

        // Totales por área
        $ttotal = 0; $impbase = 0; $impregular = 0; $impirregular = 0;
        $impplus = 0; $padicional = 0; $impsalfinal = 0;

        // Totales generales
        $gttotal = 0; $gimpbase = 0; $gimpregular = 0; $gimpirregular = 0;
        $gimpplus = 0; $gpadicional = 0; $gimpsalfinal = 0;

        foreach ($data['registros'] as $arr) {
            $areaActual = $arr['nombarea'] ?? 'Sin área';

            // Cambio de área → subtotal
            if ($area !== null && $area !== $areaActual) {
                $this->renderSubtotalArea($area, $ttotal, $impregular, $impirregular, $impbase, $impplus, $padicional, $impsalfinal, $posY, $vcampo);
                $posY += 10;
                $i++;
                $nro++;
                $this->resetTotals($ttotal, $impbase, $impregular, $impirregular, $impplus, $padicional, $impsalfinal);
            }

            $area = $areaActual;

            // Nueva página si necesario
            if ($posY >= $max) {
                $this->inicio($titulo, 10, 5);
                $this->titulos($campos2, $campos, $campos1, 30);
                $this->firmas(170);
                $posY = 48;
                $i = 0;
            }

            // Fila de datos (replica legacy:1050-1067)
            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->SetFont('Arial', '', 10);
            $this->Cell($vcampo, 6, $arr['nronomina'] ?? '', 1, 0, 'C', 1);
            $this->Cell(75, 6, mb_strtoupper(mb_strtolower($arr['nombrecompleto'] ?? '')), 1, 0, 'L', 1);
            $this->SetFont('Arial', '', 12);
            $this->Cell(20, 6, $this->fmt($arr['ttotal'] ?? 0), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmt($arr['impregular'] ?? 0), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmt($arr['impirregular'] ?? 0), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmt($arr['impbase'] ?? 0), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->fmt($arr['impplus'] ?? 0), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->fmt($arr['padicionales'] ?? 0), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->fmt($arr['impreporte'] ?? 0), 1, 0, 'R', 1);
            $posY += 6;
            $i++;
            $nro++;

            // Acumular totales
            $ttotal += ($arr['ttotal'] ?? 0);
            $impbase += ($arr['impbase'] ?? 0);
            $impregular += ($arr['impregular'] ?? 0);
            $impirregular += ($arr['impirregular'] ?? 0);
            $impplus += ($arr['impplus'] ?? 0);
            $padicional += ($arr['padicionales'] ?? 0);
            $impsalfinal += ($arr['impreporte'] ?? 0);

            // Acumular totales generales
            $gttotal += ($arr['ttotal'] ?? 0);
            $gimpbase += ($arr['impbase'] ?? 0);
            $gimpregular += ($arr['impregular'] ?? 0);
            $gimpirregular += ($arr['impirregular'] ?? 0);
            $gimpplus += ($arr['impplus'] ?? 0);
            $gpadicional += ($arr['padicionales'] ?? 0);
            $gimpsalfinal += ($arr['impreporte'] ?? 0);
        }

        // Subtotal última área
        if ($area !== null) {
            $this->renderSubtotalArea($area, $ttotal, $impregular, $impirregular, $impbase, $impplus, $padicional, $impsalfinal, $posY, $vcampo);
            $posY += 10;
        }

        // Total general
        $this->SetXY(10, $posY);
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor($this->getFillColor());
        $this->Cell($vcampo + 75, 10, 'TOTAL GENERAL', 1, 0, 'L', 1);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(20, 10, $this->fmt($gttotal), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmt($gimpregular), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmt($gimpirregular), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmt($gimpbase), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmt($gimpplus), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmt($gpadicional), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmt($gimpsalfinal), 1, 0, 'R', 1);

        return $this->Output('S');
    }

    private function renderSubtotalArea(string $area, float $ttotal, float $impregular, float $impirregular, float $impbase, float $impplus, float $padicional, float $impsalfinal, int $posY, int $vcampo): void
    {
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(10, $posY);
        $this->SetFillColor($this->getFillColor());
        $this->Cell($vcampo + 75, 10, 'TOTAL ' . mb_strtoupper($area), 1, 0, 'L', 1);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(20, 10, $this->fmt($ttotal), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmt($impregular), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmt($impirregular), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmt($impbase), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmt($impplus), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmt($padicional), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmt($impsalfinal), 1, 0, 'R', 1);
    }

    private function resetTotals(&$ttotal, &$impbase, &$impregular, &$impirregular, &$impplus, &$padicional, &$impsalfinal): void
    {
        $ttotal = 0; $impbase = 0; $impregular = 0; $impirregular = 0;
        $impplus = 0; $padicional = 0; $impsalfinal = 0;
    }

    private function getFillColor(): int
    {
        return (session('FillColor') ?? 200);
    }
}
