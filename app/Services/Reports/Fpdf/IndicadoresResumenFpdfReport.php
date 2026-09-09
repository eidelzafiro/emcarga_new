<?php

namespace App\Services\Reports\Fpdf;

use App\Services\Reports\ResumenExplotacionService;

/**
 * Reporte FPDF "Indicadores Mensuales Explotación {variable}" (réplica 1:1 del
 * legacy Reportes2::pdf_indicadores_resumen) y su variante combinada
 * "Indicadores Mensuales {org/cliente} - Productos"
 * (pdf_indicadores_organismo_producto).
 */
class IndicadoresResumenFpdfReport extends ReportesnewFpdfBase
{
    private ResumenExplotacionService $service;

    private array $datos;

    private array $cfg;

    private string $dimension;

    private array $combTractivo;

    private float $indice;

    public function __construct(?int $entidadId, string $mes, string $ano, string $dimension, array $filtros = [])
    {
        $this->dimension = $dimension;
        $this->service = app(ResumenExplotacionService::class);
        $this->datos = $this->service->datosResumen($dimension, $filtros, true);
        $this->cfg = $this->datos['cfg'];
        $this->combTractivo = $this->service->combustiblePorTractivo($filtros);
        $this->indice = $this->service->indiceCombustible($filtros);

        $combinado = (bool) $this->cfg['combinado'];
        parent::__construct($entidadId, $mes, $ano, $filtros, 'L', $combinado ? 'Letter' : 'Legal');
    }

    public function generate(): string
    {
        if (empty($this->datos['filas'])) {
            $this->inicio($this->datos['titulo'], 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');

            return $this->Output('S');
        }

        return $this->cfg['combinado'] ? $this->generarCombinado() : $this->generarSimple();
    }

    private function combustible(array $f): float
    {
        if ($this->dimension === 'tractivo') {
            return $this->combTractivo[$f['label']] ?? 0;
        }

        return round($f['km_total'] * $this->indice, 2);
    }

    private function indicadores(array $f, float $comb): array
    {
        $car = ($f['km_carga'] > 0 && $f['km_total'] > 0) ? round(($f['km_carga'] / $f['km_total']) * 100, 2) : 0;
        $cace = ($f['tn_real'] > 0 && $f['tn_pos'] > 0) ? round(($f['tn_real'] / $f['tn_pos']) * 100, 2) : 0;
        $dist = ($f['traf_real'] > 0 && $f['tn_real'] > 0) ? round($f['traf_real'] / $f['tn_real'], 2) : 0;

        $factor = $this->service->factorCombustible();
        $diesel = 0;
        if ($comb > 0 && $f['traf_real'] > 0 && $factor > 0) {
            $diesel = round(($comb / $factor) / ($f['traf_real'] / 1000000), 2);
        }

        return ['car' => $car, 'cace' => $cace, 'dist' => $dist, 'diesel' => $diesel];
    }

    /**
     * Layout simple (Legal landscape): [VAR] | VIAJES | COMBUSTIBLE |
     * TONELADAS(POS/REAL) | KILOMETROS(CARGA/VACÍO/TOTAL) | TRAFICO(POS/REAL) |
     * CAR | CACE | DIST | DIESEL.
     */
    private function generarSimple(): string
    {
        $vcampo = 85;

        $campos1 = [
            ['titulo' => $this->cfg['titulo'], 'ancho' => $vcampo, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'VIA', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'COMBUSTIBLE', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '9'],
            ['titulo' => 'TONELADAS', 'ancho' => 40, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'KILOMETROS', 'ancho' => 60, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'TRAFICO', 'ancho' => 40, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'CAR', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'CACE', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'DIST', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'DIESEL', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR'],
        ];

        $campos = [
            ['titulo' => '', 'ancho' => $vcampo, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'JES', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'POSIBLE', 'ancho' => 20, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'REAL', 'ancho' => 20, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'CARGA', 'ancho' => 20, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'VACIO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'TOTAL', 'ancho' => 20, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'POSIBLE', 'ancho' => 20, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'REAL', 'ancho' => 20, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => '', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'MEDIA', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '9'],
            ['titulo' => 'TRAFICO', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB'],
        ];

        $this->inicio($this->datos['titulo'], 50, 5);
        $this->titulosFilas($campos1, $campos, 6, 10, 30);

        $posY = 42;
        $i = 1;
        $max = 19;
        $linea = 8;

        $acum = $this->acumVacio();

        foreach ($this->datos['filas'] as $arr) {
            if ($i == $max) {
                $this->inicio($this->datos['titulo'], 50, 5);
                $this->titulosFilas($campos1, $campos, 6, 10, 30);
                $posY = 42;
                $i = 1;
            }

            $comb = $this->combustible($arr);
            $indic = $this->indicadores($arr, $comb);

            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->SetTextColor(0, 0, 0);
            $this->SetFont('Arial', 'BU', 12);
            $this->Cell($vcampo, $linea, $this->latin1(substr((string) $arr['label'], 0, 85)), 1, 0, 'L', 1);
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(10, $linea, $this->fmtVar($arr['viajes'], 0), 1, 0, 'C', 1);
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(25, $linea, $this->fmtVar($comb, 2), 1, 0, 'R', 1);
            $this->Cell(20, $linea, $this->fmtVar($arr['tn_pos'], 2), 1, 0, 'R', 1);
            $this->Cell(20, $linea, $this->fmtVar($arr['tn_real'], 2), 1, 0, 'R', 1);
            $this->Cell(20, $linea, $this->fmtVar($arr['km_carga'], 2), 1, 0, 'R', 1);
            $this->Cell(20, $linea, $this->fmtVar($arr['km_vacio'], 2), 1, 0, 'R', 1);
            $this->Cell(20, $linea, $this->fmtVar($arr['km_total'], 2), 1, 0, 'R', 1);
            $this->SetFont('Arial', 'B', 9);
            $this->Cell(20, $linea, $this->fmtVar($arr['traf_pos'], 2), 1, 0, 'R', 1);
            $this->Cell(20, $linea, $this->fmtVar($arr['traf_real'], 2), 1, 0, 'R', 1);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(15, $linea, $this->fmtVar($indic['car'], 2), 1, 0, 'R', 1);
            $this->Cell(15, $linea, $this->fmtVar($indic['cace'], 2), 1, 0, 'R', 1);
            $this->Cell(15, $linea, $this->fmtVar($indic['dist'], 2), 1, 0, 'R', 1);
            $this->Cell(15, $linea, $this->fmtVar($indic['diesel'], 2), 1, 0, 'R', 1);

            $acum = $this->acumular($acum, $arr, $comb, $indic);
            $posY += $linea;
            $i++;
        }

        $this->filaTotalSimple($vcampo, $acum, $posY);

        return $this->Output('S');
    }

    private function filaTotalSimple(int $vcampo, array $t, int $posY): void
    {
        $this->SetXY(10, $posY);
        $this->SetFillColor($this->fillGris());
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($vcampo, 10, 'TOTAL', 1, 0, 'L', 1);
        $this->Cell(10, 10, $this->fmtVar($t['viajes'], 0), 1, 0, 'C', 1);
        $this->Cell(25, 10, $this->fmtVar($t['comb'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($t['tn_pos'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($t['tn_real'], 2), 1, 0, 'R', 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 10, $this->fmtVar($t['km_carga'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($t['km_vacio'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($t['km_total'], 2), 1, 0, 'R', 1);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(20, 10, $this->fmtVar($t['traf_pos'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($t['traf_real'], 2), 1, 0, 'R', 1);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(15, 10, $this->fmtVar($t['car'], 2), 1, 0, 'R', 1);
        $this->Cell(15, 10, $this->fmtVar($t['cace'], 2), 1, 0, 'R', 1);
        $this->Cell(15, 10, $this->fmtVar($t['dist'], 2), 1, 0, 'R', 1);
        $this->Cell(15, 10, $this->fmtVar($t['diesel'], 2), 1, 0, 'R', 1);
    }

    /**
     * Layout combinado (Letter landscape): PRODUCTO | VIAJES | COMBUS TIBLE |
     * TONELADAS(POS/REAL) | KILOMETROS(CARGA/VACÍO/TOTAL) | TRAFICO(POS/REAL),
     * con subtotales por organismo/cliente.
     */
    private function generarCombinado(): string
    {
        $campos1 = [
            ['titulo' => 'PRODUCTO', 'ancho' => 70, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'VIAJES', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'COMBUS', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'TONELADAS', 'ancho' => 40, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'KILOMETROS', 'ancho' => 60, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'TRAFICO', 'ancho' => 50, 'direccion' => 'C', 'bordes' => '1'],
        ];

        $campos = [
            ['titulo' => '', 'ancho' => 70, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'TIBLE', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'POSIBLE', 'ancho' => 20, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'REAL', 'ancho' => 20, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'CARGA', 'ancho' => 20, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'VACIO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'TOTAL', 'ancho' => 20, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'POSIBLE', 'ancho' => 25, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'REAL', 'ancho' => 25, 'direccion' => 'C', 'bordes' => '1'],
        ];

        $this->inicio($this->datos['titulo'], 50, 5);
        $this->titulosFilas($campos1, $campos, 6, 10, 30);

        $posY = 42;
        $i = 1;
        $max = 20;
        $linea = 8;

        $filas = $this->datos['filas'];
        $grupoActual = null;
        $sub = $this->acumVacio();
        $prefijo = $this->dimension === 'organismo_producto' ? 'ORGANISMO ' : 'CLIENTE ';

        foreach ($filas as $arr) {
            if ($i >= $max) {
                $this->inicio($this->datos['titulo'], 50, 5);
                $this->titulosFilas($campos1, $campos, 6, 10, 30);
                $posY = 42;
                $i = 1;
            }

            if ($grupoActual !== null && $grupoActual !== $arr['label']) {
                $this->filaSubtotal($prefijo.$grupoActual, $sub, $posY);
                $posY += $linea;
                $i++;
                $sub = $this->acumVacio();
            }

            $comb = $this->combustible($arr);

            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->SetTextColor(0, 0, 0);
            $this->SetFont('Arial', 'U', 12);
            $this->Cell(70, $linea, $this->latin1(substr((string) $arr['label2'], 0, 30)), 1, 0, 'L', 1);
            $this->SetFont('Arial', '', 12);
            $this->Cell(20, $linea, $this->fmtVar($arr['viajes'], 0), 1, 0, 'C', 1);
            $this->Cell(25, $linea, $this->fmtVar($comb, 2), 1, 0, 'R', 1);
            $this->Cell(20, $linea, $this->fmtVar($arr['tn_pos'], 2), 1, 0, 'R', 1);
            $this->Cell(20, $linea, $this->fmtVar($arr['tn_real'], 2), 1, 0, 'R', 1);
            $this->Cell(20, $linea, $this->fmtVar($arr['km_carga'], 2), 1, 0, 'R', 1);
            $this->Cell(20, $linea, $this->fmtVar($arr['km_vacio'], 2), 1, 0, 'R', 1);
            $this->Cell(20, $linea, $this->fmtVar($arr['km_total'], 2), 1, 0, 'R', 1);
            $this->Cell(25, $linea, $this->fmtVar($arr['traf_pos'], 2), 1, 0, 'R', 1);
            $this->Cell(25, $linea, $this->fmtVar($arr['traf_real'], 2), 1, 0, 'R', 1);

            $sub = $this->acumular($sub, $arr, $comb);
            $grupoActual = $arr['label'];
            $posY += $linea;
            $i++;
        }

        if ($grupoActual !== null) {
            $this->filaSubtotal($prefijo.$grupoActual, $sub, $posY);
            $posY += $linea;
        }

        // TOTAL GENERAL
        $t = $this->datos['totales'];
        $tComb = round($t['km_total'] * $this->indice, 2);
        $this->SetXY(10, $posY);
        $this->SetFillColor($this->fillGris());
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(70, 10, 'TOTAL GENERAL', 1, 0, 'L', 1);
        $this->Cell(20, 10, $this->fmtVar($t['viajes'], 0), 1, 0, 'C', 1);
        $this->Cell(25, 10, $this->fmtVar($tComb, 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($t['tn_pos'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($t['tn_real'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($t['km_carga'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($t['km_vacio'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($t['km_total'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($t['traf_pos'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($t['traf_real'], 2), 1, 0, 'R', 1);

        return $this->Output('S');
    }

    private function filaSubtotal(string $label, array $sub, int $posY): void
    {
        $this->SetXY(10, $posY);
        $this->SetFillColor($this->fillGris());
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(70, 8, $this->latin1(substr($label, 0, 85)), 1, 0, 'L', 1);
        $this->Cell(20, 8, $this->fmtVar($sub['viajes'], 0), 1, 0, 'C', 1);
        $this->Cell(25, 8, $this->fmtVar($sub['comb'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 8, $this->fmtVar($sub['tn_pos'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 8, $this->fmtVar($sub['tn_real'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 8, $this->fmtVar($sub['km_carga'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 8, $this->fmtVar($sub['km_vacio'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 8, $this->fmtVar($sub['km_total'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 8, $this->fmtVar($sub['traf_pos'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 8, $this->fmtVar($sub['traf_real'], 2), 1, 0, 'R', 1);
    }

    private function acumVacio(): array
    {
        return ['viajes' => 0, 'comb' => 0, 'tn_pos' => 0, 'tn_real' => 0, 'km_carga' => 0, 'km_vacio' => 0, 'km_total' => 0, 'traf_pos' => 0, 'traf_real' => 0, 'car' => 0, 'cace' => 0, 'dist' => 0, 'diesel' => 0];
    }

    private function acumular(array $sub, array $f, float $comb = 0, ?array $indic = null): array
    {
        $sub['viajes'] += $f['viajes'];
        $sub['comb'] += $comb;
        $sub['tn_pos'] += $f['tn_pos'];
        $sub['tn_real'] += $f['tn_real'];
        $sub['km_carga'] += $f['km_carga'];
        $sub['km_vacio'] += $f['km_vacio'];
        $sub['km_total'] += $f['km_total'];
        $sub['traf_pos'] += $f['traf_pos'];
        $sub['traf_real'] += $f['traf_real'];
        if ($indic) {
            $sub['car'] = $indic['car'];
            $sub['cace'] = $indic['cace'];
            $sub['dist'] = $indic['dist'];
            $sub['diesel'] = $indic['diesel'];
        }

        return $sub;
    }
}
