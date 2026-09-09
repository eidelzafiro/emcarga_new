<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * PRENOMINA INCIDENCIAS AL TIEMPO TRABAJADO — réplica FPDF del legacy.
 * Reportes.php:5426 (pdf_salario_prenomina_incidencias) +
 * ModIncidencias::mostrar_detalle (ModIncidencias.php:216).
 *
 * Muestra las incidencias de UN tipo (origen_id del catálogo `tipos_incidencias`).
 * Columnas: EXP(40), NOMBRE DEL TRABAJADOR(65), CLAVE(55), INICIO(20), FINAL(20),
 * ACTUAL(20), IMPORTE(25). Agrupa por trabajador ("Sub-Total" si >1 registro),
 * por área ("TOTAL {area}") y cierra con "TOTAL GENERAL". La columna IMPORTE se
 * rellena con `periodo_actual` (el legacy tenía `importe=0`; la referencia usa
 * `pactual`). Formato: Letter horizontal.
 */
class IncidenciasFpdfReport extends ReportesnewFpdfBase
{
    private int $origenId;

    public function __construct(?int $entidadId, string $mes, string $ano, int $origenId)
    {
        parent::__construct($entidadId, $mes, $ano, [], 'L', 'Letter');
        $this->origenId = $origenId;
    }

    public function generate(): string
    {
        $service = app(ReportePrenominaService::class);
        $data = $service->incidenciasTipo((int) $this->mes, (int) $this->ano, $this->origenId, $this->entidadId);

        $titulo = 'PRENOMINA INCIDENCIAS AL TIEMPO TRABAJADO';

        $campos = [
            ['titulo' => 'EXP',                  'ancho' => 40, 'direccion' => 'C'],
            ['titulo' => 'NOMBRE DEL TRABAJADOR', 'ancho' => 65, 'direccion' => 'C'],
            ['titulo' => 'CLAVE',                'ancho' => 55, 'direccion' => 'C'],
            ['titulo' => 'INICIO',               'ancho' => 20, 'direccion' => 'C', 'letra' => 9],
            ['titulo' => 'FINAL',                'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'ACTUAL',               'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'IMPORTE',              'ancho' => 25, 'direccion' => 'C'],
        ];

        $registros = $data['registros'] ?? [];

        if (empty($registros)) {
            $this->inicio($this->latin1($titulo), 50, 5);
            $this->SetFont('Arial', 'B', 35);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
            return $this->Output('S');
        }

        $this->inicio($this->latin1($titulo), 50, 5);
        $this->titulos($campos, [], [], 6, 10, 30);
        $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'L');

        $posY = 36;
        $max = 21;
        $i = 0;

        $area = null;
        $trabajador = null;
        $contar = 0;
        $pactual = 0.0;
        $importe = 0.0;
        $apactual = 0.0;
        $aimporte = 0.0;
        $tpactual = 0.0;
        $timporte = 0.0;

        foreach ($registros as $arr) {
            $nombre = $arr['nombrecompleto'] ?? '';

            // Salto de página.
            if ($i >= $max) {
                $this->inicio($this->latin1($titulo), 50, 5);
                $this->titulos($campos, [], [], 6, 10, 35);
                $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'L');
                $posY = 45;
                $i = 0;
            }

            if ($trabajador === $nombre) {
                $this->renderFila($arr, $posY);
                $posY += 6;
                $i++;
                $contar++;
                $pactual += (float) $arr['pactual'];
                $importe += (float) $arr['importe'];
                $apactual += (float) $arr['pactual'];
                $aimporte += (float) $arr['importe'];
            } else {
                if ($contar > 1) {
                    $this->renderSubtotal($trabajador, $pactual, $importe, $posY);
                    $posY += 6;
                    $i++;
                }
                $trabajador = $nombre;
                $pactual = 0.0;
                $importe = 0.0;
                $contar = 0;

                if ($area !== null && $area !== $arr['nombarea']) {
                    $this->renderTotalArea($area, $apactual, $aimporte, $posY);
                    $posY += 6;
                    $i++;
                    $area = $arr['nombarea'];
                    $apactual = 0.0;
                    $aimporte = 0.0;
                }
                $area = $arr['nombarea'];

                $this->renderFila($arr, $posY);
                $posY += 6;
                $i++;
                $contar++;
                $pactual += (float) $arr['pactual'];
                $importe += (float) $arr['importe'];
                $apactual += (float) $arr['pactual'];
                $aimporte += (float) $arr['importe'];
            }

            $tpactual += (float) $arr['pactual'];
            $timporte += (float) $arr['importe'];
        }

        // Último sub-total y total de área.
        if ($contar > 1) {
            $this->renderSubtotal($trabajador, $pactual, $importe, $posY);
            $posY += 6;
        }
        if ($area !== null) {
            $this->renderTotalArea($area, $apactual, $aimporte, $posY);
            $posY += 6;
        }

        // Total general.
        $this->SetXY(10, $posY);
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor($this->getFillColor());
        $this->Cell(200, 6, 'TOTAL GENERAL', 1, 0, 'L', 1);
        $this->Cell(20, 6, '', 1, 0, 'R', 1);
        $this->Cell(25, 6, $this->fmtVar($timporte, 2), 1, 0, 'R', 1);

        return $this->Output('S');
    }

    private function renderFila(array $arr, int $posY): void
    {
        $this->SetXY(10, $posY);
        $this->SetFillColor(999, 999, 999);
        $this->SetFont('Arial', '', 10);
        $this->Cell(40, 6, $arr['nronomina'] ?? '', 1, 0, 'C', 1);
        $this->Cell(65, 6, $this->latin1(ucwords(strtolower($arr['nombrecompleto'] ?? ''))), 1, 0, 'L', 1);
        $this->Cell(55, 6, $this->latin1(mb_substr($arr['clave'] ?? '', 0, 25)), 1, 0, 'L', 1);
        $this->Cell(20, 6, $arr['inicio'] ?? '', 1, 0, 'R', 1);
        $this->Cell(20, 6, $arr['final'] ?? '', 1, 0, 'R', 1);
        $this->Cell(20, 6, '', 1, 0, 'R', 1);
        $this->Cell(25, 6, $this->fmtVar($arr['importe'] ?? 0, 2), 1, 0, 'R', 1);
    }

    private function renderSubtotal(string $trabajador, float $pactual, float $importe, int $posY): void
    {
        $this->SetXY(10, $posY);
        $this->SetFont('Arial', '', 12);
        $this->SetFillColor($this->getFillColor());
        $this->Cell(200, 6, $this->latin1('Sub-Total '.$trabajador), 1, 0, 'R', 1);
        $this->Cell(20, 6, '', 1, 0, 'R', 1);
        $this->Cell(25, 6, $this->fmtVar($importe, 2), 1, 0, 'R', 1);
    }

    private function renderTotalArea(string $area, float $apactual, float $aimporte, int $posY): void
    {
        $this->SetXY(10, $posY);
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor($this->getFillColor());
        $this->Cell(200, 6, $this->latin1('TOTAL '.$area), 1, 0, 'L', 1);
        $this->Cell(20, 6, '', 1, 0, 'R', 1);
        $this->Cell(25, 6, $this->fmtVar($aimporte, 2), 1, 0, 'R', 1);
    }

    private function getFillColor(): int
    {
        return (session('FillColor') ?? 200);
    }
}
