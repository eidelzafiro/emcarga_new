<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * CERTIFICACION CHOFERES AREA COMERCIAL — réplica FPDF del legacy.
 * Reportes2.php (pdf_chofer_certificacion / _tnskms) + ModAforo::reporte_choferes.
 *
 * Por chofer: CP y viajes reales vs plan, toneladas reales vs plan e ingresos
 * reales vs plan, con su % de cumplimiento. Formato: Legal horizontal.
 */
class CertificacionComercialFpdfReport extends ReportesnewFpdfBase
{
    public function __construct(?int $entidadId, string $mes, string $ano)
    {
        parent::__construct($entidadId, $mes, $ano, [], 'L', 'Legal');
    }

    public function generate(): string
    {
        $data = app(ReportePrenominaService::class)
            ->certificacionComercial((int) $this->mes, (int) $this->ano, $this->entidadId);

        $titulo = 'CERTIFICACION CHOFERES AREA COMERCIAL';

        $campos = [
            ['titulo' => 'VERSAT',      'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'CHOFER',      'ancho' => 70, 'direccion' => 'C'],
            ['titulo' => 'CP',          'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'VIAJES PLAN', 'ancho' => 27, 'direccion' => 'C', 'letra' => 9],
            ['titulo' => 'VIAJES REAL', 'ancho' => 27, 'direccion' => 'C', 'letra' => 9],
            ['titulo' => '%',           'ancho' => 16, 'direccion' => 'C'],
            ['titulo' => 'TNS PLAN',    'ancho' => 27, 'direccion' => 'C', 'letra' => 9],
            ['titulo' => 'TNS REAL',    'ancho' => 27, 'direccion' => 'C', 'letra' => 9],
            ['titulo' => '%',           'ancho' => 16, 'direccion' => 'C'],
            ['titulo' => 'ING PLAN',    'ancho' => 30, 'direccion' => 'C', 'letra' => 9],
            ['titulo' => 'ING REAL',    'ancho' => 30, 'direccion' => 'C', 'letra' => 9],
            ['titulo' => '%',           'ancho' => 16, 'direccion' => 'C'],
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
        $this->titulos($campos, [], [], 6, 10, 30);

        $posY = 36;
        $max = 21;
        $i = 0;

        foreach ($registros as $r) {
            if ($i >= $max) {
                $this->inicio($this->latin1($titulo), 50, 5);
                $this->titulos($campos, [], [], 6, 10, 35);
                $posY = 45;
                $i = 0;
            }

            $this->SetFont('Arial', '', 9);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, $posY);
            $this->Cell(25, 6, $this->latin1((string) $r['versat']), 1, 0, 'C', 1);
            $this->Cell(70, 6, $this->latin1(ucwords(mb_strtolower((string) $r['nombrecompleto']))), 1, 0, 'L', 1);
            $this->Cell(20, 6, (string) $r['nrocp'], 1, 0, 'C', 1);
            $this->Cell(27, 6, $this->fmtVar($r['planviajes'], 2), 1, 0, 'R', 1);
            $this->Cell(27, 6, $this->fmtVar($r['viajes'], 2), 1, 0, 'R', 1);
            $this->Cell(16, 6, $this->fmtVar($r['cumplimientoviajes'], 2), 1, 0, 'R', 1);
            $this->Cell(27, 6, $this->fmtVar($r['plantns'], 2), 1, 0, 'R', 1);
            $this->Cell(27, 6, $this->fmtVar($r['tnreal'], 2), 1, 0, 'R', 1);
            $this->Cell(16, 6, $this->fmtVar($r['cumplimientotns'], 2), 1, 0, 'R', 1);
            $this->Cell(30, 6, $this->fmtVar($r['planmensual'], 2), 1, 0, 'R', 1);
            $this->Cell(30, 6, $this->fmtVar($r['produccion'], 2), 1, 0, 'R', 1);
            $this->Cell(16, 6, $this->fmtVar($r['cumplimiento'], 2), 1, 0, 'R', 1);

            $posY += 6;
            $i++;
        }

        return $this->Output('S');
    }
}
