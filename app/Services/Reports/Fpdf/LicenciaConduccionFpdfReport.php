<?php

namespace App\Services\Reports\Fpdf;

use App\Models\Bolsa;

/**
 * LISTADO PERSONAL CON LICENCIA CONDUCCION — réplica FPDF del legacy.
 * Reportes.php:708 (pdf_choferes, query 'LICENCIA') + ModBolsa.php:7 (mostrar_todos).
 *
 * Orientación 'L' Legal. Columnas: NRO(10), NOMBRE(80), CIDENTIDAD(25), LICENCIA(25),
 * CATEGORIA A/A1/B/C/C1/D/D1/E/F/FE (7 c/u), FALTA(20), FVENCE(20), PUNTOS(20), LIMITACION(40).
 * Las categorías se leen de `bolsa.categorias_licencia` (p.ej. "B,C,D,E") y el número
 * de licencia de `bolsa.licencia`.
 */
class LicenciaConduccionFpdfReport extends ReportesnewFpdfBase
{
    private const CATEGORIAS = ['A', 'A1', 'B', 'C', 'C1', 'D', 'D1', 'E', 'F', 'FE'];

    public function __construct(?int $entidadId, string $mes, string $ano)
    {
        parent::__construct($entidadId, $mes, $ano, [], 'L', 'Legal');
    }

    public function generate(): string
    {
        $titulo = 'LISTADO PERSONAL CON LICENCIA CONDUCCION';

        $campos = [
            ['titulo' => 'NRO',              'ancho' => 10, 'bordes' => 'LTR', 'direccion' => 'C'],
            ['titulo' => 'NOMBRE TRABAJADOR','ancho' => 80, 'bordes' => 'LTR', 'direccion' => 'L'],
            ['titulo' => 'CIDENTIDAD',       'ancho' => 25, 'bordes' => 'LTR', 'direccion' => 'C'],
            ['titulo' => 'LICENCIA',         'ancho' => 25, 'bordes' => 'LTR', 'direccion' => 'C'],
            ['titulo' => 'CATEGORIA',        'ancho' => 70, 'direccion' => 'C'],
            ['titulo' => 'FALTA',            'ancho' => 20, 'bordes' => 'LTR', 'direccion' => 'C'],
            ['titulo' => 'FVENCE',           'ancho' => 20, 'bordes' => 'LTR', 'direccion' => 'C'],
            ['titulo' => 'PUNTOS',           'ancho' => 20, 'bordes' => 'LTR', 'direccion' => 'C'],
            ['titulo' => 'LIMITACION',       'ancho' => 40, 'bordes' => 'LTR', 'direccion' => 'C'],
        ];

        $campos1 = [
            ['titulo' => '',  'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 10],
            ['titulo' => '',  'ancho' => 80, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 7],
            ['titulo' => '',  'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 7],
            ['titulo' => '',  'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 7],
            ['titulo' => 'A',  'ancho' => 7,  'direccion' => 'C', 'bordes' => 1,   'letra' => 12],
            ['titulo' => 'A1', 'ancho' => 7,  'direccion' => 'C', 'bordes' => 1,   'letra' => 12],
            ['titulo' => 'B',  'ancho' => 7,  'direccion' => 'C', 'bordes' => 1,   'letra' => 12],
            ['titulo' => 'C',  'ancho' => 7,  'direccion' => 'C', 'bordes' => 1,   'letra' => 12],
            ['titulo' => 'C1', 'ancho' => 7,  'direccion' => 'C', 'bordes' => 1,   'letra' => 12],
            ['titulo' => 'D',  'ancho' => 7,  'direccion' => 'C', 'bordes' => 1,   'letra' => 12],
            ['titulo' => 'D1', 'ancho' => 7,  'direccion' => 'C', 'bordes' => 1,   'letra' => 12],
            ['titulo' => 'E',  'ancho' => 7,  'direccion' => 'C', 'bordes' => 1,   'letra' => 12],
            ['titulo' => 'F',  'ancho' => 7,  'direccion' => 'C', 'bordes' => 1,   'letra' => 12],
            ['titulo' => 'FE', 'ancho' => 7,  'direccion' => 'C', 'bordes' => 1,   'letra' => 12],
            ['titulo' => '',   'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 7],
            ['titulo' => '',   'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',   'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '',   'ancho' => 40, 'direccion' => 'C', 'bordes' => 'LRB'],
        ];

        $data = $this->obtenerDatos();

        if (empty($data)) {
            $this->inicio($this->latin1($titulo), 50, 5);
            $this->SetFont('Arial', 'B', 18);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
            return $this->Output('S');
        }

        $this->inicio($this->latin1($titulo), 50, 5);
        $this->titulos($campos, $campos1, [], 6, 15, 30);

        $this->SetFillColor(999, 999, 999);
        $posY = 42;
        $nro = 1;
        $i = 1;
        $max = 24;
        $linea = 6;

        foreach ($data as $arr) {
            if ($i >= $max) {
                $this->inicio($this->latin1($titulo), 50, 5);
                $this->titulos($campos, $campos1, [], 6, 15, 30);
                $this->SetFillColor(999, 999, 999);
                $posY = 42;
                $i = 1;
            }

            $this->SetFont('Arial', 'B', 10);
            $this->SetXY(15, $posY);
            $this->Cell(10, $linea, $nro, 1, 0, 'C', 1);
            $this->Cell(80, $linea, $this->latin1($arr['nombrecompleto']), 1, 0, 'L', 1);
            $this->Cell(25, $linea, $this->latin1($arr['ci']), 1, 0, 'L', 1);
            $this->Cell(25, $linea, $this->latin1($arr['licencia']), 1, 0, 'C', 1);
            foreach (self::CATEGORIAS as $cat) {
                $this->Cell(7, $linea, $arr['categorias'][$cat] ?? '', 1, 0, 'C', 1);
            }
            $this->Cell(20, $linea, $this->latin1($arr['falta']), 1, 0, 'C', 1);
            $this->Cell(20, $linea, $this->latin1($arr['fvence']), 1, 0, 'C', 1);
            $this->Cell(20, $linea, '', 1, 0, 'C', 1);
            $this->Cell(40, $linea, $this->latin1($arr['limitacion']), 1, 0, 'C', 1);

            $posY += $linea;
            $i++;
            $nro++;
        }

        return $this->Output('S');
    }

    /**
     * Trabajadores con licencia (licencia != ''), activos, de la entidad, ordenados
     * por nombre completo. Categorías parseadas desde categorias_licencia.
     */
    private function obtenerDatos(): array
    {
        $rows = Bolsa::query()
            ->where('id_entidad', $this->entidadId)
            ->where('activo', true)
            ->where('licencia', '!=', '')
            ->whereNull('deleted_at')
            ->orderBy('nombre')
            ->orderBy('apellidos')
            ->get();

        $arr = [];
        foreach ($rows as $b) {
            $cats = $this->parsearCategorias((string) $b->categorias_licencia);
            $arr[] = [
                'nombrecompleto' => $b->nombre.' '.$b->apellidos,
                'ci' => (string) $b->ci,
                'licencia' => (string) $b->licencia,
                'categorias' => $cats,
                'falta' => $this->formatoFecha($b->licencia_emision),
                'fvence' => $this->formatoFecha($b->licencia_vencimiento),
                'limitacion' => trim((string) $b->limitaciones) !== '' ? 'C/ESPEJUELOS' : '',
            ];
        }

        return $arr;
    }

    private function parsearCategorias(string $raw): array
    {
        $flags = [];
        foreach (explode(',', $raw) as $token) {
            $token = trim($token);
            if (in_array($token, self::CATEGORIAS, true)) {
                $flags[$token] = 'X';
            }
        }
        return $flags;
    }

    private function formatoFecha($fecha): string
    {
        if (empty($fecha) || $fecha === '0000-00-00') {
            return '';
        }
        $parte = strtok((string) $fecha, ' ');
        [$anio, $mes, $dia] = explode('-', $parte);
        return $dia.'-'.$mes.'-'.$anio;
    }
}
