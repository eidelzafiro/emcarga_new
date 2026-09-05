<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;
use Illuminate\Support\Facades\DB;

/**
 * Prenómina Salario Transportación — réplica FPDF exacta del legacy.
 * Reportesh.php:433 npdf_salario_choferes_resumen
 */
class PrenominaTransportacionFpdfReport extends FpdfReportBase
{
    public function generate(): string
    {
        $service = app(ReportePrenominaService::class);
        $data = $service->prenominaChoferes((int) $this->mes, (int) $this->ano, $this->entidadId);

        if (empty($data['registros'])) {
            $this->inicio('DATOS P/NOMINAS SALARIO TRANSPORTACION', 10, 5);
            $this->SetFont('Arial', 'B', 35);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
            return $this->Output('S');
        }

        $titulo = 'DATOS P/NOMINAS SALARIO TRANSPORTACION';
        $this->inicio($titulo, 10, 5);

        // Coeficiente choferes (si frecuencia == 3)
        $entidad = \App\Models\Entidad::find($this->entidadId);
        if ($entidad && ($entidad->idfrecuencia ?? 0) == 3) {
            $this->SetFont('Arial', 'B', 14);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, 26);
            $this->Cell(0, 6, 'COEFICIENTE CHOFERES: ' . $this->calcularCoeficiente(), 0, 0, 'L', 1);
        }

        // Definición de columnas (replica exacta del legacy líneas 446-481)
        $vcampo = 10;

        $campos2 = [
            ['titulo' => '',           'ancho' => $vcampo, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',           'ancho' => 45,     'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '9'],
            ['titulo' => '',           'ancho' => 15,     'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',           'ancho' => 15,     'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',           'ancho' => 15,     'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',           'ancho' => 15,     'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',           'ancho' => 20,     'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'INICIAL',    'ancho' => 20,     'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '%',          'ancho' => 10,     'direccion' => 'C'],
            ['titulo' => 'IMPORTE',    'ancho' => 20,     'direccion' => 'C'],
            ['titulo' => 'FINAL',      'ancho' => 20,     'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'FERIADO',    'ancho' => 20,     'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',           'ancho' => 20,     'direccion' => 'C', 'bordes' => 'LRB'],
        ];

        $campos = [
            ['titulo' => 'NRO',             'ancho' => $vcampo, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => '',                'ancho' => 45,     'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => '',                'ancho' => 15,     'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'SALARIO ESCALA',  'ancho' => 65,     'direccion' => 'C'],
            ['titulo' => 'SALARIO RESULTADO','ancho' => 70,    'direccion' => 'C'],
            ['titulo' => 'SALARIO',         'ancho' => 20,     'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'SALARIO',         'ancho' => 20,     'direccion' => 'C', 'bordes' => 'LTR'],
        ];

        $campos1 = [
            ['titulo' => 'EXP',              'ancho' => $vcampo, 'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'NOMBRE TRABAJADOR','ancho' => 45,     'direccion' => 'L', 'bordes' => 'LR', 'letra' => '9'],
            ['titulo' => 'TIEMPO',           'ancho' => 15,     'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'BASE',             'ancho' => 15,     'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'PLUS',             'ancho' => 15,     'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'CLA',              'ancho' => 15,     'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'TOTAL',            'ancho' => 20,     'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'SALARIO',          'ancho' => 20,     'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'PENALIZACION',     'ancho' => 30,     'direccion' => 'C'],
            ['titulo' => 'SALARIO',          'ancho' => 20,     'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'GPS',              'ancho' => 20,     'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'TOTAL',            'ancho' => 20,     'direccion' => 'C', 'bordes' => 'LR'],
        ];

        $this->titulos($campos2, $campos, $campos1, 35);
        $this->firmas(170);

        // Contenido (replica exacta legacy:504-532)
        $posY = 53;
        $max = 22;
        $i = 1;
        $nro = 1;

        // Totales
        $tttotal = 0; $timpbase = 0; $timpplus = 0; $timpcla = 0;
        $tsalariojornal = 0; $tsalarioresultado = 0; $tpenalizacion = 0;
        $tsalariofinal = 0; $timpferiado = 0; $timpsalfinal2 = 0;

        foreach ($data['registros'] as $arr) {
            $ttotal = $arr['ttotal'] ?? 0;
            if ($ttotal <= 0) {
                continue;
            }

            // Nueva página si necesario
            if ($i >= $max) {
                $this->inicio($titulo, 10, 5);
                $this->titulos($campos2, $campos, $campos1, 35);
                $this->firmas(170);
                $posY = 53;
                $i = 0;
            }

            // Usar campos pre-calculados del servicio
            $impbase = $arr['impbase'] ?? 0;
            $impbase2 = $arr['impbase2'] ?? 0;
            $impplus = $arr['impplus'] ?? 0;
            $impcla = $arr['impcla'] ?? 0;
            $impiresultado = $arr['impiresultado'] ?? 0;
            $penresultado = $arr['penresultado'] ?? '';
            $penimporte = $arr['penimporte'] ?? 0;
            $impresultado = $arr['impresultado'] ?? 0;
            $impgps = $arr['impgps'] ?? 0;
            $impferiados = $arr['impferiados'] ?? 0;
            $impreporte = $arr['impreporte'] ?? 0;

            // Fila de datos (replica legacy:508-532)
            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->SetFont('Arial', '', 11);
            $this->Cell($vcampo, 6, $arr['nronomina'] ?? '', 1, 0, 'C', 1);
            $this->SetFont('Arial', 'B', 8);
            $this->Cell(45, 6, mb_strtoupper(mb_strtolower($arr['nombrecompleto'] ?? '')), 1, 0, 'L', 1);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(15, 6, $this->fmt($ttotal), 1, 0, 'R', 1);
            $this->SetFont('Arial', '', 12);
            $this->Cell(15, 6, $this->fmt($impbase), 1, 0, 'R', 1);
            $this->Cell(15, 6, $this->fmt($impplus), 1, 0, 'R', 1);
            $this->Cell(15, 6, $this->fmt($impcla), 1, 0, 'R', 1);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(20, 6, $this->fmt($impbase2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmt($impiresultado), 1, 0, 'R', 1);
            $this->SetFont('Arial', '', 12);
            $this->Cell(10, 6, $this->fmt($penresultado, 0), 1, 0, 'C', 1);
            $this->Cell(20, 6, $this->fmt($penimporte), 1, 0, 'R', 1);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(20, 6, $this->fmt($impresultado), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmt($impgps + $impferiados), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->fmt($impreporte), 1, 0, 'R', 1);
            $posY += 6;
            $i++;
            $nro++;

            // Acumular totales
            $tttotal += $ttotal;
            $timpbase += $impbase;
            $timpplus += $impplus;
            $timpcla += $impcla;
            $tsalariojornal += $impbase2;
            $tsalarioresultado += $impiresultado;
            $tpenalizacion += $penimporte;
            $tsalariofinal += $impresultado;
            $timpferiado += ($impgps + $impferiados);
            $timpsalfinal2 += $impreporte;
        }

        // Fila de totales
        $this->SetFont('Arial', 'B', 11);
        $this->SetXY(10, $posY);
        $this->SetFillColor($this->getFillColor());
        $this->Cell($vcampo, 10, $nro - 1, 1, 0, 'C', 1);
        $this->Cell(45, 10, 'TOTALES', 1, 0, 'C', 1);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(15, 10, $this->fmt($tttotal), 1, 0, 'R', 1);
        $this->Cell(15, 10, $this->fmt($timpbase), 1, 0, 'R', 1);
        $this->Cell(15, 10, $this->fmt($timpplus), 1, 0, 'R', 1);
        $this->Cell(15, 10, $this->fmt($timpcla), 1, 0, 'R', 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 10, $this->fmt($tsalariojornal), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmt($tsalarioresultado), 1, 0, 'R', 1);
        $this->Cell(10, 10, '', 1, 0, 'C', 1);
        $this->Cell(20, 10, $this->fmt($tpenalizacion), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmt($tsalariofinal), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmt($timpferiado), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmt($timpsalfinal2), 1, 0, 'R', 1);

        return $this->Output('S');
    }

    private function calcularCoeficiente(): string
    {
        // Calcular coeficiente choferes desde catalogo_items (replica legacy:498-502)
        // Escrito en el header cuando idfrecuencia == 3
        $row = DB::table('catalogo_items')
            ->where('tipo', 'tipos_sistemas_pago')
            ->where('origen_id', 2)
            ->first();

        $resultados = $row ? (json_decode($row->extra, true)['resultados'] ?? 0) : 0;

        return $this->fmt($resultados ?? 0, 6);
    }

    private function getFillColor(): int
    {
        return (session('FillColor') ?? 200);
    }
}
