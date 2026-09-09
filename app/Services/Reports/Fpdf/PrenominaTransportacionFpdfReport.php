<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * Prenómina Salario Transportación — réplica FPDF del legacy.
 * Reportes.php:2512 npdf_salario_choferes_resumen
 *
 * Columnas: EXP, NOMBRE, TH, TRT, ESCALA, CLA, STRT, INGRESOS TRANSP REAL,
 * FONDO FORMADO, INCREMENTO SALARIAL (ISInicial/Pen%/Pen$/ISFinal),
 * SD, FT, SALARIO TOTAL y DT.
 *
 * Formato: Legal horizontal (355.6mm), réplica 1:1 del legacy
 * (`FPDF('L','mm','Legal')`). Los 335mm de las 17 columnas caben en el
 * ancho útil (~350mm); no se escalan.
 */
class PrenominaTransportacionFpdfReport extends ReportesnewFpdfBase
{
    /** Anchos de las 17 columnas (Legacy Legal 335mm, sin escalar). */
    private const ANCHOS = [15, 50, 15, 15, 20, 20, 20, 20, 20, 20, 15, 15, 20, 20, 15, 20, 15];

    public function __construct(?int $entidadId, string $mes, string $ano, array $filtros = [])
    {
        parent::__construct($entidadId, $mes, $ano, $filtros, 'L', 'Legal');
    }

    public function generate(): string
    {
        $service = app(ReportePrenominaService::class);
        $data = $service->prenominaChoferes((int) $this->mes, (int) $this->ano, $this->entidadId);

        $titulo = 'PRENOMINA SALARIO TRANSPORTACION';

        if (empty($data['registros'])) {
            $this->inicio($titulo, 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
            return $this->Output('S');
        }

        $this->inicio($titulo, 50, 5);

        $a = self::ANCHOS;

        // Definición de columnas (replica Reportes.php:2524-2574).
        $campos = [
            ['titulo' => 'NRO',      'ancho' => $a[0],  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => '',         'ancho' => $a[1],  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'TH',       'ancho' => $a[2],  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'TRT',      'ancho' => $a[3],  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'STRT',     'ancho' => $a[4] + $a[5] + $a[6], 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'INGRESOS', 'ancho' => $a[7],  'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 6],
            ['titulo' => 'FONDO',    'ancho' => $a[8],  'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 12],
            ['titulo' => 'INCREMENTO SALARIAL', 'ancho' => $a[9] + $a[10] + $a[11] + $a[12], 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'SD',       'ancho' => $a[13], 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'FT',       'ancho' => $a[14], 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'SALARIO',  'ancho' => $a[15], 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'DT',       'ancho' => $a[16], 'direccion' => 'C', 'bordes' => 'LTR'],
        ];

        $campos1 = [
            ['titulo' => 'EXP',     'ancho' => $a[0],  'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'NOMBRE',  'ancho' => $a[1],  'direccion' => 'L', 'bordes' => 'LR', 'letra' => 9],
            ['titulo' => '',        'ancho' => $a[2],  'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => '',        'ancho' => $a[3],  'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'ESCALA',  'ancho' => $a[4],  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'CLA',     'ancho' => $a[5],  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'STRT',    'ancho' => $a[6],  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'TRANSP',  'ancho' => $a[7],  'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'FORMADO', 'ancho' => $a[8],  'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'ISInicial', 'ancho' => $a[9],  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'Pen %',   'ancho' => $a[10], 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'Pen $',   'ancho' => $a[11], 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'ISFinal', 'ancho' => $a[12], 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => '',        'ancho' => $a[13], 'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => '',        'ancho' => $a[14], 'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => '',        'ancho' => $a[15], 'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => '',        'ancho' => $a[16], 'direccion' => 'C', 'bordes' => 'LR'],
        ];

        $campos2 = [
            ['titulo' => '',       'ancho' => $a[0],  'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',       'ancho' => $a[1],  'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 9],
            ['titulo' => '',       'ancho' => $a[2],  'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',       'ancho' => $a[3],  'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',       'ancho' => $a[4],  'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',       'ancho' => $a[5],  'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',       'ancho' => $a[6],  'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'REAL',   'ancho' => $a[7],  'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',       'ancho' => $a[8],  'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',       'ancho' => $a[9],  'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',       'ancho' => $a[10], 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',       'ancho' => $a[11], 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',       'ancho' => $a[12], 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',       'ancho' => $a[13], 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',       'ancho' => $a[14], 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'TOTAL',  'ancho' => $a[15], 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'TOTAL',  'ancho' => $a[16], 'direccion' => 'C', 'bordes' => 'LRB'],
        ];

        $this->titulos($campos, $campos1, $campos2, 6, 5, 35);
        $this->firmasSalario('SISTEMA PAGO CHOFERES', 'L', 170);

        $posY = 53;
        $max = 22;
        $i = 1;
        $nro = 1;

        $tot = $data['totales'];

        foreach ($data['registros'] as $arr) {
            if ($i >= $max) {
                $this->inicio($titulo, 50, 5);
                $this->titulos($campos, $campos1, $campos2, 6, 5, 35);
                $this->firmasSalario('SISTEMA PAGO CHOFERES', 'L', 170);
                $posY = 53;
                $i = 0;
            }

            $this->SetXY(5, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->SetFont('Arial', '', 10);
            $this->Cell($a[0], 6, $arr['exp'] ?? '', 1, 0, 'C', 1);
            $this->Cell($a[1], 6, $this->latin1(mb_strtoupper(mb_strtolower($arr['nombrecompleto'] ?? ''))), 1, 0, 'L', 1);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell($a[2], 6, $this->fmt($arr['tarifa'] ?? 0, 4), 1, 0, 'R', 1);
            $this->Cell($a[3], 6, $this->fmt($arr['ttotal'] ?? 0), 1, 0, 'R', 1);
            $this->Cell($a[4], 6, $this->fmt($arr['escala'] ?? 0), 1, 0, 'R', 1);
            $this->Cell($a[5], 6, $this->fmt($arr['cla'] ?? 0), 1, 0, 'R', 1);
            $this->Cell($a[6], 6, $this->fmt($arr['strt'] ?? 0), 1, 0, 'R', 1);
            $this->Cell($a[7], 6, $this->fmt($arr['ingresos'] ?? 0), 1, 0, 'R', 1);
            $this->Cell($a[8], 6, $this->fmt($arr['fondo'] ?? 0), 1, 0, 'R', 1);
            $this->Cell($a[9], 6, $this->fmt($arr['is_inicial'] ?? 0), 1, 0, 'R', 1);
            $this->Cell($a[10], 6, $arr['pen_resultado'] ? $this->fmt($arr['pen_resultado'], 0) : '', 1, 0, 'R', 1);
            $this->Cell($a[11], 6, $this->fmt($arr['pen_importe'] ?? 0), 1, 0, 'R', 1);
            $this->Cell($a[12], 6, $this->fmt($arr['is_final'] ?? 0), 1, 0, 'R', 1);
            $this->Cell($a[13], 6, $this->fmt($arr['sd'] ?? 0), 1, 0, 'R', 1);
            $this->Cell($a[14], 6, $this->fmt($arr['ft'] ?? 0), 1, 0, 'R', 1);
            $this->Cell($a[15], 6, $this->fmt($arr['salario'] ?? 0), 1, 0, 'R', 1);
            $this->Cell($a[16], 6, $this->fmt($arr['dt'] ?? 0, 0), 1, 0, 'R', 1);
            $posY += 6;
            $i++;
            $nro++;
        }

        // Fila de totales (replica Reportes.php:2647-2666).
        $this->SetFont('Arial', 'B', 11);
        $this->SetXY(5, $posY);
        $this->SetFillColor($this->getFillColor());
        $this->Cell($a[0], 10, $nro - 1, 1, 0, 'C', 1);
        $this->Cell($a[1], 10, 'TOTALES', 1, 0, 'C', 1);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($a[2], 10, '', 1, 0, 'R', 1);
        $this->Cell($a[3], 10, $this->fmt($tot['ttotal'] ?? 0), 1, 0, 'R', 1);
        $this->Cell($a[4], 10, $this->fmt($tot['escala'] ?? 0), 1, 0, 'R', 1);
        $this->Cell($a[5], 10, $this->fmt($tot['cla'] ?? 0), 1, 0, 'R', 1);
        $this->Cell($a[6], 10, $this->fmt($tot['strt'] ?? 0), 1, 0, 'R', 1);
        $this->Cell($a[7], 10, $this->fmt($tot['ingresos'] ?? 0), 1, 0, 'R', 1);
        $this->Cell($a[8], 10, $this->fmt($tot['fondo'] ?? 0), 1, 0, 'R', 1);
        $this->Cell($a[9], 10, $this->fmt($tot['is_inicial'] ?? 0), 1, 0, 'R', 1);
        $this->Cell($a[10], 10, '', 1, 0, 'R', 1);
        $this->Cell($a[11], 10, $this->fmt($tot['pen_importe'] ?? 0), 1, 0, 'R', 1);
        $this->Cell($a[12], 10, $this->fmt($tot['is_final'] ?? 0), 1, 0, 'R', 1);
        $this->Cell($a[13], 10, $this->fmt($tot['sd'] ?? 0), 1, 0, 'R', 1);
        $this->Cell($a[14], 10, $this->fmt($tot['ft'] ?? 0), 1, 0, 'R', 1);
        $this->Cell($a[15], 10, $this->fmt($tot['salario'] ?? 0), 1, 0, 'R', 1);
        $this->Cell($a[16], 10, $this->fmt($tot['dt'] ?? 0, 0), 1, 0, 'R', 1);

        return $this->Output('S');
    }

    private function getFillColor(): int
    {
        return (session('FillColor') ?? 200);
    }
}
