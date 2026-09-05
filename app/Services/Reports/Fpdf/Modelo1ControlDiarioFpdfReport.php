<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * Modelo 1 Control Diario de las Transportaciones — réplica FPDF exacta del legacy.
 * Reportes.php:1872 npdf_salario_choferes_modelo1_transcar
 */
class Modelo1ControlDiarioFpdfReport extends FpdfReportBase
{
    private ?int $idBolsa;

    public function __construct(int $entidadId, string $mes, string $ano, ?int $idBolsa = null)
    {
        parent::__construct($entidadId, $mes, $ano);
        $this->idBolsa = $idBolsa;
    }

    public function generate(): string
    {
        $service = app(ReportePrenominaService::class);
        $data = $service->modelo1((int) $this->mes, (int) $this->ano, $this->idBolsa);

        if (empty($data['por_chofer'])) {
            $this->inicio('MODELO 1 CONTROL TRANSPORTACIONES', 10, 5);
            $this->SetFont('Arial', 'B', 35);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
            return $this->Output('S');
        }

        // Si es un solo chofer, mostrar su nombre en el header
        $titulo = 'MODELO 1 CONTROL TRANSPORTACIONES';
        if (count($data['por_chofer']) === 1) {
            $chofer = $data['por_chofer'][0];
            $titulo .= ' — ' . ($chofer['nombre'] ?? '');
        }

        $this->inicio($titulo, 10, 5);

        // Definición de columnas (replica del legacy Reportes.php modelo1)
        $vcampo = 10;

        $campos = [
            ['titulo' => 'FECHA',        'ancho' => 18,  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'EQUIPO',       'ancho' => 35,  'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'],
            ['titulo' => 'HR',           'ancho' => 12,  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'CP',           'ancho' => 15,  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'KMS',          'ancho' => 15,  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'TNS',          'ancho' => 15,  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'TIEMPO',       'ancho' => 55,  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'CLA',          'ancho' => 30,  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'SALARIO',      'ancho' => 20,  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'INGRESOS',     'ancho' => 20,  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'TASA',         'ancho' => 20,  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'FONDO',        'ancho' => 20,  'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'NOTA',         'ancho' => 25,  'direccion' => 'C', 'bordes' => 'LTR'],
        ];

        $campos1 = [
            ['titulo' => '',       'ancho' => 18,  'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'TONS',   'ancho' => 35,  'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'],
            ['titulo' => '',       'ancho' => 12,  'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => '',       'ancho' => 15,  'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => '',       'ancho' => 15,  'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => '',       'ancho' => 15,  'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'TOTAL',  'ancho' => 55,  'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'VALOR',  'ancho' => 30,  'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'X TRT',  'ancho' => 20,  'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => 'TOTAL',  'ancho' => 20,  'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => '',       'ancho' => 20,  'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => '',       'ancho' => 20,  'direccion' => 'C', 'bordes' => 'LR'],
            ['titulo' => '',       'ancho' => 25,  'direccion' => 'C', 'bordes' => 'LR'],
        ];

        $this->titulos([], $campos, $campos1, 30);
        $this->firmas(170);

        // Un chofer por página
        $posY = 48;
        $max = 170;

        foreach ($data['por_chofer'] as $chofer) {
            // Header del chofer
            $this->SetXY(10, $posY);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(0, 6, 'NOMBRE Y APELLIDOS: ' . mb_strtoupper($chofer['nombre'] ?? ''), 0, 1);
            $posY += 8;

            // Registros del chofer
            foreach ($chofer['registros'] as $reg) {
                if ($posY >= $max) {
                    $this->inicio($titulo, 10, 5);
                    $this->titulos([], $campos, $campos1, 30);
                    $this->firmas(170);
                    $posY = 48;

                    // Repetir header del chofer
                    $this->SetXY(10, $posY);
                    $this->SetFont('Arial', 'B', 10);
                    $this->Cell(0, 6, 'NOMBRE Y APELLIDOS: ' . mb_strtoupper($chofer['nombre'] ?? ''), 0, 1);
                    $posY += 8;
                }

                $equipo = $reg['equipo'] ?? '';
                $fecha = $reg['fecha'] instanceof \Carbon\Carbon
                    ? $reg['fecha']->format('d/m/Y')
                    : $reg['fecha'];

                $this->SetXY(10, $posY);
                $this->SetFont('Arial', '', 9);
                $this->Cell(18, 6, $fecha, 1, 0, 'C');
                $this->Cell(35, 6, mb_strtoupper(mb_substr($equipo, 0, 20)), 1, 0, 'L');
                $this->Cell(12, 6, $reg['nro_hr'] ?? '', 1, 0, 'C');
                $this->Cell(15, 6, $reg['nro_cp'] ?? '', 1, 0, 'C');
                $this->Cell(15, 6, $this->fmt($reg['km_total'] ?? 0), 1, 0, 'R');
                $this->Cell(15, 6, $this->fmt($reg['tn_real'] ?? 0), 1, 0, 'R');
                $this->Cell(55, 6, $this->fmt($reg['tiempo_total'] ?? 0), 1, 0, 'R');
                $this->Cell(30, 6, $this->fmt($reg['tasa_valor'] ?? 0), 1, 0, 'R');
                $this->Cell(20, 6, $this->fmt($reg['salario'] ?? 0), 1, 0, 'R');
                $this->Cell(20, 6, $this->fmt($reg['ingreso'] ?? 0), 1, 0, 'R');
                $this->Cell(20, 6, $reg['tasa_nombre'] ?? '', 1, 0, 'C');
                $this->Cell(20, 6, '', 1, 0, 'R');
                $this->Cell(25, 6, '', 1, 0, 'C');
                $posY += 6;
            }

            // Totales del chofer
            $this->SetXY(10, $posY);
            $this->SetFont('Arial', 'B', 9);
            $this->SetFillColor($this->getFillColor());
            $this->Cell(18, 8, '', 1, 0, 'C', 1);
            $this->Cell(35, 8, 'TOTALES', 1, 0, 'C', 1);
            $this->Cell(12, 8, '', 1, 0, 'C', 1);
            $this->Cell(15, 8, '', 1, 0, 'C', 1);
            $this->Cell(15, 8, $this->fmt($chofer['totales']['km_total'] ?? 0), 1, 0, 'R', 1);
            $this->Cell(15, 8, $this->fmt($chofer['totales']['toneladas'] ?? 0), 1, 0, 'R', 1);
            $this->Cell(55, 8, $this->fmt($chofer['totales']['tiempo_total'] ?? 0), 1, 0, 'R', 1);
            $this->Cell(30, 8, '', 1, 0, 'R', 1);
            $this->Cell(20, 8, '', 1, 0, 'R', 1);
            $this->Cell(20, 8, $this->fmt($chofer['totales']['ingresos'] ?? 0), 1, 0, 'R', 1);
            $this->Cell(20, 8, '', 1, 0, 'C', 1);
            $this->Cell(20, 8, '', 1, 0, 'R', 1);
            $this->Cell(25, 8, '', 1, 0, 'C', 1);
            $posY += 10;

            // Análisis del tiempo de trabajo en días (calendario 1-31)
            $posY += 5;
            $this->SetXY(10, $posY);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(0, 6, 'ANÁLISIS DEL TIEMPO DE TRABAJO EN DÍAS', 0, 1);
            $posY += 8;

            // Calendario días 1-31
            $this->SetXY(10, $posY);
            $this->SetFont('Arial', '', 8);
            $diaY = $posY;
            for ($d = 1; $d <= 31; $d++) {
                $this->SetXY(10 + (($d - 1) * 9), $diaY);
                $this->Cell(9, 5, (string) $d, 1, 0, 'C');
            }
            $posY += 6;

            // Fila de horas por día
            $this->SetXY(10, $posY);
            for ($d = 1; $d <= 31; $d++) {
                $this->SetXY(10 + (($d - 1) * 9), $posY);
                $this->Cell(9, 5, '', 1, 0, 'C');
            }
            $posY += 10;
        }

        return $this->Output('S');
    }

    private function getFillColor(): int
    {
        return (session('FillColor') ?? 200);
    }
}
