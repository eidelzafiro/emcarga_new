<?php

namespace App\Services\Reports\Fpdf;

use App\Services\ReportePrenominaService;

/**
 * Modelo 1 Control Diario de las Transportaciones — réplica FPDF del legacy.
 * Reportes.php:1872 npdf_salario_choferes_modelo1_transcar
 *
 * 16 columnas por fila: fechas inicio/terminación, equipo (capacidad), HR, CP,
 * KMS (carga), TNS, tiempos (otros/mov/carga/descarga/total), escala, tarifa CLA,
 * valor CLA, salario x TRT, ingresos, tasa, fondo formado y nota.
 */
class Modelo1ControlDiarioFpdfReport extends ReportesnewFpdfBase
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

        $titulo = 'MODELO 1 CONTROL DIARIO DE LAS TRANSPORTACIONES';

        // Columnas (replica del legacy Reportes.php:1888-1924).
        $campos = [
            ['titulo' => 'FECHA',   'ancho' => 18, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'FECHA',   'ancho' => 18, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'EQUIPO',  'ancho' => 18, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'HR',      'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'CP',      'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'KMS',     'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 8],
            ['titulo' => 'TNS',     'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 8],
            ['titulo' => 'TIEMPO TRABAJADO', 'ancho' => 65, 'direccion' => 'C'],
            ['titulo' => 'CLA',     'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'SALARIO', 'ancho' => 15, 'direccion' => 'C', 'letra' => 9],
            ['titulo' => 'INGRESOS', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 8],
            ['titulo' => 'TASA',    'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 8],
            ['titulo' => 'FONDO',   'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 8],
            ['titulo' => 'NOTA',    'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 8],
        ];

        $campos1 = [
            ['titulo' => 'INICIO', 'ancho' => 18, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'TERM',   'ancho' => 18, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '(TONS)', 'ancho' => 18, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'OTROS', 'ancho' => 10, 'direccion' => 'C', 'letra' => 7],
            ['titulo' => 'T.M',   'ancho' => 10, 'direccion' => 'C', 'letra' => 8],
            ['titulo' => 'T.C',   'ancho' => 10, 'direccion' => 'C', 'letra' => 8],
            ['titulo' => 'T.D',   'ancho' => 10, 'direccion' => 'C', 'letra' => 8],
            ['titulo' => 'TOTAL', 'ancho' => 10, 'direccion' => 'C', 'letra' => 8],
            ['titulo' => 'ESCALA', 'ancho' => 15, 'direccion' => 'C', 'letra' => 8],
            ['titulo' => 'TASA',   'ancho' => 10, 'direccion' => 'C', 'letra' => 8],
            ['titulo' => 'VALOR',  'ancho' => 10, 'direccion' => 'C', 'letra' => 8],
            ['titulo' => 'X TRT',  'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'TOTAL',  'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 8],
            ['titulo' => '',       'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'FORMADO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 8],
            ['titulo' => '',        'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
        ];

        $posY = 50;
        $max = 20;
        $filasPagina = 0;

        $notas = [
            1 => 'DOBLE CHOFER',
            2 => 'FERIADO',
            3 => 'DC+FERIADO',
            4 => 'VACACIONES',
            5 => 'ALM',
        ];

        foreach ($data['por_chofer'] as $chofer) {
            // Hoja independiente por chofer (réplica legacy: inicio() + titulos por chofer).
            $this->inicio($titulo, 50, 5);
            $this->titulos($campos, [], $campos1, 6, 10, 38);
            $this->firmasSalario('SISTEMA PAGO CHOFERES', 'L');
            $this->imprimirNombreChofer($chofer['nombre'] ?? '');
            $posY = 50;
            $filasPagina = 0;

            foreach ($chofer['registros'] as $reg) {
                // Salto de página por número de filas (réplica legacy: if ($i == $max)).
                if ($filasPagina >= $max) {
                    $this->inicio($titulo, 50, 5);
                    $this->titulos($campos, [], $campos1, 6, 10, 38);
                    $this->firmasSalario('SISTEMA PAGO CHOFERES', 'L');
                    $this->imprimirNombreChofer($chofer['nombre'] ?? '');
                    $posY = 50;
                    $filasPagina = 0;
                }

                $ajuste = (int) ($reg['ajuste'] ?? 0);
                $nota = $ajuste > 0 ? ($notas[$ajuste] ?? '') : '';
                $equipo = ($reg['equipo'] ?? '') . '(' . ($reg['capacidad'] ?? '') . ')';

                $this->SetXY(10, $posY);
                if ($ajuste > 0 && $ajuste != 3) {
                    $this->SetFillColor($this->getFillColor());
                } else {
                    $this->SetFillColor(999, 999, 999);
                }

                $fill = 1;
                $this->SetFont('Arial', '', 8);
                $this->Cell(18, 6, '(' . ($reg['fcarga'] ?? '') . ')-' . ($reg['hcarga1'] ?? ''), 1, 0, 'C', $fill);
                $this->Cell(18, 6, '(' . ($reg['fdescarga'] ?? '') . ')-' . ($reg['hdescarga1'] ?? ''), 1, 0, 'C', $fill);
                $this->SetFont('Arial', 'B', 8);
                $this->Cell(18, 6, mb_strtoupper(mb_substr($equipo, 0, 12)), 1, 0, 'C', $fill);
                $this->SetFont('Arial', 'B', 9);
                $this->Cell(15, 6, $reg['nro_hr'] ?? '', 1, 0, 'C', $fill);
                $this->Cell(15, 6, $reg['nro_cp'] ?? '', 1, 0, 'C', $fill);
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(10, 6, $this->fmt($reg['kmcarga'] ?? 0, 0), 1, 0, 'R', $fill);
                $this->SetFont('Arial', 'B', 9);
                $this->Cell(10, 6, $this->fmt($reg['tnreal'] ?? 0), 1, 0, 'R', $fill);
                $this->SetFont('Arial', 'B', 8);
                $this->Cell(10, 6, $this->fmt($reg['tperm'] ?? 0), 1, 0, 'R', $fill);
                $this->Cell(10, 6, $this->fmt($reg['tmov'] ?? 0), 1, 0, 'R', $fill);
                $this->Cell(10, 6, $this->fmt($reg['tcarga'] ?? 0), 1, 0, 'R', $fill);
                $this->Cell(10, 6, $this->fmt($reg['tdescarga'] ?? 0), 1, 0, 'R', $fill);
                $this->Cell(10, 6, $this->fmt($reg['ttotal'] ?? 0), 1, 0, 'R', $fill);
                $this->SetFont('Arial', 'B', 9);
                $this->Cell(15, 6, $this->fmt($reg['saltrt'] ?? 0), 1, 0, 'R', $fill);
                $this->Cell(10, 6, $this->fmt($reg['tarcla'] ?? 0), 1, 0, 'R', $fill);
                $this->SetFont('Arial', 'B', 8);
                $this->Cell(10, 6, $this->fmt($reg['impcla'] ?? 0), 1, 0, 'R', $fill);
                $this->SetFont('Arial', 'B', 9);
                $this->Cell(15, 6, $this->fmt($reg['saltrtcla'] ?? 0), 1, 0, 'R', $fill);
                $this->Cell(20, 6, $this->fmt($reg['ingresos'] ?? 0), 1, 0, 'R', $fill);
                $this->Cell(15, 6, $this->fmt($reg['tasa'] ?? 0, 5), 1, 0, 'R', $fill);
                $this->Cell(20, 6, $this->fmt($reg['salario'] ?? 0), 1, 0, 'R', $fill);
                $this->SetFont('Arial', 'B', 6);
                $this->Cell(20, 6, $nota, 1, 0, 'C', $fill);
                $posY += 6;
                $filasPagina++;
            }

            // Totales del chofer (réplica legacy: altura 10, posY += 10).
            $tot = $chofer['totales'];
            $this->SetXY(10, $posY);
            $this->SetFont('Arial', 'B', 8);
            $this->SetFillColor($this->getFillColor());
            $this->Cell(18, 10, '', 1, 0, 'C', 1);
            $this->Cell(18, 10, '', 1, 0, 'C', 1);
            $this->Cell(18, 10, 'TOTALES', 1, 0, 'C', 1);
            $this->Cell(15, 10, '', 1, 0, 'C', 1);
            $this->Cell(15, 10, $tot['nro_cp'], 1, 0, 'C', 1);
            $this->Cell(10, 10, $this->fmt($tot['kms'], 0), 1, 0, 'R', 1);
            $this->Cell(10, 10, $this->fmt($tot['tons']), 1, 0, 'R', 1);
            $this->Cell(10, 10, $this->fmt($tot['tperm']), 1, 0, 'R', 1);
            $this->Cell(10, 10, $this->fmt($tot['tmov']), 1, 0, 'R', 1);
            $this->Cell(10, 10, $this->fmt($tot['tcarga']), 1, 0, 'R', 1);
            $this->Cell(10, 10, $this->fmt($tot['tdescarga']), 1, 0, 'R', 1);
            $this->Cell(10, 10, $this->fmt($tot['ttotal']), 1, 0, 'R', 1);
            $this->Cell(15, 10, $this->fmt($tot['saltrt']), 1, 0, 'R', 1);
            $this->Cell(10, 10, '', 1, 0, 'R', 1);
            $this->Cell(10, 10, $this->fmt($tot['impcla']), 1, 0, 'R', 1);
            $this->Cell(15, 10, $this->fmt($tot['saltrtcla']), 1, 0, 'R', 1);
            $this->Cell(20, 10, $this->fmt($tot['ingresos']), 1, 0, 'R', 1);
            $this->Cell(15, 10, '', 1, 0, 'R', 1);
            $this->Cell(20, 10, $this->fmt($tot['salario']), 1, 0, 'R', 1);
            $this->Cell(20, 10, '', 1, 0, 'C', 1);
            $posY += 10;

            // Análisis del tiempo de trabajo en días (réplica legacy).
            $posY += 8;
            $this->SetXY(10, $posY);
            $this->SetFont('Arial', 'B', 11);
            $this->SetFillColor($this->getFillColor());
            $this->Cell(248, 5, $this->latin1('ANÁLISIS DEL TIEMPO DE TRABAJO EN DÍAS'), 1, 0, 'C', 1);
            $posY += 5;

            $posX = 10;
            $this->SetFont('Arial', 'B', 10);
            $this->SetFillColor($this->getFillColor());
            for ($d = 1; $d <= 31; $d++) {
                $this->SetXY($posX, $posY);
                $this->Cell(8, 5, (string) $d, 1, 0, 'C', 1);
                $posX += 8;
            }
            $posY += 5;
            $posX = 10;

            // Fila de control (domingos gris 192, resto blanco).
            $empleados = $service->controlDias(
                (int) $chofer['id_bolsa'],
                (int) $this->mes,
                (int) $this->ano,
                $this->entidadId ?: null
            );
            $dias = \Carbon\Carbon::createFromDate((int) $this->ano, (int) $this->mes, 1)->daysInMonth - 1;

            $this->SetFont('Arial', 'B', 12);
            for ($i = 0; $i <= $dias; $i++) {
                if (($empleados[$i] ?? '') === 'D') {
                    $this->SetFillColor(192, 192, 192);
                } else {
                    $this->SetFillColor(999, 999, 999);
                }
                $this->SetXY($posX, $posY);
                $this->Cell(8, 5, $empleados[$i] ?? '', 1, 0, 'C', 1);
                $posX += 8;
            }
            for (; $i < 31; $i++) {
                $this->SetFillColor(999, 999, 999);
                $this->SetXY($posX, $posY);
                $this->Cell(8, 5, '', 1, 0, 'C', 1);
                $posX += 8;
            }
            $posY += 5;
        }

        return $this->Output('S');
    }

    private function getFillColor(): int
    {
        return (session('FillColor') ?? 200);
    }

    private function imprimirNombreChofer(string $nombre): void
    {
        $this->SetXY(10, 30);
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(999, 999, 999);
        $this->Cell(0, 6, 'NOMBRE Y APELLIDOS: ' . $this->latin1(mb_strtoupper($nombre ?? '')), 0, 0, 'L', 1);
    }
}
