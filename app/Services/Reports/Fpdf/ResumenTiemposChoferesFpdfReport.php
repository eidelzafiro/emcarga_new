<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * MODELO RESUMEN DE LOS TIEMPOS CHOFERES TRANSPORTACION — réplica FPDF del legacy.
 * Reportes.php:2939 (npdf_salario_choferes_tiempos).
 *
 * DISTRIBUCION DE LOS TIEMPOS: VERSAT, NOMBRE, MTD(tperm), MOV(tmov),
 * CARGA(tcarga), DESCA(tdescarga), TOTAL(ttotal).
 * DESCUENTOS: VAC, SUB, REUB, RECALIF, INT, FNT/RECESO, OTROS, TOTAL.
 *
 * Formato: Legal horizontal. Solo choferes con ttotal > 0. Fila TOTALES
 * (réplica del legacy: columnas de descuentos con la mezcla de variables
 * original — regular/irregular/tgarantia/ttotal repetidos).
 */
class ResumenTiemposChoferesFpdfReport extends ReportesnewFpdfBase
{
    public function __construct(?int $entidadId, string $mes, string $ano)
    {
        parent::__construct($entidadId, $mes, $ano, [], 'L', 'Legal');
    }

    public function generate(): string
    {
        $service = app(ReportePrenominaService::class);
        $data = $service->tiemposChoferes((int) $this->mes, (int) $this->ano, $this->entidadId);

        $titulo = 'MODELO RESUMEN DE LOS TIEMPOS CHOFERES TRANSPORTACION';

        // Cabecera superior (agrupada) — réplica Reportes.php:2950-2954.
        $superior = [
            ['titulo' => 'NRO',                         'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => '',                            'ancho' => 75, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'DISTRIBUCION DE LOS TIEMPOS',  'ancho' => 75, 'direccion' => 'C'],
            ['titulo' => 'DESCUENTOS',                   'ancho' => 160, 'direccion' => 'C'],
        ];

        // Cabecera inferior (subcolumnas).
        $medio = [
            ['titulo' => 'VERSAT',                'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 10],
            ['titulo' => 'NOMBRE DEL TRABAJADOR', 'ancho' => 75, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'MTD',                   'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'MOV',                   'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'CARGA',                 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'DESCA',                 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'TOTAL',                 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'VAC',                   'ancho' => 20, 'direccion' => 'C', 'letra' => 8],
            ['titulo' => 'SUB',                   'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'REUB',                  'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'RECALIF',               'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'INT',                   'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'FNT/RECESO',            'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'OTROS',                 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'TOTAL',                 'ancho' => 20, 'direccion' => 'C'],
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
        $this->firmasSalario('SISTEMA PAGO CHOFERES', 'L');

        $posY = 42;
        $max = 23;

        $tot = $data['totales'] ?? [];

        foreach ($registros as $r) {
            if ($posY - 42 + 6 > ($max * 6)) {
                $this->inicio($this->latin1($titulo), 50, 5);
                $this->titulos($superior, $medio, [], 6, 10, 30);
                $this->firmasSalario('SISTEMA PAGO CHOFERES', 'L');
                $posY = 42;
            }

            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->SetFont('Arial', '', 12);
            $this->Cell(25, 6, $r['versat'] ?? '', 1, 0, 'C', 1);
            $this->Cell(75, 6, $this->latin1(ucwords(strtolower($r['nombrecompleto'] ?? ''))), 1, 0, 'L', 1);
            $this->Cell(15, 6, $this->fmtVar($r['tperm'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell(15, 6, $this->fmtVar($r['tmov'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell(15, 6, $this->fmtVar($r['tcarga'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell(15, 6, $this->fmtVar($r['tdescarga'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell(15, 6, $this->fmtVar($r['ttotal'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmtVar($r['vacaciones'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmtVar($r['subsidios'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmtVar($r['reubicado'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmtVar($r['recalificacion'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmtVar($r['tgarantia'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmtVar($r['receso'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmtVar($r['totros'] ?? 0, 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmtVar($r['tincidencias'] ?? 0, 2), 1, 0, 'R', 1);
            $posY += 6;
        }

        // Fila TOTALES (réplica Reportes.php:3039-3056: 100 ancho + tiempos
        // y las columnas de descuentos con la mezcla de variables del legacy:
        // vacaciones='', subsidios, regular, irregular, tgarantia, ttotal, tgarantia, ttotal).
        $this->SetXY(10, $posY);
        $this->SetFillColor($this->getFillColor());
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(100, 10, 'TOTALES', 1, 0, 'C', 1);
        $this->Cell(15, 10, $this->fmtVar($tot['tperm'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(15, 10, $this->fmtVar($tot['tmov'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(15, 10, $this->fmtVar($tot['tcarga'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(15, 10, $this->fmtVar($tot['tdescarga'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(15, 10, $this->fmtVar($tot['ttotal'] ?? 0, 2), 1, 0, 'R', 1);
        // Descuentos (mezcla legacy): VAC='', SUB=subsidios, REUB=regular,
        // RECALIF=irregular, INT=tgarantia, FNT/RECESO=ttotal, OTROS=tgarantia, TOTAL=ttotal.
        $this->Cell(20, 10, '', 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($tot['subsidios'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($tot['reubicado'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($tot['recalificacion'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($tot['tgarantia'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($tot['ttotal'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($tot['tgarantia'] ?? 0, 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($tot['ttotal'] ?? 0, 2), 1, 0, 'R', 1);

        return $this->Output('S');
    }

    private function getFillColor(): int
    {
        return (session('FillColor') ?? 200);
    }
}
