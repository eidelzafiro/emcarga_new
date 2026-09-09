<?php

namespace App\Services\Reports\Fpdf;

use App\Models\Bolsa;
use Illuminate\Support\Facades\DB;

/**
 * LISTADO DE CUMPLEAÑOS DEL MES — réplica FPDF del legacy.
 * Reportes.php:606 (pdf_empleados_cumple) + ModBolsa.php:172 (mostrar_cumple).
 *
 * La fecha de nacimiento se deriva del CI cubano (YYMMDD), NO de un campo fecha.
 * Columnas: NRO(15), EMPLEADO(85), FECHA(25), EDAD(25). Letra datos Arial 12.
 * Orden: cidentidad DESC. Formato 'P' Letter.
 */
class CumpleanosFpdfReport extends ReportesnewFpdfBase
{
    public function __construct(?int $entidadId, string $mes, string $ano)
    {
        parent::__construct($entidadId, $mes, $ano, [], 'P', 'Letter');
    }

    public function generate(): string
    {
        $titulo = 'LISTADO DE CUMPLEAÑOS DEL MES';

        $campos = [
            ['titulo' => 'NRO',      'ancho' => 15, 'direccion' => 'L'],
            ['titulo' => 'EMPLEADO', 'ancho' => 85, 'direccion' => 'L'],
            ['titulo' => 'FECHA',    'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'EDAD',     'ancho' => 25, 'direccion' => 'C'],
        ];

        $data = $this->obtenerDatos();

        if (empty($data)) {
            $this->inicio($this->latin1($titulo), 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
            return $this->Output('S');
        }

        $this->inicio($this->latin1($titulo), 50, 5);
        $this->titulos($campos, [], [], 6, 15, 30);

        $this->SetFillColor(999, 999, 999);
        $posY = 36;
        $nro = 1;
        $max = 35;
        $e = 0;

        foreach ($data as $arr) {
            if ($e == $max) {
                $this->inicio($this->latin1($titulo), 50, 5);
                $this->titulos($campos, [], [], 6, 15, 30);
                $this->SetFillColor(999, 999, 999);
                $e = 0;
                $posY = 36;
            }

            $this->SetFont('Arial', '', 12);
            $this->SetXY(15, $posY);
            $this->Cell(15, 6, $nro, 1, 0, 'L', 1);
            $this->Cell(85, 6, $this->latin1($arr['nombrecompleto']), 1, 0, 'L', 1);
            $this->Cell(25, 6, $this->latin1($arr['fecha']), 1, 0, 'C', 1);
            $this->Cell(25, 6, $this->latin1((string) $arr['edad']), 1, 0, 'C', 1);

            $posY += 6;
            $e++;
            $nro++;
        }

        return $this->Output('S');
    }

    /**
     * Replica mostrar_cumple(): deriva mes/dia/anio de nacimiento del CI cubano,
     * calcula la edad (ajustando según la fecha de operaciones) y filtra por mes.
     * Ordena por cidentidad DESC.
     */
    private function obtenerDatos(): array
    {
        $mes = (int) $this->mes;
        $ano = (int) $this->ano;
        $fOperaciones = session('fecha_operaciones');
        $fOpDia = $fOperaciones ? (int) substr($fOperaciones, 8, 2) : (int) date('d');
        $anioActualCorto = (int) substr((string) $ano, 2, 2);

        $rows = Bolsa::query()
            ->where('id_entidad', $this->entidadId)
            ->where('activo', true)
            ->whereNull('deleted_at')
            ->get();

        $arr = [];
        foreach ($rows as $b) {
            $ci = (string) $b->ci;
            if (strlen($ci) < 6) {
                continue;
            }
            $mesci = (int) substr($ci, 2, 2);
            $diaci = (int) substr($ci, 4, 2);
            $yy = (int) substr($ci, 0, 2);

            $anoNac = ($yy < $anioActualCorto) ? 2000 + $yy : 1900 + $yy;
            $fecha = $anoNac.'/'.str_pad((string) $mesci, 2, '0', STR_PAD_LEFT).'/'.str_pad((string) $diaci, 2, '0', STR_PAD_LEFT);

            $edad = $ano - $anoNac;
            if ($mesci >= $mes) {
                if ($diaci >= $fOpDia) {
                    $edad = $edad - 1;
                }
            }

            if ($mesci == $mes) {
                $arr[] = [
                    'ci' => $ci,
                    'nombrecompleto' => $b->nombre.' '.$b->apellidos,
                    'fecha' => $fecha,
                    'edad' => $edad,
                ];
            }
        }

        usort($arr, fn ($a, $b) => strcmp($b['ci'], $a['ci']));

        return $arr;
    }
}
