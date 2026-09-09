<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * MODELO ANALISIS DEL SALARIO TRANSPORTACION — réplica FPDF del legacy.
 * Reportes.php:3068 (npdf_salario_choferes_resumen_analisis).
 *
 * Columnas: NOMBRE, INGRESOS, TIEMPO TRANSPOR, GARANTIA, TOTAL,
 * COEFICIENTE, TIEMPO TRABAJADO, RESULTADO, TOTAL, GARANTIA,
 * NORMA SALARIAL, TARIFA TRANSPORTA, TARIFA GENERAL.
 *
 * Formato: Letter horizontal con cabecera de 3 filas (campos/campos1/campos2
 * del legacy — la del medio está en letra 6 y la inferior en letra 5/6).
 * Solo choferes con ttotal > 0 || tgarantia > 0.
 */
class AnalisisSalarioTransportacionFpdfReport extends ReportesnewFpdfBase
{
    public function __construct(?int $entidadId, string $mes, string $ano)
    {
        parent::__construct($entidadId, $mes, $ano, [], 'L', 'Letter');
    }

    public function generate(): string
    {
        $service = app(ReportePrenominaService::class);
        $data = $service->analisisTransportacion((int) $this->mes, (int) $this->ano, $this->entidadId);

        $titulo = 'MODELO ANALISIS DEL SALARIO TRANSPORTACION';

        // Fila superior (grupos) — réplica Reportes.php:3077-3082.
        $campos = [
            ['titulo' => '',                        'ancho' => 55, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => '',                        'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'TIEMPO',                  'ancho' => 45, 'direccion' => 'C'],
            ['titulo' => 'DISTRIBUCION SALARIO',    'ancho' => 88, 'direccion' => 'C'],
            ['titulo' => 'INDICADORES',             'ancho' => 40, 'direccion' => 'C'],
        ];

        // Fila media — réplica Reportes.php:3084-3097 (letra 6/8).
        $campos1 = [
            ['titulo' => 'NOMBRE DEL TRABAJADOR', 'ancho' => 55, 'direccion' => 'C', 'bordes' => 'LR',  'letra' => 8],
            ['titulo' => 'INGRESOS',              'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LR',  'letra' => 8],
            ['titulo' => 'TRANSPOR',              'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 6],
            ['titulo' => 'GARANTIA',              'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 6],
            ['titulo' => 'TOTAL',                 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 6],
            ['titulo' => 'COEFICIENTE',           'ancho' => 17, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 6],
            ['titulo' => 'TIEMPO',                'ancho' => 17, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 6],
            ['titulo' => 'RESULTADO',             'ancho' => 17, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 6],
            ['titulo' => 'TOTAL',                 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 6],
            ['titulo' => 'GARANTIA',              'ancho' => 17, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 6],
            ['titulo' => 'NORMA',                 'ancho' => 13, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 6],
            ['titulo' => 'TARIFA',                'ancho' => 13, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 6],
            ['titulo' => 'TARIFA',                'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 6],
        ];

        // Fila inferior — réplica Reportes.php:3099-3113 (letra 5/6).
        $campos2 = [
            ['titulo' => '',              'ancho' => 55, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 6],
            ['titulo' => '',              'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 6],
            ['titulo' => 'TACION',        'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 6],
            ['titulo' => 'SALARIAL',      'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 6],
            ['titulo' => '',              'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 6],
            ['titulo' => 'NORMATIVO',     'ancho' => 17, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 6],
            ['titulo' => 'TRABAJADO',     'ancho' => 17, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 6],
            ['titulo' => '',              'ancho' => 17, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 6],
            ['titulo' => '',              'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 6],
            ['titulo' => 'SALARIAL',      'ancho' => 17, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 6],
            ['titulo' => 'SALARIAL',      'ancho' => 13, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 5],
            ['titulo' => 'TRANSPORTA',    'ancho' => 13, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 5],
            ['titulo' => 'GENERAL',       'ancho' => 14, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 5],
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
        $this->titulos($campos, $campos1, $campos2, 6, 10, 30);
        $this->firmasSalario('SISTEMA PAGO CHOFERES', 'L');

        $posY = 48;
        $max = 21;

        foreach ($registros as $r) {
            if ($posY - 48 + 6 > ($max * 6)) {
                $this->inicio($this->latin1($titulo), 50, 5);
                $this->titulos($campos, $campos1, $campos2, 6, 10, 30);
                $this->firmasSalario('SISTEMA PAGO CHOFERES', 'L');
                $posY = 48;
            }

            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->SetFont('Arial', '', 9);
            $this->Cell(55, 6, $this->latin1(ucwords(strtolower($r['nombrecompleto'] ?? ''))), 1, 0, 'L', 1);
            $this->SetFont('Arial', '', 11);
            $this->Cell(20, 6, $this->fmtVar($r['ingresos'], 2), 1, 0, 'R', 1);
            $this->Cell(15, 6, $this->fmtVar($r['ttotal'], 2), 1, 0, 'R', 1);
            $this->Cell(15, 6, $this->fmtVar($r['tgarantia'], 2), 1, 0, 'R', 1);
            $this->Cell(15, 6, $this->fmtVar($r['ttotal'], 2), 1, 0, 'R', 1);
            $this->Cell(17, 6, $this->fmtVar($r['coeficiente'], 2), 1, 0, 'R', 1);
            $this->Cell(17, 6, $this->fmtVar($r['tiempo_trabajado'], 2), 1, 0, 'R', 1);
            $this->Cell(17, 6, $this->fmtVar($r['resultado'], 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmtVar($r['total'], 2), 1, 0, 'R', 1);
            $this->Cell(17, 6, $this->fmtVar($r['garantia'], 2), 1, 0, 'R', 1);
            $this->SetFont('Arial', 'B', 13);
            $this->Cell(13, 6, $this->fmtVar($r['normasalarial'], 2), 1, 0, 'R', 1);
            $this->Cell(13, 6, $this->fmtVar($r['normatransp'], 2), 1, 0, 'R', 1);
            $this->Cell(14, 6, $this->fmtVar($r['normatotal'], 2), 1, 0, 'R', 1);
            $posY += 6;
        }

        // Fila TOTALES — réplica Reportes.php:3179-3198.
        $tot = $data['totales'] ?? [];
        $this->SetFont('Arial', 'B', 11);
        $this->SetXY(10, $posY);
        $this->SetFillColor($this->fillGris());
        $this->Cell(55, 10, 'TOTALES', 1, 0, 'C', 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 10, $this->fmtVar($tot['ingresos'], 2), 1, 0, 'R', 1);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(15, 10, $this->fmtVar($tot['ttotal'], 2), 1, 0, 'R', 1);
        $this->Cell(15, 10, $this->fmtVar($tot['tgarantia'], 2), 1, 0, 'R', 1);
        $this->Cell(15, 10, $this->fmtVar($tot['ttotal'], 2), 1, 0, 'R', 1);
        $this->Cell(17, 10, $this->fmtVar($tot['coeficiente'], 2), 1, 0, 'R', 1);
        $this->Cell(17, 10, $this->fmtVar($tot['tiempo_trabajado'], 2), 1, 0, 'R', 1);
        $this->Cell(17, 10, $this->fmtVar($tot['resultado'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($tot['total'], 2), 1, 0, 'R', 1);
        $this->Cell(17, 10, $this->fmtVar($tot['garantia'], 2), 1, 0, 'R', 1);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(13, 10, $this->fmtVar($tot['normasalarial'], 2), 1, 0, 'R', 1);
        $this->Cell(13, 10, $this->fmtVar($tot['normatransp'], 2), 1, 0, 'R', 1);
        $this->Cell(14, 10, $this->fmtVar($tot['normatotal'], 2), 1, 0, 'R', 1);

        return $this->Output('S');
    }
}
