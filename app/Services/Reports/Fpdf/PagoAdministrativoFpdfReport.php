<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * DATOS P/NOMINAS PAGO ADMINISTRATIVO — réplica FPDF del legacy con correcciones.
 * Legacy: Reportes.php:4491 pdf_salario_prenomina_resultado_adm +
 * ModSalarioAdmin:973-991 (Sistema de Pagos por Resultados EMCARGA).
 *
 * Columnas: No, Cod, NOMBRE, CARGO, TRT, SE, SCLA, STRT, SRInicial, Pen,
 * SRFinal, NOCTURNIDAD, MAESTRIA, H/EXTRAS, PAGOS ADICIONALES, SALARIO TOTAL.
 * Agrupa por área con subtotal (área + columnas acumuladas) y TOTAL GENERAL.
 *
 * Correcciones respecto al legacy (solicitadas por el usuario):
 * 1. Título ÚNICO "DATOS P/NOMINAS PAGO ADMINISTRATIVO" en todas las páginas
 *    (el legacy mostraba "SALARIO ADMINISTRATIVO" en la primera y cambiaba
 *    el título al saltar de página).
 * 2. El subtotal del área se imprime UNA sola vez y el "TOTAL GENERAL" queda
 *    limpio (el legacy re-dibujaba el nombre del área ENCIMA del "TOTAL
 *    GENERAL", tapándolo y dejando una fila duplicada).
 *
 * Formato: Legal horizontal. Cod = bolsa.versat. El CDS sale de cds_entidades
 * (entidad+mes+año); si falta, SRInicial/SRFinal/PAGOS quedan vacíos.
 */
class PagoAdministrativoFpdfReport extends ReportesnewFpdfBase
{
    public function __construct(?int $entidadId, string $mes, string $ano)
    {
        parent::__construct($entidadId, $mes, $ano, [], 'L', 'Legal');
    }

    public function generate(): string
    {
        $service = app(ReportePrenominaService::class);
        $data = $service->pagoAdministrativo((int) $this->mes, (int) $this->ano, $this->entidadId);

        $titulo = 'DATOS P/NOMINAS PAGO ADMINISTRATIVO';

        // Cabecera superior (agrupada) — réplica Reportes.php:4502-4517.
        $superior = [
            ['titulo' => 'No',          'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'Cod',         'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'NOMBRE Y APELLIDOS', 'ancho' => 45, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 7],
            ['titulo' => 'CARGO',       'ancho' => 45, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 12],
            ['titulo' => 'TRT',         'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 8],
            ['titulo' => 'SE',          'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 8],
            ['titulo' => 'SCLA',        'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 8],
            ['titulo' => 'STRT',        'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 8],
            ['titulo' => 'SRInicial',   'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 8],
            ['titulo' => 'Pen',         'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 8],
            ['titulo' => 'SRFinal',     'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 8],
            ['titulo' => 'NOCTURNIDAD', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 7],
            ['titulo' => 'MAESTRIA',    'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 7],
            ['titulo' => 'H/EXTRAS',    'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 7],
            ['titulo' => 'PAGOS',       'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 7],
            ['titulo' => 'SALARIO',     'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 8],
        ];

        // Cabecera inferior (subcolumnas).
        $medio = [
            ['titulo' => ' ',              'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => ' ',              'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'DEL TRABAJADOR', 'ancho' => 45, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 7],
            ['titulo' => ' ',              'ancho' => 45, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',               'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',               'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',               'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',               'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',               'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',               'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',               'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',               'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',               'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',               'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'ADICIONALES',    'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'TOTAL',          'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
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
        $this->titulos($superior, $medio, [], 6, 10, 30);
        $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'L');

        $posY = 42;
        $max = 165;
        $nro = 1;

        $keys = ['ttotal', 'impescala', 'impcla', 'impstrt', 'impsrinicial', 'penimporte',
            'impsrfinal', 'impnocturnidad', 'impmaestrias', 'impextra', 'imppadicional', 'impstotal'];
        $subtotal = array_fill_keys($keys, 0.0);
        $general = array_fill_keys($keys, 0.0);

        $area = null;
        $conCds = $data['tiene_cds'] ?? false;

        $dibujarEncabezados = function () use ($titulo, $superior, $medio) {
            $this->inicio($this->latin1($titulo), 50, 5);
            $this->titulos($superior, $medio, [], 6, 10, 30);
            $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'L');
        };

        foreach ($registros as $r) {
            $areaActual = $r['nombarea'] ?? 'Sin área';

            // Subtotal del área anterior (UNA sola vez — corrección del legacy).
            if ($area !== null && $area !== $areaActual) {
                $this->renderSubtotalArea($area, $subtotal, $conCds, $posY);
                $posY += 10;
                $subtotal = array_fill_keys($keys, 0.0);
            }
            $area = $areaActual;

            if ($posY >= $max) {
                $dibujarEncabezados();
                $posY = 42;
            }

            $this->renderFila($r, $nro, $posY, $conCds);
            $posY += 6;
            $nro++;

            foreach ($keys as $k) {
                $subtotal[$k] += (float) ($r[$k] ?? 0);
                $general[$k] += (float) ($r[$k] ?? 0);
            }
        }

        // Subtotal de la última área (UNA sola vez — corrección del legacy).
        if ($area !== null) {
            $this->renderSubtotalArea($area, $subtotal, $conCds, $posY);
            $posY += 10;
        }

        // TOTAL GENERAL limpio (corrección: el legacy lo tapaba con el área).
        $this->SetXY(10, $posY);
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor($this->getFillColor());
        $this->Cell(110, 10, 'TOTAL GENERAL', 1, 0, 'L', 1);
        $this->renderNumerosFila($general, 10, $conCds);

        return $this->Output('S');
    }

    private function renderFila(array $r, int $nro, int $posY, bool $conCds): void
    {
        $this->SetXY(10, $posY);
        $this->SetFillColor(999, 999, 999);
        $this->SetFont('Arial', '', 12);
        $this->Cell(10, 6, (string) $nro, 1, 0, 'C', 1);
        $this->Cell(10, 6, $r['versat'] ?? '', 1, 0, 'C', 1);
        $this->Cell(45, 6, $this->latin1(ucwords(strtolower($r['nombrecompleto'] ?? ''))), 1, 0, 'L', 1);
        $this->SetFont('Arial', '', 8);
        $this->Cell(45, 6, $this->latin1(mb_substr($r['nombcargo'] ?? '', 0, 25)), 1, 0, 'L', 1);
        $this->SetFont('Arial', '', 12);
        $this->Cell(15, 6, $this->fmtVar($r['ttotal'] ?? 0, 2), 1, 0, 'C', 1);
        $this->Cell(20, 6, $this->fmtVar($r['impescala'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, 6, $this->fmtVar($r['impcla'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, 6, $this->fmtVar($r['impstrt'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, 6, $conCds ? $this->fmtVar($r['impsrinicial'] ?? 0, 2) : '', 1, 0, 'R', 1);
        $this->Cell(20, 6, $this->fmtVar($r['penimporte'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, 6, $conCds ? $this->fmtVar($r['impsrfinal'] ?? 0, 2) : '', 1, 0, 'R', 1);
        $this->Cell(20, 6, $this->fmtVar($r['impnocturnidad'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, 6, $this->fmtVar($r['impmaestrias'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, 6, $this->fmtVar($r['impextra'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, 6, $this->fmtVar($r['imppadicional'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, 6, $conCds ? $this->fmtVar($r['impstotal'] ?? 0, 2) : '', 1, 0, 'R', 1);
    }

    private function renderSubtotalArea(string $area, array $s, bool $conCds, int $posY): void
    {
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(10, $posY);
        $this->SetFillColor($this->getFillColor());
        $this->Cell(110, 10, $this->latin1($area), 1, 0, 'L', 1);
        $this->renderNumerosFila($s, 10, $conCds);
    }

    /** Números compartidos entre subtotal de área y TOTAL GENERAL (altura 10). */
    private function renderNumerosFila(array $v, int $altura, bool $conCds): void
    {
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(15, $altura, $this->fmtVar($v['ttotal'] ?? 0, 2), 1, 0, 'C', 1);
        $this->Cell(20, $altura, $this->fmtVar($v['impescala'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, $altura, $this->fmtVar($v['impcla'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, $altura, $this->fmtVar($v['impstrt'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, $altura, $conCds ? $this->fmtVar($v['impsrinicial'] ?? 0, 2) : '', 1, 0, 'R', 1);
        $this->Cell(20, $altura, $this->fmtVar($v['penimporte'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, $altura, $conCds ? $this->fmtVar($v['impsrfinal'] ?? 0, 2) : '', 1, 0, 'R', 1);
        $this->Cell(20, $altura, $this->fmtVar($v['impnocturnidad'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, $altura, $this->fmtVar($v['impmaestrias'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, $altura, $this->fmtVar($v['impextra'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, $altura, $this->fmtVar($v['imppadicional'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, $altura, $conCds ? $this->fmtVar($v['impstotal'] ?? 0, 2) : '', 1, 0, 'R', 1);
    }

    private function getFillColor(): int
    {
        return (session('FillColor') ?? 200);
    }
}
