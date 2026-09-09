<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * DATOS P/NOMINAS (NOCTURNIDAD) — réplica FPDF del legacy.
 * Reportes.php:4046 (pdf_salario_prenomina_nocturnidad) +
 * ModSalarioAdmin::mostrar_salario_emcarga (sistema de pago = 1).
 *
 * Muestra SOLO trabajadores administrativos con nocturnidad (impnocturnidad > 0).
 * Columnas: EXP, NOMBRE DEL TRABAJADOR, TRABAJADAS DE 7-11PM (HRSX4/IMPORTE),
 * TRABAJADAS DE 11-7AM (HRSX8/IMPORTE), HORAS TOTAL y TRABAJADO A PAGAR.
 * Agrupa por área con "TOTAL {area}" y cierra con "TOTAL GENERAL".
 *
 * Formato: Letter horizontal. La columna EXP usa el CI (nroci=1) o versat.
 */
class NocturnidadFpdfReport extends ReportesnewFpdfBase
{
    public function __construct(?int $entidadId, string $mes, string $ano)
    {
        parent::__construct($entidadId, $mes, $ano, [], 'L', 'Letter');
    }

    public function generate(): string
    {
        $service = app(ReportePrenominaService::class);
        $data = $service->prenominaAdministrativo((int) $this->mes, (int) $this->ano, $this->entidadId);

        $titulo = 'DATOS P/NOMINAS (NOCTURNIDAD)';

        $vcampo = 25;
        // Cabecera superior (agrupada).
        $superior = [
            ['titulo' => 'EXP',                  'ancho' => $vcampo, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'NOMBRE DEL TRABAJADOR', 'ancho' => 70, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'TRABAJADAS DE 7-11PM', 'ancho' => 50, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'TRABAJADAS DE 11-7AM', 'ancho' => 50, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'HORAS',                'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'TRABAJADO',            'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LTR'],
        ];

        // Cabecera inferior (subcolumnas).
        $medio = [
            ['titulo' => '',       'ancho' => $vcampo, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',       'ancho' => 70, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'HRSX4',  'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'IMPORTE', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'HRSX8',  'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'IMPORTE', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'TOTAL',  'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'A PAGAR', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRB'],
        ];

        // Solo registros con nocturnidad (impnocturnidad > 0).
        $registros = array_values(array_filter(
            $data['registros'] ?? [],
            fn ($r) => ((float) ($r['impnocturnidad'] ?? 0)) > 0
        ));

        if (empty($registros)) {
            $this->inicio($this->latin1($titulo), 50, 5);
            $this->SetFont('Arial', 'B', 18);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
            return $this->Output('S');
        }

        $this->inicio($this->latin1($titulo), 50, 5);
        $this->titulos($superior, $medio, [], 6, 10, 30);
        $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'L');

        $posY = 42;
        $max = 170;

        $area = null;
        $h1 = 0.0; $h2 = 0.0; $h3 = 0.0;
        $i1 = 0.0; $i2 = 0.0; $i3 = 0.0;
        $th1 = 0.0; $th2 = 0.0; $th3 = 0.0;
        $ti1 = 0.0; $ti2 = 0.0; $ti3 = 0.0;

        foreach ($registros as $arr) {
            $areaActual = $arr['nombarea'] ?? 'Sin área';

            if ($area !== null && $area !== $areaActual) {
                $posY = $this->renderTotalArea($area, $h1, $i1, $h2, $i2, $h3, $i3, $vcampo, $posY);
                $area = $areaActual;
                $h1 = 0.0; $h2 = 0.0; $h3 = 0.0;
                $i1 = 0.0; $i2 = 0.0; $i3 = 0.0;
            }
            $area = $areaActual;

            if ($posY >= $max) {
                $this->inicio($this->latin1($titulo), 50, 5);
                $this->titulos($superior, $medio, [], 6, 10, 30);
                $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'L');
                $posY = 42;
            }

            $noct1 = (float) ($arr['noct1'] ?? 0);
            $noct2 = (float) ($arr['noct2'] ?? 0);
            $impnoct1 = (float) ($arr['impnoct1'] ?? 0);
            $impnoct2 = (float) ($arr['impnoct2'] ?? 0);
            $impnocturnidad = (float) ($arr['impnocturnidad'] ?? 0);

            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->SetFont('Arial', '', 11);
            $this->Cell($vcampo, 6, $arr['versat'] ?? '', 1, 0, 'C', 1);
            $this->Cell(70, 6, $this->latin1(ucwords(strtolower($arr['nombrecompleto'] ?? ''))), 1, 0, 'L', 1);
            $this->SetFont('Arial', '', 14);
            $this->Cell(25, 6, $this->fmtVar($noct1, 0), 1, 0, 'C', 1);
            $this->Cell(25, 6, $this->fmtVar($impnoct1, 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->fmtVar($noct2, 0), 1, 0, 'C', 1);
            $this->Cell(25, 6, $this->fmtVar($impnoct2, 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->fmtVar($noct1 + $noct2, 0), 1, 0, 'C', 1);
            $this->Cell(30, 6, $this->fmtVar($impnocturnidad, 2), 1, 0, 'R', 1);
            $posY += 6;

            $h1 += $noct1;
            $h2 += $noct2;
            $h3 += $noct1 + $noct2;
            $i1 += $impnoct1;
            $i2 += $impnoct2;
            $i3 += $impnocturnidad;
            $th1 += $noct1;
            $th2 += $noct2;
            $th3 += $noct1 + $noct2;
            $ti1 += $impnoct1;
            $ti2 += $impnoct2;
            $ti3 += $impnocturnidad;
        }

        // Último total de área.
        if ($area !== null) {
            $posY = $this->renderTotalArea($area, $h1, $i1, $h2, $i2, $h3, $i3, $vcampo, $posY);
        }

        // Total general.
        $this->SetFillColor($this->getFillColor());
        $this->SetFont('Arial', 'B', 14);
        $this->SetXY(10, $posY);
        $this->Cell($vcampo + 70, 10, 'TOTAL GENERAL ', 1, 0, 'L', 1);
        $this->Cell(25, 10, $this->fmtVar($th1, 0), 1, 0, 'C', 1);
        $this->Cell(25, 10, $this->fmtVar($ti1, 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($th2, 0), 1, 0, 'C', 1);
        $this->Cell(25, 10, $this->fmtVar($ti2, 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($th3, 0), 1, 0, 'C', 1);
        $this->Cell(30, 10, $this->fmtVar($ti3, 2), 1, 0, 'R', 1);

        return $this->Output('S');
    }

    private function renderTotalArea(string $area, float $h1, float $i1, float $h2, float $i2, float $h3, float $i3, int $vcampo, int $posY): int
    {
        $this->SetFont('Arial', 'B', 14);
        $this->SetXY(10, $posY);
        $this->SetFillColor($this->getFillColor());
        $this->Cell($vcampo + 70, 10, $this->latin1('TOTAL '.$area), 1, 0, 'L', 1);
        $this->Cell(25, 10, $this->fmtVar($h1, 0), 1, 0, 'C', 1);
        $this->Cell(25, 10, $this->fmtVar($i1, 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($h2, 0), 1, 0, 'C', 1);
        $this->Cell(25, 10, $this->fmtVar($i2, 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($h3, 0), 1, 0, 'C', 1);
        $this->Cell(30, 10, $this->fmtVar($i3, 2), 1, 0, 'R', 1);

        return $posY + 10;
    }

    private function getFillColor(): int
    {
        return (session('FillColor') ?? 200);
    }
}
