<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * PENALIZACIONES X PAGO ADICIONAL — réplica FPDF del legacy.
 * Reportes.php (pdf_salario_penalizacion) + ModPenalizacion::mostrar_reporte.
 *
 * Lista las penalizaciones de UN tipo de pago adicional (origen_id del catálogo
 * `tipos_pagos_adicionales`) en el mes/año de operaciones, ordenadas por área y
 * número de nómina. Columnas: EXP(25), NOMBRE DEL EMPLEADO(70), CAUSA(140), %(15).
 * Formato: Letter horizontal.
 */
class PenalizacionesFpdfReport extends ReportesnewFpdfBase
{
    private int $origenPago;

    public function __construct(?int $entidadId, string $mes, string $ano, int $origenPago)
    {
        parent::__construct($entidadId, $mes, $ano, [], 'L', 'Letter');
        $this->origenPago = $origenPago;
    }

    public function generate(): string
    {
        $data = app(ReportePrenominaService::class)
            ->penalizacionesPorPago((int) $this->mes, (int) $this->ano, $this->origenPago, $this->entidadId);

        $titulo = 'PENALIZACIONES '.trim(mb_strtoupper($data['titulo_tipo'] ?? ''));

        $campos = [
            ['titulo' => 'EXP',                 'ancho' => 25,  'direccion' => 'C'],
            ['titulo' => 'NOMBRE DEL EMPLEADO', 'ancho' => 70,  'direccion' => 'L'],
            ['titulo' => 'CAUSA',               'ancho' => 140, 'direccion' => 'L', 'letra' => 9],
            ['titulo' => '%',                   'ancho' => 15,  'direccion' => 'R'],
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

        foreach ($registros as $r) {
            if ($i >= $max) {
                $this->inicio($this->latin1($titulo), 50, 5);
                $this->titulos($campos, [], [], 6, 10, 35);
                $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'L');
                $posY = 45;
                $i = 0;
            }

            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->SetFont('Arial', '', 10);
            $this->Cell(25, 6, (string) $r['nronomina'], 1, 0, 'C', 1);
            $this->Cell(70, 6, $this->latin1(ucwords(mb_strtolower((string) $r['nombrecompleto']))), 1, 0, 'L', 1);
            $this->SetFont('Arial', '', 9);
            $this->Cell(140, 6, $this->latin1(mb_substr((string) $r['causa'], 0, 60)), 1, 0, 'L', 1);
            $this->SetFont('Arial', '', 10);
            $this->Cell(15, 6, $this->fmtVar($r['importe'], 2), 1, 0, 'R', 1);

            $posY += 6;
            $i++;
        }

        return $this->Output('S');
    }
}
