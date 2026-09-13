<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * EMPLEADOS CON GARANTIA SALARIAL — réplica FPDF del legacy.
 * Reportes.php (npdf_listado_choferes_garantia) + ModBolsa::mostrar_garantia.
 *
 * Lista los choferes de TRANSPORTACION con garantía salarial > 0.
 * Columnas: NRO(15), EMPLEADO(85), TIEMPO(30). Formato: Letter vertical.
 */
class GarantiaSalarialFpdfReport extends ReportesnewFpdfBase
{
    public function generate(): string
    {
        $data = app(ReportePrenominaService::class)->garantiaSalarial($this->entidadId);
        $titulo = 'EMPLEADOS CON GARANTIA SALARIAL';

        $campos = [
            ['titulo' => 'NRO',      'ancho' => 15, 'direccion' => 'L'],
            ['titulo' => 'EMPLEADO', 'ancho' => 85, 'direccion' => 'L'],
            ['titulo' => 'TIEMPO',   'ancho' => 30, 'direccion' => 'C'],
        ];

        $registros = $data['registros'] ?? [];

        if (empty($registros)) {
            $this->inicio($this->latin1($titulo), 50, 5);
            $this->SetFont('Arial', 'B', 18);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');

            return $this->Output('S');
        }

        $this->inicio($this->latin1($titulo), 50, 5);
        $this->titulos($campos, [], [], 6, 10, 30);

        $posY = 36;
        $max = 33;
        $i = 0;
        $nro = 1;

        foreach ($registros as $r) {
            if ($i >= $max) {
                $this->inicio($this->latin1($titulo), 50, 5);
                $this->titulos($campos, [], [], 6, 10, 35);
                $posY = 45;
                $i = 0;
            }

            $this->SetFont('Arial', '', 10);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, $posY);
            $this->Cell(15, 6, (string) $nro, 1, 0, 'L', 1);
            $this->Cell(85, 6, $this->latin1(ucwords(mb_strtolower((string) $r['nombrecompleto']))), 1, 0, 'L', 1);
            $this->Cell(30, 6, (string) $r['garantia'], 1, 0, 'C', 1);

            $posY += 6;
            $i++;
            $nro++;
        }

        return $this->Output('S');
    }
}
