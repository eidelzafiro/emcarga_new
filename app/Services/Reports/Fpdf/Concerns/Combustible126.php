<?php

namespace App\Services\Reports\Fpdf\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Reporte 126 — RESUMEN MENSUAL DE LAS TARJETAS COMBUSTIBLE.
 * Replica el legacy `Reportes2::pdf_cod_tarjetas_resumen($mes,$unidad)`.
 *
 * Estructura: detalle por tarjeta, subtotal por moneda, subtotal por tipo de
 * combustible y TOTAL GENERAL, con los mismos saltos de página del legacy.
 */
trait Combustible126
{
    /**
     * Claves de los acumuladores: saldo inicial, cargas, descargas,
     * transferencias y saldo actual (moneda y litros).
     */
    private array $comb126Claves = ['sim', 'sil', 'scm', 'scl', 'sdm', 'sdl', 'stm', 'stl', 'sam', 'sal'];

    public function pdfResumenTarjetas(?string $mes = null): \Illuminate\Http\Response
    {
        $titulo = 'RESUMEN MENSUAL DE LAS TARJETAS COMBUSTIBLE';
        [$anio, $mesNum] = $this->anioMes($mes);

        $campos1 = [
            ['titulo' => 'NRO', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'CODIGO', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'SALDO INICIAL', 'ancho' => 40, 'direccion' => 'C'],
            ['titulo' => 'CARGAS', 'ancho' => 40, 'direccion' => 'C'],
            ['titulo' => 'DESCARGAS', 'ancho' => 40, 'direccion' => 'C'],
            ['titulo' => 'TRANSFERENCIAS', 'ancho' => 40, 'direccion' => 'C'],
            ['titulo' => 'SALDO FINAL', 'ancho' => 40, 'direccion' => 'C'],
            ['titulo' => 'SALDO', 'ancho' => 15, 'direccion' => 'C', 'letra' => '8'],
        ];
        $campos = [
            ['titulo' => '', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '12'],
            ['titulo' => '', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'MON', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'LTS', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'MON', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'LTS', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'MON', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'LTS', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'MON', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'LTS', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'MON', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'LTS', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'CONTROL', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'],
        ];

        $data = $this->resumenTarjetasData($anio, $mesNum);

        if ($data->isEmpty()) {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');

            return $this->salida($titulo.'.pdf');
        }

        $posX = 15.0;
        $posY = 42.0;
        $i = 1;
        $nro = 1;
        $max = 25;

        $tipo = array_fill_keys($this->comb126Claves, 0);
        $mon = array_fill_keys($this->comb126Claves, 0);
        $tot = array_fill_keys($this->comb126Claves, 0);

        $first = $data[0];
        $tipocomb = $first->tipocombustibles;
        $moneda = $first->monedas;
        $existfincimexmonmn = (float) $first->existfincmn;
        $existfincimexltsmn = (float) $first->preciomn > 0
            ? round((float) $first->existfincmn / (float) $first->preciomn, 2)
            : 0.0;

        $page = 0;

        $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
        $this->titulos(6, 15, 30, $campos, $campos1);

        foreach ($data as $arr) {
            $fill = $this->fillValidacionTarjeta($arr);

            if ($tipocomb == $arr->tipocombustibles) {
                if ($moneda == $arr->monedas) {
                    $this->filaDetalleResumen($arr, $nro, $posX, $posY, $fill);
                    $posY += 6;
                    $i++;
                    $nro++;
                    $this->acumularTipo($tipo, $arr);
                    $this->acumularMoneda($mon, $arr);
                } else {
                    if ($existfincimexmonmn > 0 && $moneda == 'MN') {
                        $this->filaExistenciaFincimex($posX, $posY, $this->fillColorBase(), $existfincimexmonmn, $existfincimexltsmn);
                        $i++;
                        $posY += 10;
                        $mon['sam'] = round($mon['sam'] + $existfincimexmonmn, 2);
                        $mon['sal'] = round($mon['sal'] + $existfincimexltsmn, 2);
                        $tipo['sam'] += $existfincimexmonmn;
                        $tipo['sal'] += $existfincimexltsmn;
                        $tot['sam'] += $existfincimexmonmn;
                        $tot['sal'] += $existfincimexltsmn;
                    }

                    $this->filaSubtotalResumen($posX, $posY, $this->fillColorBase(), (string) $moneda, $this->valoresAcc($mon), 10, 10);
                    $i++;
                    $posY += 10;
                    $mon = array_fill_keys($this->comb126Claves, 0);

                    $moneda = $arr->monedas;
                    $existfincimexmonmn = (float) $arr->existfincmn;
                    $existfincimexltsmn = (float) $arr->preciomn > 0
                        ? round((float) $arr->existfincmn / (float) $arr->preciomn, 2)
                        : 0.0;

                    $fill = $this->fillValidacionTarjeta($arr);
                    $this->filaDetalleResumen($arr, $nro, $posX, $posY, $fill);
                    $posY += 6;
                    $i++;
                    $nro++;
                    $this->acumularTipo($tipo, $arr);
                    $this->acumularMoneda($mon, $arr);
                }
            } else {
                if ($existfincimexmonmn > 0 && $moneda == 'MN') {
                    $this->filaExistenciaFincimex($posX, $posY, $this->fillColorBase(), $existfincimexmonmn, $existfincimexltsmn);
                    $i++;
                    $posY += 10;
                    $mon['sam'] = round($mon['sam'] + $existfincimexmonmn, 2);
                    $mon['sal'] = round($mon['sal'] + $existfincimexltsmn, 2);
                    $tipo['sam'] += $existfincimexmonmn;
                    $tipo['sal'] += $existfincimexltsmn;
                    $tot['sam'] += $existfincimexmonmn;
                    $tot['sal'] += $existfincimexltsmn;
                }

                if ($tipo['sal'] != $mon['sal']) {
                    $this->filaSubtotalResumen($posX, $posY, $this->fillColorBase(), (string) $moneda, $this->valoresAcc($mon), 10, 10);
                    $posY += 10;
                    $i++;
                }

                $existfincimexmonmn = (float) $arr->existfincmn;
                if ((float) $arr->preciomn > 0) {
                    $existfincimexltsmn = round((float) $arr->existfincmn / (float) $arr->preciomn, 2);
                }
                $mon = array_fill_keys($this->comb126Claves, 0);

                $this->filaSubtotalResumen($posX, $posY, $this->fillColorBase(), (string) $tipocomb, $this->valoresAcc($tipo), 9, 11);
                $i++;
                $posY += 10;
                $tipo = array_fill_keys($this->comb126Claves, 0);

                if ($arr->idtipocombustibles != 14 && $page == 0) {
                    $page = 1;
                    $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                    $this->titulos(6, 15, 35, $campos, $campos1);
                    $posY = 47;
                    $posX = 15.0;
                    $i = 1;
                }

                $tipocomb = $arr->tipocombustibles;
                $moneda = $arr->monedas;

                $fill = $this->fillValidacionTarjeta($arr);
                $this->filaDetalleResumen($arr, $nro, $posX, $posY, $fill);
                $posY += 6;
                $i++;
                $nro++;
                $this->acumularTipo($tipo, $arr);
                $this->acumularMoneda($mon, $arr);
            }

            if ($i == $max) {
                $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                $this->titulos(6, 15, 35, $campos, $campos1);
                $posY = 47;
                $posX = 15.0;
                $i = 1;
            }

            $this->acumularTotal($tot, $arr);
        }

        $fill = $this->fillColorBase();

        if ($tipo['sal'] != $mon['sal']) {
            $this->filaSubtotalResumen($posX, $posY, $fill, (string) $tipocomb, $this->valoresAcc($mon), 9, 11);
            $posY += 10;
        }

        if ($tipo['sal'] != $tot['sal']) {
            $this->filaSubtotalResumen($posX, $posY, $fill, (string) $tipocomb, $this->valoresAcc($tipo), 9, 11);
            $posY += 10;
        }

        $this->filaSubtotalResumen($posX, $posY, $fill, 'TOTAL GENERAL', $this->valoresAcc($tot), 10, 10);

        return $this->salida($titulo.'.pdf');
    }

    /**
     * Filas del resumen (una por tarjeta) con cargas/descargas del mes y
     * validación. Transferencias tomadas de las columnas de `tarjetas`.
     */
    private function resumenTarjetasData(int $anio, int $mes)
    {
        $cargas = DB::table('detalles_carga_combustible')
            ->selectRaw('id_tarjeta, SUM(COALESCE(saldo_mon,0)) as mon, SUM(COALESCE(saldo_lts,0)) as lts')
            ->whereYear('fcarga', $anio)
            ->whereMonth('fcarga', $mes)
            ->groupBy('id_tarjeta');

        $descargas = DB::table('combustible_descargas')
            ->selectRaw('id_tarjeta, SUM(COALESCE(saldo_mon,0)) as mon, SUM(COALESCE(saldo_lts,0)) as lts')
            ->whereYear('fdescarga', $anio)
            ->whereMonth('fdescarga', $mes)
            ->groupBy('id_tarjeta');

        return DB::table('tarjetas as t')
            ->leftJoin('monedas as mo', 'mo.id', '=', 't.idmonedas')
            ->leftJoin('tipos_combustibles as tc', 'tc.id', '=', 't.idtipocombustibles')
            ->leftJoinSub($cargas, 'cg', 'cg.id_tarjeta', '=', 't.id')
            ->leftJoinSub($descargas, 'dg', 'dg.id_tarjeta', '=', 't.id')
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('t.id_entidad', $this->entidadIds))
            ->where('t.cancelado', 0)
            ->orderBy('t.idtipocombustibles')
            ->orderBy('t.idmonedas')
            ->orderBy('t.numero')
            ->selectRaw('t.id as idtarjeta, t.numero as codtm, t.idtipocombustibles, t.idmonedas,
                tc.nombre as tipocombustibles, tc.preciomn, COALESCE(tc.existfincmn,0) as existfincmn,
                mo.nombre as monedas,
                COALESCE(t.saldoinicialmon,0) as saldoinicialmon, COALESCE(t.saldoiniciallts,0) as saldoiniciallts,
                COALESCE(t.saldo_actual,0) as saldoactualmon, COALESCE(t.saldoactuallts,0) as saldoactuallts,
                COALESCE(cg.mon,0) as saldocargadomon, COALESCE(cg.lts,0) as saldocargadolts,
                COALESCE(dg.mon,0) as saldodescargadomon, COALESCE(dg.lts,0) as saldodescargadolts,
                COALESCE(t.saldotransferenciamon,0) as saldotransferenciamon,
                COALESCE(t.saldotransferencialts,0) as saldotransferencialts')
            ->get()
            ->map(function ($r) {
                $r->validacionmon = round((float) $r->saldoinicialmon + (float) $r->saldocargadomon
                    - (float) $r->saldodescargadomon + (float) $r->saldotransferenciamon, 3);
                $r->validacionlts = round((float) $r->saldoiniciallts + (float) $r->saldocargadolts
                    - (float) $r->saldodescargadolts + (float) $r->saldotransferencialts, 3);

                return $r;
            });
    }

    /** Color de relleno: blanco si cuadra, si no el FillColor de sesión. */
    private function fillValidacionTarjeta(object $arr): int
    {
        $ok = $arr->validacionmon == $arr->saldoactualmon && $arr->validacionlts == $arr->saldoactuallts;

        return $ok ? 255 : $this->fillColorBase();
    }

    /** FillColor de sesión usado por los subtotales y el total general. */
    private function fillColorBase(): int
    {
        return (int) (session('FillColor') ?? 200);
    }

    private function filaDetalleResumen(object $arr, int $nro, float $posX, float $posY, int $fill): void
    {
        $this->SetFillColor($fill);
        $this->SetFont('Arial', '', 12);
        $this->SetXY($posX, $posY);
        $this->Cell(10, 6, (string) $nro, 1, 0, 'C', 1);
        $this->SetFont('Arial', 'U', 12);
        $this->Cell(25, 6, (string) $arr->codtm, 1, 0, 'C', 1);
        $this->filaValoresResumen([
            $arr->saldoinicialmon, $arr->saldoiniciallts,
            $arr->saldocargadomon, $arr->saldocargadolts,
            $arr->saldodescargadomon, $arr->saldodescargadolts,
            $arr->saldotransferenciamon, $arr->saldotransferencialts,
            $arr->saldoactualmon, $arr->saldoactuallts,
        ], 6, '', 12);
        $this->SetFont('Arial', '', 12);
        $this->Cell(15, 6, $this->cambiarVariable((float) $arr->validacionlts - (float) $arr->saldoactuallts, 2), 1, 0, 'R', 1);
    }

    private function filaExistenciaFincimex(float $posX, float $posY, int $fill, float $mon, float $lts): void
    {
        $this->SetFillColor($fill);
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY($posX, $posY);
        $this->Cell(195, 10, $this->txt('EXISTENCIA FINCIMEX MN'), 1, 0, 'L', 1);
        $this->Cell(20, 10, $this->cambiarVariable($mon, 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->cambiarVariable($lts, 2), 1, 0, 'R', 1);
        $this->Cell(15, 10, '', 1, 0, 'R', 1);
    }

    private function filaSubtotalResumen(float $posX, float $posY, int $fill, string $label, array $vals, int $labelSize, int $valueSize): void
    {
        $this->SetFillColor($fill);
        $this->SetFont('Arial', 'B', $labelSize);
        $this->SetXY($posX, $posY);
        $this->Cell(35, 10, $this->txt($label), 1, 0, 'L', 1);
        $this->filaValoresResumen($vals, 10, 'B', $valueSize);
        $this->Cell(15, 10, '', 1, 0, 'R', 1);
    }

    private function filaValoresResumen(array $vals, float $h, string $style, int $size): void
    {
        $this->SetFont('Arial', $style, $size);
        foreach ($vals as $v) {
            $this->Cell(20, $h, $this->cambiarVariable($v, 2), 1, 0, 'R', 1);
        }
    }

    private function acumularTipo(array &$acc, object $a): void
    {
        $acc['sim'] += (float) $a->saldoinicialmon;
        $acc['sil'] += (float) $a->saldoiniciallts;
        $acc['scm'] += (float) $a->saldocargadomon;
        $acc['scl'] += (float) $a->saldocargadolts;
        $acc['sdm'] += (float) $a->saldodescargadomon;
        $acc['sdl'] += (float) $a->saldodescargadolts;
        $acc['stm'] += (float) $a->saldotransferenciamon;
        $acc['stl'] += (float) $a->saldotransferencialts;
        $acc['sam'] += (float) $a->saldoactualmon;
        $acc['sal'] += (float) $a->saldoactuallts;
    }

    private function acumularMoneda(array &$acc, object $a): void
    {
        $this->acumularTipo($acc, $a);
    }

    private function acumularTotal(array &$acc, object $a): void
    {
        $this->acumularTipo($acc, $a);
    }

    private function valoresAcc(array $acc): array
    {
        return [
            $acc['sim'], $acc['sil'], $acc['scm'], $acc['scl'],
            $acc['sdm'], $acc['sdl'], $acc['stm'], $acc['stl'],
            $acc['sam'], $acc['sal'],
        ];
    }
}
