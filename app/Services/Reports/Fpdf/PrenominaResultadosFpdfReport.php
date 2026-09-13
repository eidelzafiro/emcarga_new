<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * SALARIOS X SISTEMA DE PAGO CON RESULTADOS (administrativos) — réplica FPDF
 * del legacy Reportes.php (pdf_salario_prenomina_resultado_adm, #4067/#4068).
 *
 * Columnas: No, Cod, NOMBRE, CARGO, TRT, SE, SCLA, STRT, SRInicial, Pen,
 * SRFinal, NOCTURNIDAD, MAESTRIA, H/EXTRAS, PAGOS, SALARIO.
 * Formato: Legal horizontal.
 */
class PrenominaResultadosFpdfReport extends ReportesnewFpdfBase
{
    public function __construct(?int $entidadId, string $mes, string $ano)
    {
        parent::__construct($entidadId, $mes, $ano, [], 'L', 'Legal');
    }

    public function generate(): string
    {
        $data = app(ReportePrenominaService::class)
            ->prenominaResultadosAdmin((int) $this->mes, (int) $this->ano, $this->entidadId);

        $titulo = 'SALARIOS X SISTEMA DE PAGO CON RESULTADOS';

        $campos = [
            ['titulo' => 'No',                'ancho' => 10, 'direccion' => 'C'],
            ['titulo' => 'Cod',               'ancho' => 10, 'direccion' => 'C'],
            ['titulo' => 'NOMBRE Y APELLIDOS', 'ancho' => 50, 'direccion' => 'C', 'letra' => 8],
            ['titulo' => 'CARGO',             'ancho' => 45, 'direccion' => 'C', 'letra' => 10],
            ['titulo' => 'TRT',               'ancho' => 15, 'direccion' => 'C', 'letra' => 8],
            ['titulo' => 'SE',                'ancho' => 20, 'direccion' => 'C', 'letra' => 8],
            ['titulo' => 'SCLA',              'ancho' => 20, 'direccion' => 'C', 'letra' => 8],
            ['titulo' => 'STRT',              'ancho' => 20, 'direccion' => 'C', 'letra' => 8],
            ['titulo' => 'SRInicial',         'ancho' => 20, 'direccion' => 'C', 'letra' => 8],
            ['titulo' => 'Pen',               'ancho' => 18, 'direccion' => 'C', 'letra' => 8],
            ['titulo' => 'SRFinal',           'ancho' => 20, 'direccion' => 'C', 'letra' => 8],
            ['titulo' => 'NOCTURNIDAD',       'ancho' => 22, 'direccion' => 'C', 'letra' => 7],
            ['titulo' => 'MAESTRIA',          'ancho' => 20, 'direccion' => 'C', 'letra' => 7],
            ['titulo' => 'H/EXTRAS',          'ancho' => 20, 'direccion' => 'C', 'letra' => 7],
            ['titulo' => 'PAGOS',             'ancho' => 18, 'direccion' => 'C', 'letra' => 7],
            ['titulo' => 'SALARIO',           'ancho' => 22, 'direccion' => 'C', 'letra' => 8],
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
        $this->titulos($campos, [], [], 6, 15, 35);
        $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'L');

        $posY = 42;
        $max = 20;
        $i = 0;
        $nro = 1;

        foreach ($registros as $r) {
            if ($i >= $max) {
                $this->inicio($this->latin1($titulo), 50, 5);
                $this->titulos($campos, [], [], 6, 15, 35);
                $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'L');
                $posY = 45;
                $i = 0;
            }

            $this->SetFont('Arial', '', 8);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, $posY);
            $this->Cell(10, 6, (string) $nro, 1, 0, 'C', 1);
            $this->Cell(10, 6, $this->latin1((string) $r['codigo']), 1, 0, 'C', 1);
            $this->Cell(50, 6, $this->latin1(ucwords(mb_strtolower((string) $r['nombrecompleto']))), 1, 0, 'L', 1);
            $this->Cell(45, 6, $this->latin1(mb_substr((string) $r['cargo'], 0, 28)), 1, 0, 'L', 1);
            $this->Cell(15, 6, $this->fmtVar($r['trt'], 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmtVar($r['se'], 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmtVar($r['scla'], 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmtVar($r['strt'], 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmtVar($r['srinicial'], 2), 1, 0, 'R', 1);
            $this->Cell(18, 6, $this->fmtVar($r['pen'], 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmtVar($r['srfinal'], 2), 1, 0, 'R', 1);
            $this->Cell(22, 6, $this->fmtVar($r['nocturnidad'], 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmtVar($r['maestria'], 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmtVar($r['hextras'], 2), 1, 0, 'R', 1);
            $this->Cell(18, 6, $this->fmtVar($r['pagos'], 2), 1, 0, 'R', 1);
            $this->Cell(22, 6, $this->fmtVar($r['salario'], 2), 1, 0, 'R', 1);

            $posY += 6;
            $i++;
            $nro++;
        }

        return $this->Output('S');
    }
}
