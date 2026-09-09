<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * SC-4-05 CONTROL DIARIO TIEMPO DE TRABAJO (reporte 10) — réplica FPDF.
 * Reportes.php:3510 (pdf_salario_control_diario_administrativo).
 *
 * Grid Legal horizontal: NRO EXPEDIENTE + DT (categoría) + 31 días (2 filas
 * de 16/15) + RESUMEN DESCUENTOS (2 filas de 8 columnas) + NOCT1/NOCT2 +
 * TIEMPO. Filas de 5mm, dos líneas por trabajador (nronomina / nombre).
 * Agrupado por área con fila de título de área de 6mm.
 */
class ControlDiarioAdministrativoFpdfReport extends ReportesnewFpdfBase
{
    public function __construct(?int $entidadId, string $mes, string $ano)
    {
        parent::__construct($entidadId, $mes, $ano, [], 'L', 'Legal');
    }

    public function generate(): string
    {
        $service = app(ReportePrenominaService::class);
        $data = $service->controlDiario((int) $this->mes, (int) $this->ano, $this->entidadId);

        $titulo = 'SC-4-05 CONTROL DIARIO TIEMPO DE TRABAJO';
        $dias = \Carbon\Carbon::createFromDate((int) $this->ano, (int) $this->mes, 1)->daysInMonth;

        $registros = $data['registros'] ?? [];

        if (empty($registros)) {
            $this->inicio($this->latin1($titulo), 50, 5);
            $this->SetFont('Arial', 'B', 35);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
            return $this->Output('S');
        }

        $this->paginaBase($titulo);

        $posY = 51;
        $max = 165;
        $area = null;

        foreach ($registros as $r) {
            if ($posY >= $max) {
                $this->paginaBase($titulo);
                $posY = 51;
            }

            if ($area !== $r['nombarea']) {
                $area = $r['nombarea'];
                $this->SetFont('Arial', 'B', 12);
                $this->SetFillColor(999, 999, 999);
                $this->SetXY(10, $posY);
                $this->Cell(330, 6, $this->latin1($area), 1, 0, 'C', 1);
                $posY += 6;
            }

            $posY = $this->filaTrabajador($r, $posY, $dias);
        }

        return $this->Output('S');
    }

    /**
     * Pinta la cabecera completa de la página (título, números de días,
     * columnas de descuentos y firmas).
     */
    protected function paginaBase(string $titulo): void
    {
        $this->inicio($this->latin1($titulo), 50, 5);

        $campos = [
            ['titulo' => 'NRO EXPEDIENTE',                  'ancho' => 45, 'direccion' => 'C'],
            ['titulo' => 'DT',                              'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'DISTRIBUCION DEL TIEMPO EN DIAS', 'ancho' => 160, 'direccion' => 'C'],
            ['titulo' => 'RESUMEN DESCUENTOS',              'ancho' => 80, 'direccion' => 'C'],
            ['titulo' => 'NOCTUR',                          'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => '',                                'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR'],
        ];
        $this->titulos($campos, [], [], 6, 10, 35);
        $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'L');

        // Números de día 1..31 en dos filas (1-15 / 16-31).
        $posX = 65;
        $posY = 41;
        $this->SetFillColor($this->fillGris());
        for ($i = 1; $i <= 31; $i++) {
            $this->SetFont('Arial', 'B', 10);
            $this->SetXY($posX, $posY);
            $this->Cell(10, 5, (string) $i, 1, 0, 'C', 1);
            $posX += 10;
            if ($i == 15) {
                $this->Cell(10, 5, '', 1, 0, 'C', 1);
                $posY += 5;
                $posX = 65;
            }
        }

        // NOMBRE Y APELLIDOS + DT.
        $this->SetFont('Arial', 'B', 11);
        $this->SetXY(10, 41);
        $this->Cell(45, 10, 'NOMBRE Y APELLIDOS', 'LRB', 0, 'C', 1);
        $this->Cell(10, 10, '', 'LRB', 0, 'C', 1);

        // Fila superior de descuentos (V C/R AI/AJ LM/PS CM/E-3d MOV INT FNT/RL).
        $this->SetFont('Arial', 'B', 6);
        $this->SetXY(225, 41);
        foreach (['V', 'C/R', 'AI/AJ', 'LM/PS', 'CM/E-3d', 'MOV', 'INT', 'FNT/RL'] as $c) {
            $this->Cell(10, 5, $c, 1, 0, 'C', 1);
        }
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(20, 5, 'NIDAD', 'LRB', 0, 'C', 1);
        $this->Cell(15, 5, 'TIEMPO', 'LR', 0, 'C', 1);

        // Fila inferior (A/B CHM RT/SE AT SL IMP/SAH OTROS TIEMPO NOCT1 NOCT2).
        $this->SetFont('Arial', 'B', 6);
        $this->SetXY(305, 46);
        foreach (['A/B', 'CHM', 'RT/SE', 'AT', 'SL', 'IMP/SAH', 'OTROS', 'TIEMPO', 'NOCT1', 'NOCT2'] as $c) {
            $this->Cell(10, 5, $c, 1, 0, 'C', 1);
        }
        $this->Cell(15, 5, '', 'LRB', 0, 'C', 1);
    }

    /**
     * Pinta las dos líneas de un trabajador y devuelve la nueva Y.
     */
    protected function filaTrabajador(array $r, int $posY, int $dias): int
    {
        $d = $r['descuentos'];

        // NRO EXPEDIENTE / NOMBRE.
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(10, $posY);
        $this->SetFillColor(999, 999, 999);
        $this->Cell(45, 5, (string) $r['nronomina'], 'LTR', 0, 'L', 1);
        $this->SetXY(10, $posY + 5);
        $this->SetFont('Arial', '', 9);
        $this->Cell(45, 5, $this->latin1(ucwords(strtolower($r['nombrecompleto']))), 'LRB', 0, 'L', 1);

        // DT (categoría en administrativo; días trabajados en choferes).
        $this->SetFont('Arial', 'B', 12);
        $this->SetXY(55, $posY);
        $this->Cell(10, 10, $this->celdaDt($r), 1, 0, 'C', 1);

        // Días (dos medias filas de 5mm) — réplica exacta del legacy:
        // $dias = daysInMonth-1 y loop $i=0..$dias (30 celdas para 31 días),
        // celda extra al pasar el día 15 (i==14) y rellenos según 29/28/27.
        $diasLegacy = $dias - 1;
        $posX = 65;
        $this->SetFont('Arial', 'B', 10);
        for ($i = 0; $i <= $diasLegacy; $i++) {
            $val = (string) ($r['tiempo'][$i] ?? '');
            if ($val === 'D') {
                $this->SetFillColor(192, 192, 192);
            } else {
                $this->SetFillColor(999, 999, 999);
            }
            $this->SetXY($posX, $posY);
            $this->Cell(10, 5, $val, 1, 0, 'C', 1);
            $posX += 10;
            $this->SetFillColor(999, 999, 999);
            if ($i == 14) {
                $this->Cell(10, 5, '', 1, 0, 'C', 1);
                $posY += 5;
                $posX = 65;
            }
        }
        // Relleno de la fila 2 hasta 16 celdas (meses de 30/29/28 días).
        if ($diasLegacy == 29 || $diasLegacy == 28 || $diasLegacy == 27) {
            $celdas = [30 => 1, 29 => 2, 28 => 3][$diasLegacy] ?? 0;
            for ($k = 0; $k < $celdas; $k++) {
                $this->SetXY($posX, $posY);
                $this->Cell(10, 5, '', 1, 0, 'C', 1);
                $posX += 10;
            }
        }

        // Descuentos (fila superior a $posY-5, fila inferior a $posY).
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(999, 999, 999);
        $this->SetXY($posX, $posY - 5);
        $this->Cell(10, 5, $this->fmtVar($d['vac'], 2), 1, 0, 'C', 1);
        $this->Cell(10, 5, $this->fmtVar($d['cr'], 2), 1, 0, 'C', 1);
        $this->Cell(10, 5, $this->fmtVar($d['ai'], 2), 1, 0, 'C', 1);
        $this->Cell(10, 5, $this->fmtVar($d['lm'], 2), 1, 0, 'C', 1);
        $this->Cell(10, 5, $this->fmtVar($d['cm'], 2), 1, 0, 'C', 1);
        $this->Cell(10, 5, $this->fmtVar($d['mov'], 2), 1, 0, 'C', 1);
        $this->Cell(10, 5, $this->fmtVar($d['int'], 2), 1, 0, 'C', 1);
        $this->Cell(10, 5, $this->fmtVar($d['fnt'], 2), 1, 0, 'C', 1);
        $this->Cell(10, 10, $this->fmtVar($d['noct1'], 2), 1, 0, 'C', 1);
        $this->Cell(10, 10, $this->fmtVar($d['noct2'], 2), 1, 0, 'C', 1);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(15, 10, $this->fmtVar($r['regular'], 2), 1, 0, 'C', 1);

        $this->SetFont('Arial', 'B', 9);
        $this->SetXY($posX, $posY);
        $this->Cell(10, 5, $this->fmtVar($d['ab'], 2), 1, 0, 'C', 1);
        $this->Cell(10, 5, $this->fmtVar($d['chm'], 2), 1, 0, 'C', 1);
        $this->Cell(10, 5, $this->fmtVar($d['rt'], 2), 1, 0, 'C', 1);
        $this->Cell(10, 5, $this->fmtVar($d['at'], 2), 1, 0, 'C', 1);
        $this->Cell(10, 5, $this->fmtVar($d['sl'], 2), 1, 0, 'C', 1);
        $this->Cell(10, 5, $this->fmtVar($d['imp'], 2), 1, 0, 'C', 1);
        $this->Cell(10, 5, $this->fmtVar($d['o'], 2), 1, 0, 'C', 1);
        $this->Cell(10, 5, $this->fmtVar($d['tiempo1'], 2), 1, 0, 'C', 1);

        return $posY + 5;
    }

    /**
     * Contenido de la celda DT: en el administrativo es la 1ra letra de la
     * categoría del cargo (legacy substr(nombcatcargo,0,1)).
     */
    protected function celdaDt(array $r): string
    {
        return (string) ($r['categoria'] ?? '');
    }
}
