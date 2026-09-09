<?php

namespace App\Services\Reports\Fpdf;

use App\Services\Reports\ResumenExplotacionService;

/**
 * Reporte FPDF "Parte Ingresos Mensuales {variable}" (réplica 1:1 del legacy
 * Reportesnew::pdf_ingresos_resumen_otros / pdf_ingresos_resumen_tractivo) y
 * de su variante combinada "Ingresos Mensuales {org/cliente} - Productos"
 * (pdf_ingresos_organismo_producto).
 */
class IngresosResumenFpdfReport extends ReportesnewFpdfBase
{
    private ResumenExplotacionService $service;

    private array $datos;

    private array $cfg;

    private string $dimension;

    public function __construct(?int $entidadId, string $mes, string $ano, string $dimension, array $filtros = [])
    {
        $this->dimension = $dimension;
        $this->service = app(ResumenExplotacionService::class);
        $this->datos = $this->service->datosResumen($dimension, $filtros);
        $this->cfg = $this->datos['cfg'];

        $combinado = (bool) $this->cfg['combinado'];
        parent::__construct($entidadId, $mes, $ano, $filtros, $combinado ? 'P' : 'L', $combinado ? 'Letter' : 'Legal');
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

    /**
     * Layout simple (Legal landscape): [VAR] | CP | TONS(POS/REAL) |
     * KMS(CARGA/VACÍO/TOTAL) | INGRESOS(FLETE/DEMORA/OTROS/IMPORTE).
     */
    private function generarSimple(): string
    {
        $vcampo = $this->anchoVariable();

        $campos1 = [
            ['titulo' => $this->cfg['titulo'], 'ancho' => $vcampo, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'CP', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'TONS', 'ancho' => 50, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'KMS', 'ancho' => 75, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'INGRESOS', 'ancho' => 115, 'direccion' => 'C', 'bordes' => '1'],
        ];

        $campos = [
            ['titulo' => '', 'ancho' => $vcampo, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'POS', 'ancho' => 25, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'REAL', 'ancho' => 25, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'CARGA', 'ancho' => 25, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'VACIOS', 'ancho' => 25, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'TOTAL', 'ancho' => 25, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'FLETE', 'ancho' => 30, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'DEMORA', 'ancho' => 30, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'OTROS', 'ancho' => 25, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'IMPORTE', 'ancho' => 30, 'direccion' => 'C', 'bordes' => '1'],
        ];

        $this->inicio($this->datos['titulo'], 50, 5);
        $this->titulosFilas($campos1, $campos, 6, 10, 30);

        $posY = 42;
        $i = 1;
        $max = 20;
        $linea = 8;

        foreach ($this->datos['filas'] as $arr) {
            if ($i == $max) {
                $this->inicio($this->datos['titulo'], 50, 5);
                $this->titulosFilas($campos1, $campos, 6, 10, 30);
                $posY = 42;
                $i = 1;
            }

            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->SetTextColor(0, 0, 0);
            $this->SetFont('Arial', 'B', 11);
            $this->Cell($vcampo, $linea, $this->latin1(substr((string) $arr['label'], 0, 85)), 1, 0, 'L', 1);
            $this->Cell(10, $linea, $this->fmtVar($arr['cp'], 0), 1, 0, 'R', 1);
            $this->Cell(25, $linea, $this->fmtVar($arr['tn_pos'], 2), 1, 0, 'R', 1);
            $this->Cell(25, $linea, $this->fmtVar($arr['tn_real'], 2), 1, 0, 'R', 1);
            $this->Cell(25, $linea, $this->fmtVar($arr['km_carga'], 2), 1, 0, 'R', 1);
            $this->Cell(25, $linea, $this->fmtVar($arr['km_vacio'], 2), 1, 0, 'R', 1);
            $this->Cell(25, $linea, $this->fmtVar($arr['km_total'], 2), 1, 0, 'R', 1);
            $this->Cell(30, $linea, $this->fmtVar($arr['flete'], 2), 1, 0, 'R', 1);
            $this->Cell(30, $linea, $this->fmtVar($arr['demora'], 2), 1, 0, 'R', 1);
            $this->Cell(25, $linea, $this->fmtVar($arr['otros'], 2), 1, 0, 'R', 1);
            $this->Cell(30, $linea, $this->fmtVar($arr['ingreso'], 2), 1, 0, 'R', 1);

            $posY += $linea;
            $i++;
        }

        $this->filaTotal($vcampo, $posY);

        return $this->Output('S');
    }

    private function filaTotal(int $vcampo, int $posY): void
    {
        $t = $this->datos['totales'];
        $this->SetXY(10, $posY);
        $this->SetFillColor($this->fillGris());
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($vcampo, 10, 'TOTAL', 1, 0, 'L', 1);
        $this->Cell(10, 10, $this->fmtVar($t['cp'], 0), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($t['tn_pos'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($t['tn_real'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($t['km_carga'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($t['km_vacio'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($t['km_total'], 2), 1, 0, 'R', 1);
        $this->Cell(30, 10, $this->fmtVar($t['flete'], 2), 1, 0, 'R', 1);
        $this->Cell(30, 10, $this->fmtVar($t['demora'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($t['otros'], 2), 1, 0, 'R', 1);
        $this->Cell(30, 10, $this->fmtVar($t['ingreso'], 2), 1, 0, 'R', 1);
    }

    /**
     * Layout combinado (Portrait Letter): [ORG/CLI] | CP | TONS |
     * FLETE | DEMORA | OTROS | TOTAL, con fila de producto y subtotal por
     * organismo/cliente.
     */
    private function generarCombinado(): string
    {
        $tituloVar = $this->cfg['titulo'];
        // El título de la columna es solo la primera parte (ORG/CLI) — la fila
        // de producto lleva el nombre del producto; el subtotal lleva el nombre.
        $campos1 = [
            ['titulo' => 'PRODUCTO', 'ancho' => 70, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'CP', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'TONS', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'INGRESOS', 'ancho' => 90, 'direccion' => 'C', 'bordes' => '1'],
        ];

        $campos = [
            ['titulo' => '', 'ancho' => 70, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'FLETE', 'ancho' => 25, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'DEMORA', 'ancho' => 20, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'OTROS', 'ancho' => 20, 'direccion' => 'C', 'bordes' => '1'],
            ['titulo' => 'TOTAL', 'ancho' => 25, 'direccion' => 'C', 'bordes' => '1'],
        ];

        $this->inicio($this->datos['titulo'], 50, 5);
        $this->titulosFilas($campos1, $campos, 6, 10, 30);

        $posY = 42;
        $i = 1;
        $max = 26;
        $linea = 8;

        $filas = $this->datos['filas'];
        $grupoActual = null;
        $sub = $this->subtotalesVacio();

        foreach ($filas as $idx => $arr) {
            if ($i >= $max) {
                $this->inicio($this->datos['titulo'], 50, 5);
                $this->titulosFilas($campos1, $campos, 6, 10, 30);
                $posY = 42;
                $i = 1;
            }

            // Cambio de organismo/cliente: emitir subtotal del grupo anterior.
            if ($grupoActual !== null && $grupoActual !== $arr['label']) {
                $this->filaSubtotal($grupoActual, $sub, $posY);
                $posY += $linea;
                $i++;
                $sub = $this->subtotalesVacio();
            }

            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->SetTextColor(0, 0, 0);
            $this->SetFont('Arial', 'U', 13);
            $this->Cell(70, $linea, $this->latin1(substr((string) $arr['label2'], 0, 25)), 1, 0, 'L', 1);
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(10, $linea, $this->fmtVar($arr['cp'], 0), 1, 0, 'R', 1);
            $this->Cell(20, $linea, $this->fmtVar($arr['tn_real'], 2), 1, 0, 'R', 1);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(25, $linea, $this->fmtVar($arr['flete'], 2), 1, 0, 'R', 1);
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(20, $linea, $this->fmtVar($arr['demora'], 2), 1, 0, 'R', 1);
            $this->Cell(20, $linea, $this->fmtVar($arr['otros'], 2), 1, 0, 'R', 1);
            $this->Cell(25, $linea, $this->fmtVar($arr['ingreso'], 2), 1, 0, 'R', 1);

            $sub = $this->acumular($sub, $arr);
            $grupoActual = $arr['label'];
            $posY += $linea;
            $i++;
        }

        // Subtotal del último grupo
        if ($grupoActual !== null) {
            $this->filaSubtotal($grupoActual, $sub, $posY);
            $posY += $linea;
        }

        // TOTAL GENERAL
        $t = $this->datos['totales'];
        $this->SetXY(10, $posY);
        $this->SetFillColor($this->fillGris());
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(70, 10, 'TOTAL GENERAL', 1, 0, 'L', 1);
        $this->Cell(10, 10, $this->fmtVar($t['cp'], 0), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($t['tn_real'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($t['flete'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($t['demora'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->fmtVar($t['otros'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->fmtVar($t['ingreso'], 2), 1, 0, 'R', 1);

        return $this->Output('S');
    }

    private function filaSubtotal(string $label, array $sub, int $posY): void
    {
        $this->SetXY(10, $posY);
        $this->SetFillColor($this->fillGris());
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(70, 8, $this->latin1(substr($label, 0, 85)), 1, 0, 'L', 1);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(10, 8, $this->fmtVar($sub['cp'], 0), 1, 0, 'R', 1);
        $this->Cell(20, 8, $this->fmtVar($sub['tn_real'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 8, $this->fmtVar($sub['flete'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 8, $this->fmtVar($sub['demora'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 8, $this->fmtVar($sub['otros'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 8, $this->fmtVar($sub['ingreso'], 2), 1, 0, 'R', 1);
    }

    private function subtotalesVacio(): array
    {
        return ['cp' => 0, 'tn_real' => 0, 'flete' => 0, 'demora' => 0, 'otros' => 0, 'ingreso' => 0];
    }

    private function acumular(array $sub, array $arr): array
    {
        $sub['cp'] += $arr['cp'];
        $sub['tn_real'] += $arr['tn_real'];
        $sub['flete'] += $arr['flete'];
        $sub['demora'] += $arr['demora'];
        $sub['otros'] += $arr['otros'];
        $sub['ingreso'] += $arr['ingreso'];

        return $sub;
    }

    /**
     * Ancho de la columna de la variable según la dimensión (réplica del legacy
     * pdf_ingresos_resumen_otros: vcampo por tipo).
     */
    private function anchoVariable(): int
    {
        return match ($this->dimension) {
            'tractivo', 'fecha' => 40,
            'chofer', 'cliente' => 80,
            'tipo_carga' => 70,
            'producto', 'organismo' => 60,
            default => 60,
        };
    }
}
