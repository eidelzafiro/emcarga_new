<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;
use Carbon\Carbon;

/**
 * DATOS DE LOS TRABAJADORES — réplica FPDF del legacy.
 * Reportes.php (pdf_salario_datos_trabajadores) + ModMovimientos::mostrar_todos.
 *
 * Lista los trabajadores de un sistema de pago agrupados por área.
 * Columnas: NOMBRE DEL TRABAJADOR(60), EXPEDIENTE(25), NUMERO EN VERSAT(25),
 * GRUPO ESCALA(20), CAT OCUP(15), DENOMINACION DEL CARGO(95), FECHA INGRESO(25).
 * Formato: Letter horizontal.
 */
class DatosTrabajadoresFpdfReport extends ReportesnewFpdfBase
{
    private int $idSistemaPago;

    public function __construct(?int $entidadId, string $mes, string $ano, int $idSistemaPago)
    {
        parent::__construct($entidadId, $mes, $ano, [], 'L', 'Letter');
        $this->idSistemaPago = $idSistemaPago;
    }

    public function generate(): string
    {
        $data = app(ReportePrenominaService::class)
            ->datosTrabajadores($this->idSistemaPago, $this->entidadId);

        $titulo = 'CONTROL DATOS GENERALES DEL TRABAJADOR';

        $campos = [
            ['titulo' => 'NOMBRE DEL TRABAJADOR',  'ancho' => 60, 'direccion' => 'L'],
            ['titulo' => 'EXPEDIENTE',             'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'NUMERO',                 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'GRUPO',                  'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'CAT',                    'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'DENOMINACION DEL CARGO', 'ancho' => 95, 'direccion' => 'C'],
            ['titulo' => 'FECHA',                  'ancho' => 25, 'direccion' => 'C'],
        ];

        $campos1 = [
            ['titulo' => '',           'ancho' => 60, 'direccion' => 'L'],
            ['titulo' => 'LABORAL',    'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'EN VERSAT',  'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'ESCALA',     'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'OCUP',       'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => '',           'ancho' => 95, 'direccion' => 'C'],
            ['titulo' => 'INGRESO',    'ancho' => 25, 'direccion' => 'C'],
        ];

        $registros = $data['registros'] ?? [];

        if (empty($registros)) {
            $this->inicio($this->latin1($titulo));
            $this->SetFont('Arial', 'B', 18);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');

            return $this->Output('S');
        }

        $this->inicio($this->latin1($titulo));
        $this->titulos($campos1, $campos, [], 6, 10, 30);
        $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'L');

        $posY = 42;
        $max = 24;
        $i = 0;
        $area = null;

        foreach ($registros as $r) {
            if ($area !== $r['area']) {
                $area = $r['area'];
                $this->SetFont('Arial', 'B', 12);
                $this->SetFillColor(999, 999, 999);
                $this->SetXY(10, $posY);
                $this->Cell(265, 6, $this->latin1((string) $area), 1, 0, 'C', 1);
                $posY += 6;
                $i++;
            }

            if ($i >= $max) {
                $this->inicio($this->latin1($titulo));
                $this->titulos($campos1, $campos, [], 6, 10, 35);
                $this->firmasSalario('SISTEMA PAGO ADMINISTRATIVO', 'L');
                $posY = 45;
                $i = 0;
            }

            $this->SetFont('Arial', '', 10);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, $posY);
            $this->Cell(60, 6, $this->latin1(ucwords(mb_strtolower((string) $r['nombrecompleto']))), 1, 0, 'L', 1);
            $this->Cell(25, 6, $this->latin1((string) $r['nronomina']), 1, 0, 'L', 1);
            $this->Cell(25, 6, $this->latin1((string) $r['versat']), 1, 0, 'C', 1);
            $this->Cell(20, 6, $this->latin1((string) $r['grupo']), 1, 0, 'C', 1);
            $this->Cell(15, 6, $this->latin1((string) $r['categoria']), 1, 0, 'C', 1);
            $this->Cell(95, 6, $this->latin1((string) $r['cargo']), 1, 0, 'L', 1);
            $this->Cell(25, 6, $this->fecha((string) $r['fecha_ingreso']), 1, 0, 'C', 1);

            $posY += 6;
            $i++;
        }

        return $this->Output('S');
    }

    private function fecha(string $valor): string
    {
        if ($valor === '' || str_starts_with($valor, '0000-00-00')) {
            return '';
        }

        try {
            return Carbon::parse($valor)->format('d/m/Y');
        } catch (\Throwable) {
            return $valor;
        }
    }
}
