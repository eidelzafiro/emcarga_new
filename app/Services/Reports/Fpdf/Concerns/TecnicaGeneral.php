<?php

namespace App\Services\Reports\Fpdf\Concerns;

use App\Support\Catalogos;
use Illuminate\Support\Facades\DB;

/**
 * Reportes generales de técnica (estado del parque, CDT, disponibilidad,
 * índices, operaciones). Replican el legacy `Reportestec.php`.
 */
trait TecnicaGeneral
{
    // ------------------------------------------------------------------
    // Helpers internos
    // ------------------------------------------------------------------

    protected function tecGrupoArrastresId(): ?int
    {
        return Catalogos::grupoArrastresId();
    }

    protected function tecTractivosQuery()
    {
        return DB::table('tractivos')->whereIn('id_entidad', $this->entidadIds);
    }

    /** Tractivos con marca/grupo/estado y kms de sus componentes. */
    protected function tecTractivosDetalle(bool $soloActivos = true)
    {
        $q = DB::table('tractivos as t')
            ->leftJoin('tipo_vehiculos as tv', 'tv.id', '=', 't.id_tipo_vehiculo')
            ->leftJoin('catalogo_items as ma', 'ma.id', '=', 'tv.id_marca')
            ->leftJoin('catalogo_items as gr', 'gr.id', '=', 't.id_grupo')
            ->leftJoin('estados_componentes as es', 'es.id', '=', 't.id_tipo_estado')
            ->leftJoin('motores as mo', 'mo.id', '=', 't.id_motor')
            ->leftJoin('cajas as ca', 'ca.id', '=', 't.id_caja')
            ->leftJoin('diferenciales as di', 'di.id', '=', 't.id_diferencial')
            ->whereIn('t.id_entidad', $this->entidadIds)
            ->select(
                't.*',
                'ma.nombre as marca',
                'gr.nombre as grupo',
                'gr.origen_id as grupo_origen',
                'es.nombre as estado',
                'tv.id_tipo_mantenimiento',
                'mo.kms_acumulados as kmmotor',
                'ca.kms_acumulados as kmcaja',
                'di.kms_acumulados as kmdiferencial'
            )
            ->orderBy('t.codigo');

        if ($soloActivos) {
            $q->whereNull('t.fecha_baja');
        }

        return $q->get();
    }

    /** Sumas de hojas de ruta por tractivo para un año/mes. */
    protected function tecHojasSums(int $anio, ?int $mes): array
    {
        $q = DB::table('hojas_ruta')
            ->whereIn('id_entidad', $this->entidadIds)
            ->whereRaw('YEAR(COALESCE(fecha_cierre, fecha_emision)) = ?', [$anio]);
        if ($mes) {
            $q->whereRaw('MONTH(COALESCE(fecha_cierre, fecha_emision)) = ?', [$mes]);
        }

        return $q->selectRaw('id_tractivo,
                COALESCE(SUM(kms_totales),0) kms,
                COALESCE(SUM(combustible_habilitado),0) hab,
                COALESCE(SUM(combustible_tecnico),0) tec')
            ->groupBy('id_tractivo')->get()->keyBy('id_tractivo')->all();
    }

    /** Sumas de litros de control de lubricantes por tractivo/tipo operación. */
    protected function tecLitrosSums(int $anio, ?int $mes): array
    {
        $q = DB::table('control_lubricantes')
            ->whereIn('id_entidad', $this->entidadIds)
            ->whereRaw('YEAR(fecha_cambio) = ?', [$anio]);
        if ($mes) {
            $q->whereRaw('MONTH(fecha_cambio) = ?', [$mes]);
        }

        return $q->selectRaw('id_tractivo, tipo_operacion, COALESCE(SUM(litros_motor),0) litros')
            ->groupBy('id_tractivo', 'tipo_operacion')->get()
            ->groupBy('id_tractivo')
            ->map(fn ($g) => $g->keyBy('tipo_operacion'))
            ->all();
    }

    /** Combustible cargado en taller por tractivo. */
    protected function tecCombTallerSums(int $anio, ?int $mes): array
    {
        $q = DB::table('ordenes_taller')
            ->whereIn('id_entidad', $this->entidadIds)
            ->whereRaw('YEAR(COALESCE(fecha_salida, fecha_ingreso)) = ?', [$anio]);
        if ($mes) {
            $q->whereRaw('MONTH(COALESCE(fecha_salida, fecha_ingreso)) = ?', [$mes]);
        }

        return $q->selectRaw('id_tractivo, COALESCE(SUM(comb_taller),0) c')
            ->groupBy('id_tractivo')->pluck('c', 'id_tractivo')->all();
    }

    protected function tecLitros($litros, $id, string $tipo): float
    {
        if (! isset($litros[$id])) {
            return 0.0;
        }
        $fila = $litros[$id]->get($tipo);

        return $fila ? (float) $fila->litros : 0.0;
    }

    /** Reproduce Reportes_lib::ajustar_notas(). */
    protected function tecAjustarNotas($string, int $largo): array
    {
        $string = trim((string) $string);
        if ($string === '') {
            return [''];
        }
        $palabras = explode(' ', $string);
        $linea = '';
        $arr = [];
        foreach ($palabras as $palabra) {
            if (strlen($linea) + strlen($palabra) <= $largo) {
                $linea .= ' '.$palabra;
            } else {
                $arr[] = ltrim($linea);
                $linea = $palabra;
            }
        }
        $arr[] = ltrim($linea);

        return $arr;
    }

    protected function tecTiempoMinutos($t): int
    {
        if ($t === null || $t === '') {
            return 0;
        }
        $partes = explode('.', (string) $t);

        return ((int) ($partes[0] ?? 0)) * 60 + ((int) ($partes[1] ?? 0));
    }

    protected function tecSumarTiempos($t1, $t2): string
    {
        $total = $this->tecTiempoMinutos($t1) + $this->tecTiempoMinutos($t2);

        return sprintf('%d.%02d', floor($total / 60), $total % 60);
    }

    protected function tecRestarTiempos($t1, $t2): string
    {
        $total = $this->tecTiempoMinutos($t1) - $this->tecTiempoMinutos($t2);

        return sprintf('%d.%02d', floor($total / 60), $total % 60);
    }

    protected function tecCambiarHoraMinutos($tiempo)
    {
        if ($tiempo === null || $tiempo === '') {
            return '';
        }
        $partes = explode('.', (string) $tiempo);
        $total = ((int) ($partes[0] ?? 0)) * 60 + ((int) ($partes[1] ?? 0));

        return $total === 0 ? '' : $total;
    }

    protected function tecCambiarMinutosHoras($tiempo)
    {
        if ($tiempo === null || $tiempo === '') {
            return '';
        }
        $total = (float) $tiempo;
        $horas = floor($total / 60);
        $minutos = $total - ($horas * 60);

        return ($horas == 0 && $minutos == 0) ? '' : $horas.'.'.str_pad((string) $minutos, 2, '0', STR_PAD_LEFT);
    }

    /** Bloque de firmas CONF/APROB (legacy pdf_salario_firmas). */
    protected function tecFirmas(bool $landscape): void
    {
        $posY = $landscape ? 185 : 245;
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(10, $posY);
        $this->Cell(0, 6, 'CONF:', 0, 1, 'L');
        $this->SetFont('Arial', 'B', 8);
        $this->SetXY(10, $posY + 6);
        $this->Cell(65, 6, '', 0, 1, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(95, $posY);
        $this->Cell(0, 6, 'APROB :', 0, 1, 'L');
        $this->SetFont('Arial', 'B', 8);
        $this->SetXY(95, $posY + 6);
        $this->Cell(60, 6, '', 0, 1, 'L');
    }

    /** Días del mes sin depender de la extensión calendar. */
    protected function tecDiasMes(int $mes, int $anio): int
    {
        return (int) (new \DateTimeImmutable(sprintf('%04d-%02d-01', $anio, max(1, min(12, $mes)))))->format('t');
    }

    protected function tecNoData(string $titulo, string $fecha = ''): void
    {
        $this->inicio($titulo, $fecha);
        $this->SetFont('Arial', 'B', 25);
        $this->SetFillColor(255, 255, 255);
        $this->SetXY(10, 65);
        $this->Cell(0, 6, $this->txt('NO EXISTEN DATOS PARA MOSTRAR'), 0, 1, 'C');
    }

    // ------------------------------------------------------------------
    // 138 · INFORME DEL ESTADO TÉCNICO DEL PARQUE (pdf_mod_cierre_mes)
    // ------------------------------------------------------------------

    public function pdfInformeEstadoParque(?string $mes = null): \Illuminate\Http\Response
    {
        $anual = ($mes === '00' || $mes === 'TODOS');
        [$anio, $mesNum] = $this->anioMes($mes);
        if ($anual) {
            $mesNum = null;
        }
        $variable = $anual ? 'AÑO' : 'MES';
        $titulo = 'INFORME DEL ESTADO TECNICO DEL PARQUE DE VEHICULOS';
        $arrId = $this->tecGrupoArrastresId();

        $campos = [];
        $campos[] = ['titulo' => 'VEHICULO', 'ancho' => 18, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        if (! $anual) {
            $campos[] = ['titulo' => 'MANTENIMIENTO O', 'ancho' => 37, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
            $campos[] = ['titulo' => 'KMS. RECORRIDOS', 'ancho' => 45, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        }
        $campos[] = ['titulo' => 'KMS', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos[] = ['titulo' => 'CONSUMO DURANTE EL '.$variable.':', 'ancho' => 96, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos[] = ['titulo' => 'INDICES DE CONSUMO DEL '.$variable.':', 'ancho' => $anual ? 88 : 54, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];

        $campos1 = [];
        $campos1[] = ['titulo' => '', 'ancho' => 18, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        if (! $anual) {
            $campos1[] = ['titulo' => 'REVISION', 'ancho' => 37, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'];
            $campos1[] = ['titulo' => 'MES ANTERIOR ', 'ancho' => 45, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        }
        $campos1[] = ['titulo' => 'EN EL', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'];
        $campos1[] = ['titulo' => 'Combustible', 'ancho' => 60, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos1[] = ['titulo' => 'Aceite Motor:', 'ancho' => 36, 'direccion' => 'C', 'letra' => '8'];
        $campos1[] = ['titulo' => 'Combustible', 'ancho' => $anual ? 68 : 34, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos1[] = ['titulo' => 'Aceite Relleno', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];

        $campos3 = [];
        $campos3[] = ['titulo' => '#', 'ancho' => 18, 'direccion' => 'C', 'letra' => '8'];
        if (! $anual) {
            $campos3[] = ['titulo' => ' ', 'ancho' => 37, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
            $campos3[] = ['titulo' => 'Motor', 'ancho' => 15, 'direccion' => 'C', 'letra' => '8'];
            $campos3[] = ['titulo' => 'Caja', 'ancho' => 15, 'direccion' => 'C', 'letra' => '8'];
            $campos3[] = ['titulo' => 'Diferencial', 'ancho' => 15, 'direccion' => 'C', 'letra' => '8'];
        }
        $campos3[] = ['titulo' => $variable, 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        $campos3[] = ['titulo' => 'Total', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos3[] = ['titulo' => 'Hab', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos3[] = ['titulo' => 'Tecn', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos3[] = ['titulo' => 'Taller', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos3[] = ['titulo' => 'Relleno', 'ancho' => 12, 'direccion' => 'C', 'letra' => '8'];
        $campos3[] = ['titulo' => 'Mtto', 'ancho' => 12, 'direccion' => 'C', 'letra' => '8'];
        $campos3[] = ['titulo' => 'Ot. C', 'ancho' => 12, 'direccion' => 'C', 'letra' => '8'];
        $campos3[] = ['titulo' => 'Lts/100 Km', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos3[] = ['titulo' => 'Km/Lts', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        if ($anual) {
            $campos3[] = ['titulo' => 'Indice Plan', 'ancho' => 17, 'direccion' => 'C', 'letra' => '8'];
            $campos3[] = ['titulo' => '%', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        }
        $campos3[] = ['titulo' => 'Lts/1000 Km', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];

        $hojas = $this->tecHojasSums($anio, $mesNum);
        $litros = $this->tecLitrosSums($anio, $mesNum);
        $combTaller = $this->tecCombTallerSums($anio, $mesNum);

        $detalle = $this->tecTractivosDetalle()->reject(function ($v) use ($arrId) {
            return $arrId !== null && (int) $v->id_grupo === (int) $arrId;
        })->groupBy(fn ($v) => $v->marca ?: 'SIN MARCA');

        if ($detalle->isEmpty()) {
            $this->tecNoData($titulo, $mes ?: '');

            return $this->salida($titulo.'.pdf');
        }

        $totales = [];
        $this->inicio($titulo, $mes ?: '');
        $this->tecFirmas(true);
        $this->titulos(6, 8, 30, $campos3, $campos, $campos1);
        $posY = 48;

        foreach ($detalle as $marca => $vehiculos) {
            $kmTotal = $combTotal = $combHab = $combTec = $combTallerTotal = 0;
            $totalRelleno = $totalMtto = $totalO = $indiceProm = $porciento = 0;

            foreach ($vehiculos as $v) {
                $id = $v->id;
                $hr = $hojas[$id] ?? null;
                $kms = $hr ? (float) $hr->kms : 0.0;
                $hab = $hr ? (float) $hr->hab : 0.0;
                $tec = $hr ? (float) $hr->tec : 0.0;
                $taller = (float) ($combTaller[$id] ?? 0);
                $relleno = $this->tecLitros($litros, $id, 'RELLENO');
                $mtto = $this->tecLitros($litros, $id, 'MTTO');
                $otro = $this->tecLitros($litros, $id, 'O. CAUSAS');
                $indice = (float) ($v->indice_consumo ?? 0);

                $totalRelleno += $relleno;
                $totalMtto += $mtto;
                $totalO += $otro;
                $combTotal += $hab + $tec;
                $combHab += $hab;
                $combTec += $tec;
                $combTallerTotal += $taller;
                $kmTotal += $kms;

                if ($posY > 200) {
                    $this->inicio($titulo, $mes ?: '');
                    $this->tecFirmas(true);
                    $this->titulos(6, 8, 30, $campos3, $campos, $campos1);
                    $posY = 48;
                }

                $this->SetXY(8, $posY);
                $this->SetFillColor(255, 255, 255);
                $this->SetFont('Arial', 'U', 9);
                $this->Cell(18, 6, $this->txt((string) $v->codigo), 1, 0, 'C', 1);
                $this->SetFont('Arial', '', 9);
                if (! $anual) {
                    $this->Cell(37, 6, '', 1, 0, 'R', 1);
                    $this->Cell(15, 6, $this->cambiarVariable($v->kmmotor, 0), 1, 0, 'R', 1);
                    $this->Cell(15, 6, $this->cambiarVariable($v->kmcaja, 0), 1, 0, 'R', 1);
                    $this->Cell(15, 6, $this->cambiarVariable($v->kmdiferencial, 0), 1, 0, 'R', 1);
                }
                $this->Cell(16, 6, $this->cambiarVariable($kms, 2), 1, 0, 'R', 1);
                $this->Cell(16, 6, $this->cambiarVariable($hab + $tec, 2), 1, 0, 'R', 1);
                $this->Cell(16, 6, $this->cambiarVariable($hab - $taller, 2), 1, 0, 'R', 1);
                $this->Cell(14, 6, $this->cambiarVariable($tec, 2), 1, 0, 'R', 1);
                $this->Cell(14, 6, $this->cambiarVariable($taller, 2), 1, 0, 'R', 1);
                $this->Cell(12, 6, $this->cambiarVariable($relleno, 2), 1, 0, 'R', 1);
                $this->Cell(12, 6, $this->cambiarVariable($mtto, 2), 1, 0, 'R', 1);
                $this->Cell(12, 6, $this->cambiarVariable($otro, 2), 1, 0, 'R', 1);
                $lts100 = $kms > 0 ? round($hab / $kms * 100, 2) : 0;
                $kmLts = $hab > 0 ? round($kms / $hab, 2) : 0;
                $this->Cell(17, 6, $this->cambiarVariable($lts100, 2), 1, 0, 'R', 1);
                $this->Cell(17, 6, $this->cambiarVariable($kmLts, 2), 1, 0, 'R', 1);
                if ($anual) {
                    $this->Cell(17, 6, $this->cambiarVariable($indice, 2), 1, 0, 'R', 1);
                    $indiceProm += $indice;
                    $pct = $kmLts > 0 ? round($indice / $kmLts * 100 - 100, 2) : 0;
                    $this->Cell(17, 6, $this->cambiarVariable($pct, 2), 1, 0, 'R', 1);
                    $porciento += $pct;
                }
                $this->Cell(20, 6, $this->cambiarVariable($kms > 0 ? round($relleno / $kms * 1000, 2) : 0, 2), 1, 0, 'R', 1);
                $posY += 6;
            }

            $this->SetFont('Arial', 'B', 9);
            $this->SetXY(8, $posY);
            $this->SetFillColor(220, 220, 220);
            $this->Cell($anual ? 18 : 100, 6, 'TOTALES', 1, 0, 'R', 1);
            $this->SetFillColor(255, 255, 255);
            $this->Cell(16, 6, $this->cambiarVariable($kmTotal, 2), 1, 0, 'R', 1);
            $this->Cell(16, 6, $this->cambiarVariable($combTotal, 2), 1, 0, 'R', 1);
            $this->Cell(16, 6, $this->cambiarVariable($combHab - $combTallerTotal, 2), 1, 0, 'R', 1);
            $this->Cell(14, 6, $this->cambiarVariable($combTec, 2), 1, 0, 'R', 1);
            $this->Cell(14, 6, $this->cambiarVariable($combTallerTotal, 2), 1, 0, 'R', 1);
            $this->Cell(12, 6, $this->cambiarVariable($totalRelleno, 2), 1, 0, 'R', 1);
            $this->Cell(12, 6, $this->cambiarVariable($totalMtto, 2), 1, 0, 'R', 1);
            $this->Cell(12, 6, $this->cambiarVariable($totalO, 2), 1, 0, 'R', 1);
            $ind100 = $kmTotal > 0 ? round(($combHab - $combTallerTotal) / $kmTotal * 100, 2) : 0;
            $indice = ($combHab - $combTallerTotal) > 0 ? round($kmTotal / ($combHab - $combTallerTotal), 2) : 0;
            $this->Cell(17, 6, $this->cambiarVariable($ind100, 2), 1, 0, 'R', 1);
            $this->Cell(17, 6, $this->cambiarVariable($indice, 2), 1, 0, 'R', 1);
            if ($anual) {
                $n = max(count($vehiculos), 1);
                $this->Cell(17, 6, $this->cambiarVariable(round($indiceProm / $n, 2), 2), 1, 0, 'R', 1);
                $this->Cell(17, 6, $this->cambiarVariable(round($porciento / $n, 2), 2), 1, 0, 'R', 1);
            }
            $this->Cell(20, 6, $this->cambiarVariable($kmTotal > 0 ? round($totalRelleno / $kmTotal * 1000, 2) : 0, 2), 1, 0, 'R', 1);
            $posY += 6;

            $totales[] = [
                'marca' => $marca, 'kms' => $kmTotal, 'combtotal' => $combTotal,
                'combhab' => $combHab - $combTallerTotal, 'combtec' => $combTec,
                'combtaller' => $combTallerTotal, 'relleno' => $totalRelleno,
                'mtto' => $totalMtto, 'otros' => $totalO, 'indice100' => $ind100,
                'indice' => $indice, 'aceite1000' => $kmTotal > 0 ? round($totalRelleno / $kmTotal * 1000, 2) : 0,
            ];
        }

        if (count($totales) > 1) {
            $this->tecResumenPorMarca($titulo, $mes ?: '', $variable, $anual, $totales);
        }

        return $this->salida($titulo.'.pdf');
    }

    /** Página resumen por marca del informe de estado del parque. */
    protected function tecResumenPorMarca(string $titulo, string $fecha, string $variable, bool $anual, array $totales): void
    {
        $campos = [];
        $campos[] = ['titulo' => 'MARCA', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos[] = ['titulo' => 'KMS', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos[] = ['titulo' => 'CONSUMO DURANTE EL '.$variable.':', 'ancho' => 96, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos[] = ['titulo' => 'INDICES DE CONSUMO DEL '.$variable.':', 'ancho' => $anual ? 88 : 54, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos1 = [];
        $campos1[] = ['titulo' => '', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        $campos1[] = ['titulo' => 'EN EL', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'];
        $campos1[] = ['titulo' => 'Combustible', 'ancho' => 60, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos1[] = ['titulo' => 'Aceite Motor:', 'ancho' => 36, 'direccion' => 'C', 'letra' => '8'];
        $campos1[] = ['titulo' => 'Combustible', 'ancho' => $anual ? 68 : 34, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos1[] = ['titulo' => 'Aceite Relleno', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos3 = [];
        $campos3[] = ['titulo' => '', 'ancho' => 30, 'direccion' => 'C', 'letra' => '8'];
        $campos3[] = ['titulo' => $variable, 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        $campos3[] = ['titulo' => 'Total', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos3[] = ['titulo' => 'Hab', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos3[] = ['titulo' => 'Tecn', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos3[] = ['titulo' => 'Taller', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos3[] = ['titulo' => 'Relleno', 'ancho' => 12, 'direccion' => 'C', 'letra' => '8'];
        $campos3[] = ['titulo' => 'Mtto', 'ancho' => 12, 'direccion' => 'C', 'letra' => '8'];
        $campos3[] = ['titulo' => 'Ot. C', 'ancho' => 12, 'direccion' => 'C', 'letra' => '8'];
        $campos3[] = ['titulo' => 'Lts/100 Km', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos3[] = ['titulo' => 'Km/Lts', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        if ($anual) {
            $campos3[] = ['titulo' => 'Indice Plan', 'ancho' => 17, 'direccion' => 'C', 'letra' => '8'];
            $campos3[] = ['titulo' => '%', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        }
        $campos3[] = ['titulo' => 'Lts/1000 Km', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];

        $this->inicio($titulo, $fecha);
        $this->tecFirmas(true);
        $this->titulos(6, 8, 30, $campos3, $campos, $campos1);
        $posY = 48;
        $sum = [];
        foreach ($totales as $t) {
            $this->SetFont('Arial', '', 9);
            $this->SetXY(8, $posY);
            $this->SetFillColor(255, 255, 255);
            $this->Cell(30, 6, $this->txt($t['marca']), 1, 0, 'C', 1);
            $this->Cell(20, 6, $this->cambiarVariable($t['kms'], 2), 1, 0, 'C', 1);
            $this->Cell(16, 6, $this->cambiarVariable($t['combtotal'], 2), 1, 0, 'C', 1);
            $this->Cell(16, 6, $this->cambiarVariable($t['combhab'], 2), 1, 0, 'C', 1);
            $this->Cell(14, 6, $this->cambiarVariable($t['combtec'], 2), 1, 0, 'C', 1);
            $this->Cell(14, 6, $this->cambiarVariable($t['combtaller'], 2), 1, 0, 'C', 1);
            $this->Cell(12, 6, $this->cambiarVariable($t['relleno'], 2), 1, 0, 'C', 1);
            $this->Cell(12, 6, $this->cambiarVariable($t['mtto'], 2), 1, 0, 'C', 1);
            $this->Cell(12, 6, $this->cambiarVariable($t['otros'], 2), 1, 0, 'C', 1);
            $this->Cell(17, 6, $this->cambiarVariable($t['indice100'], 2), 1, 0, 'C', 1);
            $this->Cell(17, 6, $this->cambiarVariable($t['indice'], 2), 1, 0, 'C', 1);
            if ($anual) {
                $this->Cell(17, 6, '', 1, 0, 'C', 1);
                $this->Cell(17, 6, '', 1, 0, 'C', 1);
            }
            $this->Cell(20, 6, $this->cambiarVariable($t['aceite1000'], 2), 1, 0, 'C', 1);
            foreach ($t as $k => $val) {
                if ($k !== 'marca') {
                    $sum[$k] = ($sum[$k] ?? 0) + $val;
                }
            }
            $posY += 6;
        }
        $n = max(count($totales), 1);
        $this->SetFont('Arial', 'B', 9);
        $this->SetXY(8, $posY);
        $this->SetFillColor(220, 220, 220);
        $this->Cell(30, 6, 'TOTALES', 1, 0, 'R', 1);
        $this->SetFillColor(255, 255, 255);
        $this->Cell(20, 6, $this->cambiarVariable($sum['kms'] ?? 0, 2), 1, 0, 'C', 1);
        $this->Cell(16, 6, $this->cambiarVariable($sum['combtotal'] ?? 0, 2), 1, 0, 'C', 1);
        $this->Cell(16, 6, $this->cambiarVariable($sum['combhab'] ?? 0, 2), 1, 0, 'C', 1);
        $this->Cell(14, 6, $this->cambiarVariable($sum['combtec'] ?? 0, 2), 1, 0, 'C', 1);
        $this->Cell(14, 6, $this->cambiarVariable($sum['combtaller'] ?? 0, 2), 1, 0, 'C', 1);
        $this->Cell(12, 6, $this->cambiarVariable($sum['relleno'] ?? 0, 2), 1, 0, 'C', 1);
        $this->Cell(12, 6, $this->cambiarVariable($sum['mtto'] ?? 0, 2), 1, 0, 'C', 1);
        $this->Cell(12, 6, $this->cambiarVariable($sum['otros'] ?? 0, 2), 1, 0, 'C', 1);
        $this->Cell(17, 6, $this->cambiarVariable(($sum['indice100'] ?? 0) / $n, 2), 1, 0, 'C', 1);
        $this->Cell(17, 6, $this->cambiarVariable(($sum['indice'] ?? 0) / $n, 2), 1, 0, 'C', 1);
        if ($anual) {
            $this->Cell(17, 6, '', 1, 0, 'C', 1);
            $this->Cell(17, 6, '', 1, 0, 'C', 1);
        }
        $this->Cell(20, 6, $this->cambiarVariable(($sum['aceite1000'] ?? 0) / $n, 2), 1, 0, 'C', 1);
    }

    // ------------------------------------------------------------------
    // 139 · INFORME ANUAL POR VEHÍCULO (pdf_informe_anual_tractivo)
    // ------------------------------------------------------------------

    public function pdfInformeAnualTractivo($tractivo): \Illuminate\Http\Response
    {
        $cod = (string) $tractivo;
        $veh = DB::table('tractivos')
            ->whereIn('id_entidad', $this->entidadIds)
            ->where(function ($q) use ($cod) {
                $q->where('codigo', $cod);
                if (is_numeric($cod)) {
                    $q->orWhere('id', (int) $cod);
                }
            })->first();

        $titulo = 'INFORME ANUAL DEL ESTADO TECNICO DEL VEHICULOS '.$cod;
        if (! $veh) {
            $this->tecNoData($titulo);

            return $this->salida($titulo.'.pdf');
        }

        $anio = (int) $this->anioMes(null)[0];
        $arrId = $this->tecGrupoArrastresId();
        $esArrastre = $arrId !== null && (int) $veh->id_grupo === (int) $arrId;
        $motor = DB::table('motores')->where('id', $veh->id_motor)->value('kms_acumulados');
        $caja = DB::table('cajas')->where('id', $veh->id_caja)->value('kms_acumulados');
        $dif = DB::table('diferenciales')->where('id', $veh->id_diferencial)->value('kms_acumulados');

        $campos = [];
        $campos[] = ['titulo' => 'MES', 'ancho' => 18, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos[] = ['titulo' => 'KMS. RECORRIDOS', 'ancho' => 45, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos[] = ['titulo' => 'KMS', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos[] = ['titulo' => 'CONSUMO DURANTE EL MES:', 'ancho' => 96, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos[] = ['titulo' => 'INDICES DE CONSUMO DEL MES:', 'ancho' => 88, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos1 = [];
        $campos1[] = ['titulo' => '', 'ancho' => 18, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        $campos1[] = ['titulo' => 'MES ANTERIOR ', 'ancho' => 45, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        $campos1[] = ['titulo' => 'EN EL', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'];
        $campos1[] = ['titulo' => 'Combustible', 'ancho' => 60, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos1[] = ['titulo' => 'Aceite Motor:', 'ancho' => 36, 'direccion' => 'C', 'letra' => '8'];
        $campos1[] = ['titulo' => 'Combustible', 'ancho' => 68, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos1[] = ['titulo' => 'Aceite Relleno', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos3 = [];
        $campos3[] = ['titulo' => '', 'ancho' => 18, 'direccion' => 'C', 'letra' => '8'];
        $campos3[] = ['titulo' => 'Motor', 'ancho' => 15, 'direccion' => 'C', 'letra' => '8'];
        $campos3[] = ['titulo' => 'Caja', 'ancho' => 15, 'direccion' => 'C', 'letra' => '8'];
        $campos3[] = ['titulo' => 'Diferencial', 'ancho' => 15, 'direccion' => 'C', 'letra' => '8'];
        $campos3[] = ['titulo' => 'MES', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        $campos3[] = ['titulo' => 'Total', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos3[] = ['titulo' => 'Hab', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos3[] = ['titulo' => 'Tecn', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos3[] = ['titulo' => 'Taller', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos3[] = ['titulo' => 'Relleno', 'ancho' => 12, 'direccion' => 'C', 'letra' => '8'];
        $campos3[] = ['titulo' => 'Mtto', 'ancho' => 12, 'direccion' => 'C', 'letra' => '8'];
        $campos3[] = ['titulo' => 'Ot. C', 'ancho' => 12, 'direccion' => 'C', 'letra' => '8'];
        $campos3[] = ['titulo' => 'Lts/100 Km', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos3[] = ['titulo' => 'Km/Lts', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos3[] = ['titulo' => 'Indice Plan', 'ancho' => 17, 'direccion' => 'C', 'letra' => '8'];
        $campos3[] = ['titulo' => '%', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos3[] = ['titulo' => 'Lts/1000 Km', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];

        $this->inicio($titulo);
        $this->titulos(6, 10, 30, $campos3, $campos, $campos1);
        $this->tecFirmas(true);
        $posY = 48;
        $tot = ['kms' => 0, 'hab' => 0, 'tec' => 0, 'taller' => 0, 'relleno' => 0, 'mtto' => 0, 'otro' => 0, 'indice' => 0, 'pct' => 0];

        for ($m = 1; $m <= 12; $m++) {
            $hr = DB::table('hojas_ruta')
                ->where('id_tractivo', $veh->id)
                ->whereRaw('YEAR(COALESCE(fecha_cierre, fecha_emision)) = ?', [$anio])
                ->whereRaw('MONTH(COALESCE(fecha_cierre, fecha_emision)) = ?', [$m])
                ->selectRaw('COALESCE(SUM(kms_totales),0) kms, COALESCE(SUM(combustible_habilitado),0) hab, COALESCE(SUM(combustible_tecnico),0) tec')
                ->first();
            $kms = $esArrastre ? 0.0 : (float) $hr->kms;
            $hab = $esArrastre ? 0.0 : (float) $hr->hab;
            $tec = $esArrastre ? 0.0 : (float) $hr->tec;
            $taller = 0.0;
            if (! $esArrastre) {
                $taller = (float) DB::table('ordenes_taller')->where('id_tractivo', $veh->id)
                    ->whereRaw('YEAR(COALESCE(fecha_salida, fecha_ingreso)) = ?', [$anio])
                    ->whereRaw('MONTH(COALESCE(fecha_salida, fecha_ingreso)) = ?', [$m])
                    ->sum('comb_taller');
            }
            $lit = DB::table('control_lubricantes')->where('id_tractivo', $veh->id)
                ->whereRaw('YEAR(fecha_cambio) = ?', [$anio])->whereRaw('MONTH(fecha_cambio) = ?', [$m])
                ->selectRaw('tipo_operacion, COALESCE(SUM(litros_motor),0) l')->groupBy('tipo_operacion')->get()->keyBy('tipo_operacion');
            $relleno = (float) ($lit['RELLENO']->l ?? 0);
            $mtto = (float) ($lit['MTTO']->l ?? 0);
            $otro = (float) ($lit['O. CAUSAS']->l ?? 0);
            $indice = (float) ($veh->indice_consumo ?? 0);
            $kmLts = $hab > 0 ? round($kms / $hab, 2) : 0;
            $pct = $kmLts > 0 ? round($indice / $kmLts * 100 - 100, 2) : 0;

            $tot['kms'] += $kms;
            $tot['hab'] += $hab;
            $tot['tec'] += $tec;
            $tot['taller'] += $taller;
            $tot['relleno'] += $relleno;
            $tot['mtto'] += $mtto;
            $tot['otro'] += $otro;
            $tot['indice'] += $indice;
            $tot['pct'] += $pct;

            if ($posY > 195) {
                $this->inicio($titulo);
                $this->titulos(6, 10, 30, $campos3, $campos, $campos1);
                $this->tecFirmas(true);
                $posY = 48;
            }

            $this->SetFont('Arial', '', 10);
            $this->SetXY(10, $posY);
            $this->SetFillColor(255, 255, 255);
            $mesNombre = $this->meses[str_pad((string) $m, 2, '0', STR_PAD_LEFT)] ?? '';
            $this->Cell(18, 6, $this->txt(substr($mesNombre, 0, 3)), 1, 0, 'C', 1);
            if (! $esArrastre) {
                $this->Cell(15, 6, $this->cambiarVariable($motor, 0), 1, 0, 'R', 1);
                $this->Cell(15, 6, $this->cambiarVariable($caja, 0), 1, 0, 'R', 1);
                $this->Cell(15, 6, $this->cambiarVariable($dif, 0), 1, 0, 'R', 1);
            } else {
                $this->Cell(15, 6, '', 1, 0, 'R', 1);
                $this->Cell(15, 6, '', 1, 0, 'R', 1);
                $this->Cell(15, 6, '', 1, 0, 'R', 1);
            }
            $this->Cell(16, 6, $this->cambiarVariable($kms, 2), 1, 0, 'R', 1);
            $this->Cell(16, 6, $this->cambiarVariable($hab + $tec, 2), 1, 0, 'R', 1);
            $this->Cell(16, 6, $this->cambiarVariable($hab - $taller, 2), 1, 0, 'R', 1);
            $this->Cell(14, 6, $this->cambiarVariable($tec, 2), 1, 0, 'R', 1);
            $this->Cell(14, 6, $this->cambiarVariable($taller, 2), 1, 0, 'R', 1);
            $this->Cell(12, 6, $this->cambiarVariable($relleno, 2), 1, 0, 'R', 1);
            $this->Cell(12, 6, $this->cambiarVariable($mtto, 2), 1, 0, 'R', 1);
            $this->Cell(12, 6, $this->cambiarVariable($otro, 2), 1, 0, 'R', 1);
            $this->Cell(17, 6, $this->cambiarVariable($kms > 0 ? round($hab / $kms * 100, 2) : 0, 2), 1, 0, 'R', 1);
            $this->Cell(17, 6, $this->cambiarVariable($kmLts, 2), 1, 0, 'R', 1);
            $this->Cell(17, 6, $this->cambiarVariable($indice, 2), 1, 0, 'R', 1);
            $this->Cell(17, 6, $this->cambiarVariable($pct, 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->cambiarVariable($kms > 0 ? round($relleno / $kms * 1000, 2) : 0, 2), 1, 0, 'R', 1);
            $posY += 6;
        }

        $this->SetFont('Arial', 'B', 9);
        $this->SetXY(10, $posY);
        $this->SetFillColor(220, 220, 220);
        $this->Cell(63, 6, 'TOTALES', 1, 0, 'R', 1);
        $this->SetFillColor(255, 255, 255);
        $this->Cell(16, 6, $this->cambiarVariable($tot['kms'], 2), 1, 0, 'R', 1);
        $this->Cell(16, 6, $this->cambiarVariable($tot['hab'] + $tot['tec'], 2), 1, 0, 'R', 1);
        $this->Cell(16, 6, $this->cambiarVariable($tot['hab'] - $tot['taller'], 2), 1, 0, 'R', 1);
        $this->Cell(14, 6, $this->cambiarVariable($tot['tec'], 2), 1, 0, 'R', 1);
        $this->Cell(14, 6, $this->cambiarVariable($tot['taller'], 2), 1, 0, 'R', 1);
        $this->Cell(12, 6, $this->cambiarVariable($tot['relleno'], 2), 1, 0, 'R', 1);
        $this->Cell(12, 6, $this->cambiarVariable($tot['mtto'], 2), 1, 0, 'R', 1);
        $this->Cell(12, 6, $this->cambiarVariable($tot['otro'], 2), 1, 0, 'R', 1);
        $this->Cell(17, 6, $this->cambiarVariable($tot['kms'] > 0 ? round(($tot['hab'] - $tot['taller']) / $tot['kms'] * 100, 2) : 0, 2), 1, 0, 'R', 1);
        $this->Cell(17, 6, $this->cambiarVariable(($tot['hab'] - $tot['taller']) > 0 ? round($tot['kms'] / ($tot['hab'] - $tot['taller']), 2) : 0, 2), 1, 0, 'R', 1);
        $this->Cell(17, 6, $this->cambiarVariable(round($tot['indice'] / 12, 2), 2), 1, 0, 'R', 1);
        $this->Cell(17, 6, $this->cambiarVariable(round($tot['pct'] / 12, 2), 2), 1, 0, 'R', 1);
        $this->Cell(20, 6, $this->cambiarVariable($tot['kms'] > 0 ? round($tot['relleno'] / $tot['kms'] * 1000, 2) : 0, 2), 1, 0, 'R', 1);

        return $this->salida($titulo.'.pdf');
    }

    // ------------------------------------------------------------------
    // 140 · SITUACIÓN TÉCNICA DEL PARQUE (pdf_parque_vehiculos)
    // ------------------------------------------------------------------

    public function pdfSituacionParque(?string $taller = null): \Illuminate\Http\Response
    {
        $titulo = 'SITUACION TECNICA DEL PARQUE DE VEHICULOS';
        $arrId = $this->tecGrupoArrastresId();

        $campos = [];
        $campos[0] = ['titulo' => 'EQUIPO', 'ancho' => 91, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos[1] = ['titulo' => 'ARRASTRE', 'ancho' => 98, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos[2] = ['titulo' => '', 'ancho' => 72, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos1 = [];
        $campos1[0] = ['titulo' => 'VEHICULO', 'ancho' => 27, 'direccion' => 'C', 'bordes' => 'LTR'];
        $campos1[1] = ['titulo' => 'MARCA', 'ancho' => 23, 'direccion' => 'C', 'bordes' => 'LTR'];
        $campos1[2] = ['titulo' => 'VENCE ', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'];
        $campos1[3] = ['titulo' => 'SITUACION', 'ancho' => 21, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos1[4] = ['titulo' => 'VEHICULO', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 'LTR'];
        $campos1[5] = ['titulo' => 'MARCA', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'];
        $campos1[6] = ['titulo' => 'VENCE', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'];
        $campos1[7] = ['titulo' => 'TIPO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'];
        $campos1[8] = ['titulo' => 'SITUACION', 'ancho' => 21, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos1[9] = ['titulo' => 'CAUSA DE LA ROTURA', 'ancho' => 72, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '8'];
        $campos3 = [];
        $campos3[0] = ['titulo' => '', 'ancho' => 27, 'direccion' => 'C', 'bordes' => 'LRB'];
        $campos3[1] = ['titulo' => '', 'ancho' => 23, 'direccion' => 'C', 'letra' => '8', 'bordes' => 'LRB'];
        $campos3[2] = ['titulo' => 'CRT', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'];
        $campos3[3] = ['titulo' => '', 'ancho' => 21, 'direccion' => 'C', 'bordes' => 'LRB'];
        $campos3[4] = ['titulo' => '', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 'LRB'];
        $campos3[5] = ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'];
        $campos3[6] = ['titulo' => 'CRT', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'];
        $campos3[7] = ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'];
        $campos3[8] = ['titulo' => '', 'ancho' => 21, 'direccion' => 'C', 'bordes' => 'LRB'];
        $campos3[9] = ['titulo' => '', 'ancho' => 72, 'direccion' => 'C', 'bordes' => 'LRB'];

        $tractivos = $this->tecTractivosDetalle()->reject(function ($v) use ($arrId) {
            return $arrId !== null && (int) $v->id_grupo === (int) $arrId;
        });
        $asoc = DB::table('arrastre_tractivo as at')
            ->join('tractivos as a', 'a.id', '=', 'at.id_arrastre')
            ->leftJoin('tipo_vehiculos as tv', 'tv.id', '=', 'a.id_tipo_vehiculo')
            ->leftJoin('catalogo_items as ma', 'ma.id', '=', 'tv.id_marca')
            ->leftJoin('estados_componentes as es', 'es.id', '=', 'a.id_tipo_estado')
            ->select('at.id_tractivo', 'a.id as arrastre_id', 'a.codigo', 'a.id_grupo', 'a.id_tipo_vehiculo', 'ma.nombre as marca', 'es.nombre as estado')
            ->get()->keyBy('id_tractivo');
        $doc = DB::table('vehiculos_documentacion')->where('vehiculo_type', 'tractivo')->get()->keyBy('vehiculo_id');

        if ($tractivos->isEmpty()) {
            $this->tecNoData($titulo);

            return $this->salida($titulo.'.pdf');
        }

        $this->inicio($titulo);
        $this->titulos(6, 10, 30, $campos3, $campos, $campos1);
        $this->tecFirmas(true);
        $posY = 48;

        foreach ($tractivos as $v) {
            $arr = $asoc[$v->id] ?? null;
            $d = $doc[$v->id] ?? null;
            $observacion = $this->tecAjustarNotas((string) ($d->observaciones ?? ''), 35);

            for ($k = 0; $k < count($observacion); $k++) {
                if ($posY > 200) {
                    $this->inicio($titulo);
                    $this->titulos(6, 10, 30, $campos3, $campos, $campos1);
                    $this->tecFirmas(true);
                    $posY = 48;
                }
                $borde = $k < count($observacion) - 1 ? ($k === 0 ? 'LRT' : 'LR') : (count($observacion) === 1 ? 'LRTB' : 'LRB');
                $this->SetFont('Arial', '', 10);
                $this->SetXY(10, $posY);
                $this->SetFillColor(255, 255, 255);
                $this->Cell(27, 6, $k === 0 ? $this->txt(substr((string) $v->codigo, 0, 10)) : '', $borde, 0, 'C', 1);
                $this->Cell(23, 6, $k === 0 ? $this->txt(substr((string) $v->marca, 0, 8)) : '', $borde, 0, 'C', 1);
                $this->Cell(20, 6, $k === 0 ? ($d->fvence_ficav ?? '') : '', $borde, 0, 'C', 1);
                $this->Cell(21, 6, $k === 0 ? $this->txt(substr((string) $v->estado, 0, 9)) : '', $borde, 0, 'C', 1);
                $this->Cell(17, 6, $k === 0 ? ($arr->codigo ?? '') : '', $borde, 0, 'C', 1);
                $this->Cell(20, 6, $k === 0 ? $this->txt(substr((string) ($arr->marca ?? ''), 0, 8)) : '', $borde, 0, 'C', 1);
                $arrDoc = $arr ? ($doc[$arr->arrastre_id] ?? null) : null;
                $this->Cell(20, 6, $k === 0 ? ($arrDoc->fvence_ficav ?? '') : '', $borde, 0, 'C', 1);
                $this->Cell(20, 6, '', $borde, 0, 'C', 1);
                $this->Cell(21, 6, $k === 0 ? $this->txt(substr((string) ($arr->estado ?? ''), 0, 9)) : '', $borde, 0, 'C', 1);
                $this->SetFont('Arial', 'B', 9);
                $this->Cell(72, 6, $this->txt($observacion[$k]), $borde, 0, 'J', 1);
                $posY += 6;
            }
        }

        return $this->salida($titulo.'.pdf');
    }

    // ------------------------------------------------------------------
    // 144 · CONTROL CDT (pdf_cdt_general)
    // ------------------------------------------------------------------

    public function pdfControlCdt(?string $mes = null): \Illuminate\Http\Response
    {
        $titulo = 'CONTROL DEL COEFICIENTE DE DISPONIBILIDAD TECNICA (CDT)';
        [$anio, $mesNum] = $this->anioMes($mes);
        $anual = ($mes === '00' || $mes === 'TODOS');

        $campos = [];
        $campos[0] = ['titulo' => 'DIA', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos[1] = ['titulo' => 'PARQUE', 'ancho' => 23, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos[2] = ['titulo' => 'ACTIVOS', 'ancho' => 23, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos[3] = ['titulo' => 'EN TALLER', 'ancho' => 23, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos[4] = ['titulo' => 'ACTIVOS', 'ancho' => 23, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos[5] = ['titulo' => 'FONDO HORAS', 'ancho' => 23, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos[6] = ['titulo' => 'HORAS ROTO', 'ancho' => 23, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos[7] = ['titulo' => 'FONDO HORAS', 'ancho' => 23, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos[8] = ['titulo' => 'CDT', 'ancho' => 23, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos1 = [];
        $campos1[0] = ['titulo' => '', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        $campos1[1] = ['titulo' => 'TRACTIVOS', 'ancho' => 23, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        $campos1[2] = ['titulo' => '', 'ancho' => 23, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        $campos1[3] = ['titulo' => '', 'ancho' => 23, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        $campos1[4] = ['titulo' => '%', 'ancho' => 23, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        $campos1[5] = ['titulo' => 'EXPLOTACION', 'ancho' => 23, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        $campos1[6] = ['titulo' => 'EN TALLER', 'ancho' => 23, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        $campos1[7] = ['titulo' => 'DISPONIBLES', 'ancho' => 23, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        $campos1[8] = ['titulo' => '%', 'ancho' => 23, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];

        $arrId = $this->tecGrupoArrastresId();
        $tractivos = $this->tecTractivosDetalle();
        $tractivosT = $tractivos->reject(fn ($v) => $arrId !== null && (int) $v->id_grupo === (int) $arrId);
        $arrastres = $tractivos->filter(fn ($v) => $arrId !== null && (int) $v->id_grupo === (int) $arrId);

        if ($tractivos->isEmpty()) {
            $this->tecNoData($titulo, $mes ?: '');

            return $this->salida($titulo.'.pdf');
        }

        $grupos = [
            ['nombre' => 'TRACTIVOS', 'vehiculos' => $tractivosT],
            ['nombre' => 'ARRASTRES', 'vehiculos' => $arrastres],
        ];

        foreach ($grupos as $grupo) {
            if ($grupo['vehiculos']->isEmpty()) {
                continue;
            }
            $cant = $grupo['vehiculos']->count();
            $ids = $grupo['vehiculos']->pluck('id')->all();
            $this->inicio($titulo, $mes ?: '');
            $this->titulos(6, 10, 30, $campos1, $campos);

            $posY = 42;
            $sumAct = $sumTaller = $sumFhoras = $sumFhroto = $sumFhdisp = 0;
            $nDias = 0;

            if ($anual) {
                for ($mm = 1; $mm <= 12; $mm++) {
                    $dias = $this->tecDiasMes($mm, $anio);
                    $nDias += $dias;
                    $tallerHoras = $this->tecHorasTallerMes($ids, $anio, $mm);
                    $entaller = $dias > 0 ? round($tallerHoras / 24 / $dias) : 0;
                    $this->SetFont('Arial', '', 10);
                    $this->SetXY(10, $posY);
                    $this->SetFillColor(255, 255, 255);
                    $mesNombre = $this->meses[str_pad((string) $mm, 2, '0', STR_PAD_LEFT)] ?? '';
                    $this->Cell(10, 6, $this->txt(substr($mesNombre, 0, 3)), 1, 0, 'C', 1);
                    $this->Cell(23, 6, $cant, 1, 0, 'C', 1);
                    $this->Cell(23, 6, $cant - $entaller, 1, 0, 'C', 1);
                    $this->Cell(23, 6, $entaller, 1, 0, 'C', 1);
                    $this->Cell(23, 6, $cant > 0 ? round(($cant - $entaller) / $cant * 100, 2) : 0, 1, 0, 'C', 1);
                    $this->Cell(23, 6, $cant * 24 * $dias, 1, 0, 'C', 1);
                    $this->Cell(23, 6, $tallerHoras, 1, 0, 'C', 1);
                    $this->Cell(23, 6, $cant * 24 * $dias - $tallerHoras, 1, 0, 'C', 1);
                    $this->Cell(23, 6, ($cant * 24 * $dias) > 0 ? round(($cant * 24 * $dias - $tallerHoras) / ($cant * 24 * $dias) * 100, 2) : 0, 1, 0, 'C', 1);
                    $sumAct += $cant - $entaller;
                    $sumTaller += $entaller;
                    $sumFhoras += $cant * 24 * $dias;
                    $sumFhroto += $tallerHoras;
                    $sumFhdisp += $cant * 24 * $dias - $tallerHoras;
                    $posY += 6;
                }
            } else {
                $dias = $this->tecDiasMes($mesNum, $anio);
                for ($dia = 1; $dia <= $dias; $dia++) {
                    $nDias++;
                    $tallerHoras = $this->tecHorasTallerDia($ids, $anio, $mesNum, $dia);
                    $entaller = ($tallerHoras / 24 < 1 && $tallerHoras / 24 > 0) ? 1 : round($tallerHoras / 24);
                    if ($posY > 250) {
                        $this->inicio($titulo, $mes ?: '');
                        $this->titulos(6, 10, 30, $campos1, $campos);
                        $posY = 42;
                    }
                    $this->SetFont('Arial', '', 10);
                    $this->SetXY(10, $posY);
                    $this->SetFillColor(255, 255, 255);
                    $this->Cell(10, 6, $dia, 1, 0, 'C', 1);
                    $this->Cell(23, 6, $cant, 1, 0, 'C', 1);
                    $this->Cell(23, 6, $cant - $entaller, 1, 0, 'C', 1);
                    $this->Cell(23, 6, $entaller, 1, 0, 'C', 1);
                    $this->Cell(23, 6, $cant > 0 ? round(($cant - $entaller) / $cant * 100, 2) : 0, 1, 0, 'C', 1);
                    $this->Cell(23, 6, $cant * 24, 1, 0, 'C', 1);
                    $this->Cell(23, 6, $tallerHoras, 1, 0, 'C', 1);
                    $this->Cell(23, 6, $cant * 24 - $tallerHoras, 1, 0, 'C', 1);
                    $this->Cell(23, 6, ($cant * 24) > 0 ? round(($cant * 24 - $tallerHoras) / ($cant * 24) * 100, 2) : 0, 1, 0, 'C', 1);
                    $sumAct += $cant - $entaller;
                    $sumTaller += $entaller;
                    $sumFhoras += $cant * 24;
                    $sumFhroto += $tallerHoras;
                    $sumFhdisp += $cant * 24 - $tallerHoras;
                    $posY += 6;
                }
            }

            $this->SetFont('Arial', 'B', 10);
            $this->SetXY(10, $posY);
            $this->SetFillColor(220, 220, 220);
            $this->Cell(33, 6, 'PROMEDIO', 1, 0, 'C', 1);
            $this->SetFillColor(255, 255, 255);
            $d = max($nDias, 1);
            $this->Cell(23, 6, round($sumAct / $d, 2), 1, 0, 'C', 1);
            $this->Cell(23, 6, round($sumTaller / $d, 2), 1, 0, 'C', 1);
            $this->Cell(23, 6, $cant > 0 ? round(($sumAct / $d) / $cant * 100, 2) : 0, 1, 0, 'C', 1);
            $this->Cell(23, 6, $sumFhoras, 1, 0, 'C', 1);
            $this->Cell(23, 6, $sumFhroto, 1, 0, 'C', 1);
            $this->Cell(23, 6, $sumFhdisp, 1, 0, 'C', 1);
            $this->Cell(23, 6, $sumFhoras > 0 ? round($sumFhdisp / $sumFhoras * 100, 2) : 0, 1, 0, 'C', 1);
        }

        return $this->salida($titulo.'.pdf');
    }

    protected function tecHorasTallerMes(array $ids, int $anio, int $mes): float
    {
        if (empty($ids)) {
            return 0.0;
        }

        return (float) DB::table('ordenes_taller')
            ->whereIn('id_tractivo', $ids)
            ->whereRaw('YEAR(COALESCE(fecha_salida, fecha_ingreso)) = ?', [$anio])
            ->whereRaw('MONTH(COALESCE(fecha_salida, fecha_ingreso)) = ?', [$mes])
            ->sum('ottiempo');
    }

    protected function tecHorasTallerDia(array $ids, int $anio, int $mes, int $dia): float
    {
        if (empty($ids)) {
            return 0.0;
        }
        $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);

        return (float) DB::table('ordenes_taller')
            ->whereIn('id_tractivo', $ids)
            ->where(function ($q) use ($fecha) {
                $q->where('fecha_ingreso', $fecha)
                    ->orWhere('fecha_salida', $fecha)
                    ->orWhere(function ($q2) use ($fecha) {
                        $q2->where('fecha_ingreso', '<', $fecha)->where('fecha_salida', '>', $fecha);
                    });
            })
            ->sum('ottiempo');
    }

    // ------------------------------------------------------------------
    // 155 · DISPONIBILIDAD (KMS) (pdf_proximosamtto)
    // ------------------------------------------------------------------

    public function pdfDisponibilidadKms(): \Illuminate\Http\Response
    {
        $titulo = 'VEHICULOS PROXIMOS A MANTENIMIENTOS O REVISIONES';
        $campos = [];
        $campos[] = ['titulo' => 'VEHICULO', 'ancho' => 35, 'direccion' => 'C', 'bordes' => 'LTR'];
        $campos[] = ['titulo' => 'KILOMETRAJE', 'ancho' => 90, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'MTTO O ', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LTR'];
        $campos[] = ['titulo' => 'KILOMETRAJE', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LTR'];
        $campos1 = [];
        $campos1[] = ['titulo' => '', 'ancho' => 35, 'direccion' => 'C', 'bordes' => 'LRB'];
        $campos1[] = ['titulo' => 'ULTIMO MTTO', 'ancho' => 30, 'direccion' => 'C'];
        $campos1[] = ['titulo' => 'ACTUAL', 'ancho' => 30, 'direccion' => 'C'];
        $campos1[] = ['titulo' => 'PLAN MTTO', 'ancho' => 30, 'direccion' => 'C'];
        $campos1[] = ['titulo' => 'REVISION', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRB'];
        $campos1[] = ['titulo' => 'RESTANTE', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRB'];

        $tractivos = $this->tecTractivosDetalle();
        $ids = $tractivos->pluck('id')->all() ?: [0];
        $ultimos = DB::table('ordenes_taller')
            ->whereIn('id_tractivo', $ids)
            ->whereNotNull('kilometraje')
            ->orderBy('fecha_ingreso')
            ->get(['id_tractivo', 'kilometraje'])->keyBy('id_tractivo');
        $planes = DB::table('tipos_mantenimiento')->pluck('nombre', 'id');

        $rows = [];
        foreach ($tractivos as $v) {
            $actual = (float) $v->kilometraje_actual;
            $plan = (float) $v->kms_plan_mtto;
            $ultimo = (float) ($ultimos[$v->id]->kilometraje ?? 0);
            $restante = $plan - $actual;
            if ($restante > 2000) {
                continue;
            }
            $rows[] = [
                'codigo' => $v->codigo, 'ultimo' => $ultimo, 'actual' => $actual, 'plan' => $plan,
                'revision' => $planes[$v->id_tipo_mantenimiento] ?? '', 'restante' => $restante,
                'vencido' => $actual > $plan,
            ];
        }

        if (empty($rows)) {
            $this->tecNoData($titulo);

            return $this->salida($titulo.'.pdf');
        }

        $this->inicio($titulo);
        $this->titulos(6, 10, 30, $campos1, $campos);
        $posY = 42;

        foreach ($rows as $r) {
            if ($posY > 250) {
                $this->inicio($titulo);
                $this->titulos(6, 10, 30, $campos1, $campos);
                $posY = 42;
            }
            $this->SetFont('Arial', '', 12);
            $this->SetXY(10, $posY);
            $this->SetFillColor($r['vencido'] ? 220 : 255, $r['vencido'] ? 220 : 255, $r['vencido'] ? 220 : 255);
            $this->Cell(35, 6, $this->txt((string) $r['codigo']), 1, 0, 'L', 1);
            $this->Cell(30, 6, $this->cambiarVariable($r['ultimo'], 2), 1, 0, 'R', 1);
            $this->Cell(30, 6, $this->cambiarVariable($r['actual'], 2), 1, 0, 'R', 1);
            $this->Cell(30, 6, $this->cambiarVariable($r['plan'], 2), 1, 0, 'R', 1);
            $this->Cell(30, 6, $this->txt((string) $r['revision']), 1, 0, 'C', 1);
            $this->Cell(30, 6, $this->cambiarVariable(round($r['restante'], 2), 2), 1, 0, 'R', 1);
            $posY += 6;
        }

        return $this->salida($titulo.'.pdf');
    }

    // ------------------------------------------------------------------
    // 161 · ÍNDICES DETERIORADOS (pdf_vehiculos_indicedetereorado)
    // ------------------------------------------------------------------

    public function pdfIndicesDeteriorados(?string $mes = null): \Illuminate\Http\Response
    {
        [$anio, $mesNum] = $this->anioMes($mes);
        $hojas = $this->tecHojasSums($anio, $mesNum);
        $litros = $this->tecLitrosSums($anio, $mesNum);
        $combTaller = $this->tecCombTallerSums($anio, $mesNum);
        $tractivos = $this->tecTractivosDetalle();

        $tituloComb = 'VEHICULOS CON INDICES DE CONSUMOS DETERIORADOS (COMB)';
        $campos = [];
        $campos[] = ['titulo' => 'VEHICULO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '9'];
        $campos[] = ['titulo' => 'KMS', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '9'];
        $campos[] = ['titulo' => 'CONSUMO DURANTE EL MES:', 'ancho' => 60, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];
        $campos[] = ['titulo' => 'INDICES DE CONSUMO DEL MES:', 'ancho' => 102, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];
        $campos1 = [];
        $campos1[] = ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '9'];
        $campos1[] = ['titulo' => 'EN EL', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '9'];
        $campos1[] = ['titulo' => 'Combustible', 'ancho' => 60, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];
        $campos1[] = ['titulo' => 'Combustible', 'ancho' => 102, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];
        $campos3 = [];
        $campos3[] = ['titulo' => '#', 'ancho' => 20, 'direccion' => 'C', 'letra' => '9'];
        $campos3[] = ['titulo' => 'MES', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '9'];
        $campos3[] = ['titulo' => 'Total', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];
        $campos3[] = ['titulo' => 'Hab', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];
        $campos3[] = ['titulo' => 'Tecn', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];
        $campos3[] = ['titulo' => 'Taller', 'ancho' => 14, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];
        $campos3[] = ['titulo' => 'Indice Plan', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];
        $campos3[] = ['titulo' => 'Indice Real', 'ancho' => 17, 'direccion' => 'C', 'letra' => '9'];
        $campos3[] = ['titulo' => '%', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];
        $campos3[] = ['titulo' => 'Comb Dif', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];
        $campos3[] = ['titulo' => '%*', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];
        $campos3[] = ['titulo' => 'Comb Dif*', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];

        $combAhorrado = $combDesahorrado = 0.0;
        $filasComb = [];
        foreach ($tractivos as $v) {
            $hr = $hojas[$v->id] ?? null;
            $kms = $hr ? (float) $hr->kms : 0.0;
            if ($kms <= 0) {
                continue;
            }
            $hab = (float) $hr->hab;
            $tec = (float) $hr->tec;
            $taller = (float) ($combTaller[$v->id] ?? 0);
            $indice = (float) ($v->indice_consumo ?? 0);
            $real = $hab > 0 ? round($kms / $hab, 2) : 0;
            $pct = $real > 0 ? round($indice / $real * 100 - 100, 2) : 0;
            $filasComb[] = ['codigo' => $v->codigo, 'kms' => $kms, 'total' => $hab + $tec, 'hab' => $hab,
                'tec' => $tec, 'taller' => $taller, 'indice' => $indice, 'real' => $real, 'pct' => $pct];
        }

        $this->inicio($tituloComb, $mes ?: '');
        $this->titulos(6, 10, 30, $campos3, $campos, $campos1);
        $posY = 48;
        foreach ($filasComb as $f) {
            if ($posY > 250) {
                $this->inicio($tituloComb, $mes ?: '');
                $this->titulos(6, 10, 30, $campos3, $campos, $campos1);
                $posY = 48;
            }
            $this->SetFont('Arial', '', 10);
            $this->SetXY(10, $posY);
            $this->SetFillColor(255, 255, 255);
            $this->Cell(20, 6, $this->txt((string) $f['codigo']), 1, 0, 'C', 1);
            $this->Cell(16, 6, $this->cambiarVariable($f['kms'], 2), 1, 0, 'R', 1);
            $this->Cell(16, 6, $this->cambiarVariable($f['total'], 2), 1, 0, 'R', 1);
            $this->Cell(16, 6, $this->cambiarVariable($f['hab'], 2), 1, 0, 'R', 1);
            $this->Cell(14, 6, $this->cambiarVariable($f['tec'], 2), 1, 0, 'R', 1);
            $this->Cell(14, 6, $this->cambiarVariable($f['taller'], 2), 1, 0, 'R', 1);
            $this->Cell(17, 6, $this->cambiarVariable($f['indice'], 2), 1, 0, 'R', 1);
            $this->Cell(17, 6, $this->cambiarVariable($f['real'], 2), 1, 0, 'R', 1);
            $this->Cell(17, 6, $this->cambiarVariable($f['pct'], 2), 1, 0, 'R', 1);
            $this->Cell(17, 6, $this->cambiarVariable($f['indice'] > 0 ? $f['kms'] / $f['indice'] - $f['hab'] : 0, 2), 1, 0, 'R', 1);
            $pct2 = $f['pct'] > 0 ? $f['pct'] - 5 : $f['pct'] + 5;
            $this->Cell(17, 6, $this->cambiarVariable($pct2, 2), 1, 0, 'R', 1);
            $dif = $f['hab'] * ($pct2 / 100);
            $this->Cell(17, 6, $this->cambiarVariable($dif, 2), 1, 0, 'R', 1);
            if ($dif > 0) {
                $combAhorrado += $dif;
            } else {
                $combDesahorrado += abs($dif);
            }
            $posY += 6;
        }
        if ($filasComb) {
            $this->SetFont('Arial', 'B', 10);
            $this->SetXY(10, $posY);
            $this->Cell(181, 6, $this->txt('TOTAL DE COMBUSTIBLE CONSUMIDO POR DETERIORO*'), 1, 0, 'R', 1);
            $this->Cell(17, 6, $this->cambiarVariable($combDesahorrado, 2), 1, 0, 'R', 1);
            $this->SetXY(10, $posY += 6);
            $this->Cell(181, 6, $this->txt('TOTAL DE COMBUSTIBLE AHORRADO*'), 1, 0, 'R', 1);
            $this->Cell(17, 6, $this->cambiarVariable($combAhorrado, 2), 1, 0, 'R', 1);
            $this->SetXY(10, $posY += 6);
            $this->Cell(181, 6, $this->txt('DIFERENCIA*'), 1, 0, 'R', 1);
            $this->Cell(17, 6, $this->cambiarVariable($combAhorrado - $combDesahorrado, 2), 1, 0, 'R', 1);
        } else {
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, $this->txt('NO EXISTEN DATOS PARA MOSTRAR'), 0, 1, 'C');
        }

        $this->tecIndicesLubricantes($tractivos, $hojas, $litros, $mes ?: '');

        return $this->salida('vehiculos_indices_deteriorados.pdf');
    }

    protected function tecIndicesLubricantes($tractivos, array $hojas, array $litros, string $mes): void
    {
        $titulo = 'VEHICULOS CON INDICES DE CONSUMOS DETERIORADOS (LUB)';
        $campos = [];
        $campos[] = ['titulo' => 'VEHICULO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '9'];
        $campos[] = ['titulo' => 'KMS', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '9'];
        $campos[] = ['titulo' => 'CONSUMO DURANTE EL MES:', 'ancho' => 48, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];
        $campos[] = ['titulo' => 'INDICES DE CONSUMO DEL MES:', 'ancho' => 114, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];
        $campos1 = [];
        $campos1[] = ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '9'];
        $campos1[] = ['titulo' => 'EN EL', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LR', 'letra' => '9'];
        $campos1[] = ['titulo' => 'Aceite Motor:', 'ancho' => 48, 'direccion' => 'C', 'letra' => '9'];
        $campos1[] = ['titulo' => 'Aceite Relleno', 'ancho' => 114, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];
        $campos3 = [];
        $campos3[] = ['titulo' => '#', 'ancho' => 20, 'direccion' => 'C', 'letra' => '9'];
        $campos3[] = ['titulo' => 'MES', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '9'];
        $campos3[] = ['titulo' => 'Relleno', 'ancho' => 48, 'direccion' => 'C', 'letra' => '9'];
        $campos3[] = ['titulo' => 'Lts/1000kms Plan', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 1, 'letra' => '8'];
        $campos3[] = ['titulo' => 'Lts/1000kms Real', 'ancho' => 25, 'direccion' => 'C', 'letra' => '8'];
        $campos3[] = ['titulo' => '%', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];
        $campos3[] = ['titulo' => 'Lub Dif', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];
        $campos3[] = ['titulo' => '%*', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];
        $campos3[] = ['titulo' => 'Lub Dif*', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 1, 'letra' => '9'];

        $filas = [];
        foreach ($tractivos as $v) {
            $hr = $hojas[$v->id] ?? null;
            $kms = $hr ? (float) $hr->kms : 0.0;
            $litrosMotor = $this->tecLitros($litros, $v->id, 'RELLENO');
            if ($kms <= 0 || $litrosMotor <= 0) {
                continue;
            }
            $indice = (float) ($v->indice_aceite ?? 0);
            $real = round($litrosMotor / $kms * 1000, 2);
            $pct = $indice > 0 ? round($real / $indice * 100 - 100, 2) : 0;
            $filas[] = ['codigo' => $v->codigo, 'kms' => $kms, 'litros' => $litrosMotor, 'indice' => $indice, 'real' => $real, 'pct' => $pct];
        }

        if (empty($filas)) {
            return;
        }

        $lubAhorrado = $lubDesahorrado = 0.0;
        $this->inicio($titulo, $mes);
        $this->titulos(6, 10, 30, $campos3, $campos, $campos1);
        $posY = 48;
        foreach ($filas as $f) {
            if ($posY > 250) {
                $this->inicio($titulo, $mes);
                $this->titulos(6, 10, 30, $campos3, $campos, $campos1);
                $posY = 48;
            }
            $this->SetFont('Arial', '', 10);
            $this->SetXY(10, $posY);
            $this->SetFillColor(255, 255, 255);
            $this->Cell(20, 6, $this->txt((string) $f['codigo']), 1, 0, 'C', 1);
            $this->Cell(16, 6, $this->cambiarVariable($f['kms'], 2), 1, 0, 'R', 1);
            $this->Cell(48, 6, $this->cambiarVariable($f['litros'], 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->cambiarVariable($f['indice'], 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->cambiarVariable($f['real'], 2), 1, 0, 'R', 1);
            $this->Cell(15, 6, $this->cambiarVariable($f['pct'], 2), 1, 0, 'R', 1);
            $this->Cell(17, 6, $this->cambiarVariable($f['litros'] - ($f['indice'] / 1000 * $f['kms']), 2), 1, 0, 'R', 1);
            $pct2 = $f['pct'] > 0 ? $f['pct'] - 5 : $f['pct'] + 5;
            $this->Cell(15, 6, $this->cambiarVariable($pct2, 2), 1, 0, 'R', 1);
            $nuevo = $f['indice'] + ($f['indice'] * ($pct2 / 100));
            $dif2 = $f['litros'] - ($nuevo / 1000 * $f['kms']);
            $this->Cell(17, 6, $this->cambiarVariable($dif2, 2), 1, 0, 'R', 1);
            if ($dif2 < 0) {
                $lubAhorrado += abs($dif2);
            } else {
                $lubDesahorrado += abs($dif2);
            }
            $posY += 6;
        }
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(10, $posY);
        $this->Cell(181, 6, $this->txt('TOTAL DE LUBRICANTE CONSUMIDO POR DETERIORO*'), 1, 0, 'R', 1);
        $this->Cell(17, 6, $this->cambiarVariable($lubDesahorrado, 2), 1, 0, 'R', 1);
        $this->SetXY(10, $posY += 6);
        $this->Cell(181, 6, $this->txt('TOTAL DE LUBRICANTE AHORRADO*'), 1, 0, 'R', 1);
        $this->Cell(17, 6, $this->cambiarVariable($lubAhorrado, 2), 1, 0, 'R', 1);
        $this->SetXY(10, $posY += 6);
        $this->Cell(181, 6, $this->txt('DIFERENCIA*'), 1, 0, 'R', 1);
        $this->Cell(17, 6, $this->cambiarVariable($lubAhorrado - $lubDesahorrado, 2), 1, 0, 'R', 1);
    }

    // ------------------------------------------------------------------
    // 366 · DISPONIBILIDAD DE VAYAS (pdf_cdtvallas)
    // ------------------------------------------------------------------

    public function pdfDisponibilidadVayas(?string $mes = null): \Illuminate\Http\Response
    {
        [$anio, $mesNum] = $this->anioMes($mes);
        $campos = [];
        $campos[] = ['titulo' => '', 'ancho' => 33, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos[] = ['titulo' => 'FONDO HORAS', 'ancho' => 33, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos[] = ['titulo' => 'HORAS ROTO', 'ancho' => 33, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos[] = ['titulo' => 'FONDO HORAS', 'ancho' => 33, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos[] = ['titulo' => 'CDT', 'ancho' => 33, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'];
        $campos1 = [];
        $campos1[] = ['titulo' => 'DIA', 'ancho' => 33, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        $campos1[] = ['titulo' => 'EXPLOTACION', 'ancho' => 33, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        $campos1[] = ['titulo' => 'EN TALLER', 'ancho' => 33, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        $campos1[] = ['titulo' => 'DISPONIBLES', 'ancho' => 33, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];
        $campos1[] = ['titulo' => '%', 'ancho' => 33, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => '8'];

        $vallas = DB::table('vallas')->where('activo', 1)->orderBy('nombre')->get();
        if ($vallas->isEmpty()) {
            $this->tecNoData('DISPONIBILIDAD TECNICA DE VALLAS', $mes ?: '');

            return $this->salida('disponibilidad_vallas.pdf');
        }

        $dias = $this->tecDiasMes($mesNum, $anio);
        foreach ($vallas as $valla) {
            $titulo = 'DISPONIBILIDAD TECNICA DE VALLAS: '.$valla->nombre;
            $this->inicio($titulo, $mes ?: '');
            $this->titulos(6, 10, 30, $campos1, $campos);
            $posY = 42;
            $fhoras = $fhroto = 0.0;
            for ($dia = 1; $dia <= $dias; $dia++) {
                $fecha = sprintf('%04d-%02d-%02d', $anio, $mesNum, $dia);
                $tiempo = (float) DB::table('movimientos_taller')
                    ->where('id_valla', $valla->id)
                    ->where(function ($q) use ($fecha) {
                        $q->where('fecha_inicio', $fecha)
                            ->orWhere('fecha_final', $fecha)
                            ->orWhere(function ($q2) use ($fecha) {
                                $q2->where('fecha_inicio', '<', $fecha)->where('fecha_final', '>', $fecha);
                            });
                    })->sum('tiempo');
                if ($posY > 250) {
                    $this->inicio($titulo, $mes ?: '');
                    $this->titulos(6, 10, 30, $campos1, $campos);
                    $posY = 42;
                }
                $this->SetFont('Arial', '', 10);
                $this->SetXY(10, $posY);
                $this->SetFillColor(255, 255, 255);
                $this->Cell(33, 6, $dia, 1, 0, 'C', 1);
                $this->Cell(33, 6, 24, 1, 0, 'C', 1);
                $this->Cell(33, 6, $tiempo, 1, 0, 'C', 1);
                $this->Cell(33, 6, 24 - $tiempo, 1, 0, 'C', 1);
                $this->Cell(33, 6, round((24 - $tiempo) / 24 * 100, 2), 1, 0, 'C', 1);
                $fhoras += 24;
                $fhroto += $tiempo;
                $posY += 6;
            }
            $this->SetFont('Arial', 'B', 10);
            $this->SetXY(10, $posY);
            $this->SetFillColor(220, 220, 220);
            $this->Cell(33, 6, 'PROMEDIO', 1, 0, 'C', 1);
            $this->SetFillColor(255, 255, 255);
            $this->Cell(33, 6, $fhoras, 1, 0, 'C', 1);
            $this->Cell(33, 6, $fhroto, 1, 0, 'C', 1);
            $this->Cell(33, 6, $fhoras - $fhroto, 1, 0, 'C', 1);
            $this->Cell(33, 6, $fhoras > 0 ? round(($fhoras - $fhroto) / $fhoras * 100, 2) : 0, 1, 0, 'C', 1);
        }

        return $this->salida('disponibilidad_vallas.pdf');
    }

    // ------------------------------------------------------------------
    // 367 · OPERACIONES TALLER X OPERARIOS (pdf_operacionesxoperarios)
    // ------------------------------------------------------------------

    public function pdfOperacionesTallerOperarios(?string $mes = null, ?string $operario = null): \Illuminate\Http\Response
    {
        [$anio, $mesNum] = $this->anioMes($mes);
        $titulo = 'OPERACIONES REALIZADAS EN TALLER X OPERARIOS';
        $campos1 = [];
        $campos1[] = ['titulo' => '#', 'ancho' => 10, 'direccion' => 'C', 'letra' => '10', 'bordes' => 'LTR'];
        $campos1[] = ['titulo' => 'NAVE', 'ancho' => 20, 'direccion' => 'C', 'letra' => '10', 'bordes' => 'LTR'];
        $campos1[] = ['titulo' => 'VALLA', 'ancho' => 25, 'direccion' => 'C', 'letra' => '10', 'bordes' => 'LTR'];
        $campos1[] = ['titulo' => 'OPERACIONES', 'ancho' => 85, 'direccion' => 'C', 'letra' => '10', 'bordes' => 'LTR'];
        $campos1[] = ['titulo' => 'INICIO', 'ancho' => 30, 'direccion' => 'C', 'letra' => '10', 'bordes' => 'LTR'];
        $campos1[] = ['titulo' => 'FIN', 'ancho' => 30, 'direccion' => 'C', 'letra' => '10', 'bordes' => 'LTR'];
        $campos1[] = ['titulo' => 'TIEMPO', 'ancho' => 20, 'direccion' => 'C', 'letra' => '10', 'bordes' => 'LTR'];
        $campos1[] = ['titulo' => 'TIEMPO', 'ancho' => 20, 'direccion' => 'C', 'letra' => '10', 'bordes' => 'LTR'];
        $campos1[] = ['titulo' => 'DIF', 'ancho' => 20, 'direccion' => 'C', 'letra' => '10', 'bordes' => 'LTR'];
        $campos2 = [];
        $campos2[] = ['titulo' => '', 'ancho' => 10, 'direccion' => 'C', 'letra' => '10', 'bordes' => 'LBR'];
        $campos2[] = ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'letra' => '10', 'bordes' => 'LBR'];
        $campos2[] = ['titulo' => '', 'ancho' => 25, 'direccion' => 'C', 'letra' => '10', 'bordes' => 'LBR'];
        $campos2[] = ['titulo' => '', 'ancho' => 85, 'direccion' => 'C', 'letra' => '10', 'bordes' => 'LBR'];
        $campos2[] = ['titulo' => '', 'ancho' => 30, 'direccion' => 'C', 'letra' => '10', 'bordes' => 'LBR'];
        $campos2[] = ['titulo' => '', 'ancho' => 30, 'direccion' => 'C', 'letra' => '10', 'bordes' => 'LBR'];
        $campos2[] = ['titulo' => 'LAB', 'ancho' => 20, 'direccion' => 'C', 'letra' => '10', 'bordes' => 'LBR'];
        $campos2[] = ['titulo' => 'PROG', 'ancho' => 20, 'direccion' => 'C', 'letra' => '10', 'bordes' => 'LBR'];
        $campos2[] = ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'letra' => '10', 'bordes' => 'LBR'];

        $q = DB::table('ordenes_operaciones as oo')
            ->leftJoin('ordenes_taller as ot', 'ot.id', '=', 'oo.id_orden_taller')
            ->leftJoin('naves as n', 'n.id', '=', 'oo.id_nave')
            ->leftJoin('vallas as v', 'v.id', '=', 'oo.id_valla')
            ->leftJoin('catalogo_items as top', 'top.id', '=', 'oo.id_tipo_operacion')
            ->leftJoin('bolsa as b', 'b.id', '=', 'oo.id_operario')
            ->whereIn('oo.id_entidad', $this->entidadIds)
            ->whereRaw('YEAR(COALESCE(oo.fecha_inicio, oo.fecha_final)) = ?', [$anio]);
        if ($mesNum) {
            $q->whereRaw('MONTH(COALESCE(oo.fecha_inicio, oo.fecha_final)) = ?', [$mesNum]);
        }
        if ($operario && $operario !== 'TODOS') {
            $q->where('oo.id_operario', (int) $operario);
        }
        $data = $q->select('oo.*', 'n.nombre as nave', 'v.nombre as valla', 'top.nombre as operacion',
            'b.nombre as operario_nombre', 'b.apellidos as operario_apellidos')
            ->orderBy('oo.fecha_inicio')->get();

        if ($data->isEmpty()) {
            $this->tecNoData($titulo, $mes ?: '');

            return $this->salida($titulo.'.pdf');
        }

        $operarioNombre = trim(($data->first()->operario_nombre ?? '').' '.($data->first()->operario_apellidos ?? ''));
        $this->inicio($titulo, $mes ?: '');
        $this->tecFirmas(true);
        $this->titulos(7, 15, 35, $campos2, $campos1);
        $this->SetFont('Arial', 'B', 12);
        $this->SetXY(15, 28);
        $this->Cell(20, 6, $this->txt('OPERARIO: '.$operarioNombre), 0, 1, 'L');
        $posY = 48;
        $nro = 1;
        $tlab = $tprog = 0.0;

        foreach ($data as $arr) {
            $observacion = $this->tecAjustarNotas((string) $arr->operacion, 40);
            for ($k = 0; $k < count($observacion); $k++) {
                if ($posY > 250) {
                    $this->inicio($titulo, $mes ?: '');
                    $this->tecFirmas(true);
                    $this->titulos(7, 15, 35, $campos2, $campos1);
                    $this->SetFont('Arial', 'B', 12);
                    $this->SetXY(15, 28);
                    $this->Cell(20, 6, $this->txt('OPERARIO: '.$operarioNombre), 0, 1, 'L');
                    $posY = 48;
                }
                $borde = $k < count($observacion) - 1 ? ($k === 0 ? 'LRT' : 'LR') : (count($observacion) === 1 ? 'LRTB' : 'LRB');
                $this->SetFont('Arial', '', 10);
                $this->SetXY(15, $posY);
                $this->SetFillColor(255, 255, 255);
                $this->Cell(10, 6, $k === 0 ? $nro : '', $borde, 0, 'C', 1);
                $this->Cell(20, 6, $k === 0 ? $this->txt((string) $arr->nave) : '', $borde, 0, 'C', 1);
                $this->Cell(25, 6, $k === 0 ? $this->txt((string) $arr->valla) : '', $borde, 0, 'C', 1);
                $this->Cell(85, 6, $this->txt($observacion[$k]), $borde, 0, 'L', 1);
                $this->Cell(30, 6, $k === 0 ? $this->txt((string) $arr->fecha_inicio.' '.(string) $arr->hora_inicio) : '', $borde, 0, 'C', 1);
                $this->Cell(30, 6, $k === 0 ? $this->txt((string) $arr->fecha_final.' '.(string) $arr->hora_final) : '', $borde, 0, 'C', 1);
                $this->Cell(20, 6, $k === 0 ? $this->txt((string) $arr->tiempo) : '', $borde, 0, 'C', 1);
                $this->Cell(20, 6, $k === 0 ? '' : '', $borde, 0, 'C', 1);
                $this->Cell(20, 6, $k === 0 ? '' : '', $borde, 0, 'C', 1);
                if ($k === 0) {
                    $tlab += (float) $arr->tiempo;
                }
                $posY += 6;
            }
            $nro++;
        }

        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(15, $posY);
        $this->SetFillColor(220, 220, 220);
        $this->Cell(200, 6, 'TOTALES', 1, 0, 'C', 1);
        $this->SetFillColor(255, 255, 255);
        $this->Cell(20, 6, $tlab, 1, 0, 'C', 1);
        $this->Cell(20, 6, $tprog, 1, 0, 'C', 1);
        $this->Cell(20, 6, $tlab - $tprog, 1, 0, 'C', 1);

        return $this->salida($titulo.'.pdf');
    }

    // ------------------------------------------------------------------
    // 369 · GASTO DE LUBRICANTES, GRASAS Y LÍQUIDOS (pdf_gastolubgras)
    // ------------------------------------------------------------------

    public function pdfGastoLubricantes(?string $mes = null): \Illuminate\Http\Response
    {
        [$anio, $mesNum] = $this->anioMes($mes);
        $titulo = 'GASTO DE LUBRICANTES, GRASAS Y LIQUIDOS';
        $campos1 = [];
        $campos1[] = ['titulo' => 'MARCA', 'ancho' => 40, 'direccion' => 'C', 'letra' => '10'];
        $campos1[] = ['titulo' => 'CLASIFICACION', 'ancho' => 40, 'direccion' => 'C', 'letra' => '10'];
        $campos1[] = ['titulo' => 'TIPO', 'ancho' => 60, 'direccion' => 'C', 'letra' => '10'];
        $campos1[] = ['titulo' => 'CANTIDAD', 'ancho' => 30, 'direccion' => 'C', 'letra' => '10'];

        $slots = [
            'id_lub_motor' => 'litros_motor',
            'id_lub_transmision' => 'litros_transmision',
            'id_lub_direccion' => 'litros_direccion',
            'id_lub_hidraulico' => 'litros_hidraulico',
            'id_grasa_rollete' => 'grasa_rollete',
            'id_grasa_copillas' => 'grasa_copillas',
            'id_liquido_freno' => 'liquido_freno',
            'id_agua' => 'agua_refrigerada',
        ];

        $q = DB::table('control_lubricantes')->whereIn('id_entidad', $this->entidadIds)
            ->whereRaw('YEAR(fecha_cambio) = ?', [$anio]);
        if ($mesNum) {
            $q->whereRaw('MONTH(fecha_cambio) = ?', [$mesNum]);
        }
        $rows = $q->get();

        $acum = [];
        foreach ($rows as $r) {
            foreach ($slots as $fk => $col) {
                $idLub = $r->{$fk} ?? null;
                if (! $idLub) {
                    continue;
                }
                $litros = (float) ($r->{$col} ?? 0);
                if ($litros <= 0) {
                    continue;
                }
                $acum[$idLub] = ($acum[$idLub] ?? 0) + $litros;
            }
        }

        $nombres = DB::table('lubricantes')->pluck('nombre', 'id');

        if (empty($acum)) {
            $this->tecNoData($titulo, $mes ?: '');

            return $this->salida($titulo.'.pdf');
        }

        $this->inicio($titulo, $mes ?: '');
        $this->titulos(7, 15, 35, $campos1);
        $posY = 42;
        $this->SetFont('Arial', '', 10);
        foreach ($acum as $idLub => $cantidad) {
            if ($posY > 250) {
                $this->inicio($titulo, $mes ?: '');
                $this->titulos(7, 15, 35, $campos1);
                $posY = 42;
            }
            $this->SetXY(15, $posY);
            $this->SetFillColor(255, 255, 255);
            $this->Cell(40, 6, '', 1, 0, 'C', 1);
            $this->Cell(40, 6, '', 1, 0, 'C', 1);
            $this->Cell(60, 6, $this->txt((string) ($nombres[$idLub] ?? '')), 1, 0, 'C', 1);
            $this->Cell(30, 6, $this->cambiarVariable($cantidad, 2), 1, 0, 'C', 1);
            $posY += 6;
        }

        return $this->salida($titulo.'.pdf');
    }

    // ------------------------------------------------------------------
    // 376 · CONCILIACIÓN DISPONIBILIDAD VEHÍCULOS
    // ------------------------------------------------------------------

    public function pdfConciliacionDisponibilidad(): \Illuminate\Http\Response
    {
        $titulo = 'CONCILIACION DISPONIBILIDAD VEHICULOS';
        $campos = [];
        $campos[] = ['titulo' => 'VEHICULO', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTR'];
        $campos[] = ['titulo' => 'MARCA', 'ancho' => 50, 'direccion' => 'C', 'bordes' => 'LTR'];
        $campos[] = ['titulo' => 'ESTADO', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LTR'];
        $campos[] = ['titulo' => 'DISPONIBILIDAD', 'ancho' => 60, 'direccion' => 'C'];
        $campos1 = [];
        $campos1[] = ['titulo' => '', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB'];
        $campos1[] = ['titulo' => '', 'ancho' => 50, 'direccion' => 'C', 'bordes' => 'LRB'];
        $campos1[] = ['titulo' => '', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRB'];
        $campos1[] = ['titulo' => 'VEHICULO', 'ancho' => 30, 'direccion' => 'C'];
        $campos1[] = ['titulo' => 'SEGUN CICLO', 'ancho' => 30, 'direccion' => 'C'];

        $tractivos = $this->tecTractivosDetalle();
        $rows = [];
        foreach ($tractivos as $v) {
            $disponible = (float) $v->kms_plan_mtto - (float) $v->kilometraje_actual;
            $kmsDisp = (float) $v->kms_disp;
            if (round($kmsDisp, 2) === round($disponible, 2)) {
                continue;
            }
            $rows[] = ['codigo' => $v->codigo, 'marca' => $v->marca, 'estado' => $v->estado,
                'kmsdisp' => $kmsDisp, 'disponible' => $disponible];
        }

        if (empty($rows)) {
            $this->tecNoData($titulo);

            return $this->salida($titulo.'.pdf');
        }

        $this->inicio($titulo);
        $this->titulos(6, 10, 35, $campos1, $campos);
        $posY = 47;
        foreach ($rows as $r) {
            if ($posY > 250) {
                $this->inicio($titulo);
                $this->titulos(6, 10, 35, $campos1, $campos);
                $posY = 47;
            }
            $this->SetFont('Arial', '', 10);
            $this->SetXY(10, $posY);
            $this->SetFillColor(255, 255, 255);
            $this->Cell(25, 7, $this->txt((string) $r['codigo']), 1, 0, 'C', 1);
            $this->Cell(50, 7, $this->txt((string) $r['marca']), 1, 0, 'L', 1);
            $this->Cell(30, 7, $this->txt((string) $r['estado']), 1, 0, 'L', 1);
            $this->Cell(30, 7, $this->cambiarVariable($r['kmsdisp'], 2), 1, 0, 'R', 1);
            $this->Cell(30, 7, $this->cambiarVariable($r['disponible'], 2), 1, 0, 'R', 1);
            $posY += 7;
        }

        return $this->salida($titulo.'.pdf');
    }

    // ------------------------------------------------------------------
    // 4025 · CERTIFICACIÓN ÍNDICES DE CONSUMO (pdf_certificacion_indice)
    // ------------------------------------------------------------------

    public function pdfCertificoIndicesConsumo(?string $tractivo = null): \Illuminate\Http\Response
    {
        $titulo = 'CERTIFICACION INDICE DIESEL TRAFICO(IDT) E INDICE DE CONSUMO COMBUSTIBLE';
        $campos = [];
        $campos[] = ['titulo' => 'NRO', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR'];
        $campos[] = ['titulo' => 'NOMBRE Y APELLIDOS', 'ancho' => 70, 'direccion' => 'C', 'bordes' => 'LTR'];
        $campos[] = ['titulo' => 'KMS TOT', 'ancho' => 25, 'direccion' => 'C', 'letra' => 10];
        $campos[] = ['titulo' => 'CONSUMO', 'ancho' => 25, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'INDICE', 'ancho' => 20, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'IDT REAL', 'ancho' => 20, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'CANT HR', 'ancho' => 20, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'EQUIPOS', 'ancho' => 30, 'direccion' => 'C'];

        $q = DB::table('hojas_ruta as hr')
            ->join('bolsa as b', 'b.id', '=', 'hr.id_chofer')
            ->whereIn('hr.id_entidad', $this->entidadIds)
            ->whereNotNull('hr.id_chofer');
        if ($tractivo && $tractivo !== 'TODOS') {
            $q->join('tractivos as t', 't.id', '=', 'hr.id_tractivo')
                ->where(function ($w) use ($tractivo) {
                    $w->where('t.codigo', (string) $tractivo);
                    if (is_numeric($tractivo)) {
                        $w->orWhere('t.id', (int) $tractivo);
                    }
                });
        }
        $data = $q->selectRaw('hr.id_chofer, b.nombre, b.apellidos,
                COALESCE(SUM(hr.kms_totales),0) kmstot,
                COALESCE(SUM(hr.combustible_habilitado),0) combhab,
                COUNT(*) canthr,
                COUNT(DISTINCT hr.id_tractivo) equipos')
            ->groupBy('hr.id_chofer', 'b.nombre', 'b.apellidos')
            ->orderBy('b.nombre')->get();

        if ($data->isEmpty()) {
            $this->tecNoData($titulo);

            return $this->salida($titulo.'.pdf');
        }

        $this->inicio($titulo);
        $this->titulos(6, 15, 35, $campos);
        $posY = 41;
        $nro = 1;
        $kmsTotal = $combTotal = $canthrTotal = 0.0;

        foreach ($data as $arr) {
            if ($posY > 250) {
                $this->inicio($titulo);
                $this->titulos(6, 15, 35, $campos);
                $posY = 41;
            }
            $kms = (float) $arr->kmstot;
            if ($kms <= 0) {
                continue;
            }
            $comb = (float) $arr->combhab;
            $indice = $comb > 0 ? round($kms / $comb, 2) : 0;
            $nombre = trim(($arr->nombre ?? '').' '.($arr->apellidos ?? ''));
            $this->SetFont('Arial', 'B', 10);
            $this->SetXY(15, $posY);
            $this->SetFillColor(255, 255, 255);
            $this->Cell(15, 9, $nro, 1, 0, 'R', 1);
            $this->Cell(70, 9, $this->txt($nombre), 1, 0, 'L', 1);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(25, 9, $this->cambiarVariable($kms, 2), 1, 0, 'R', 1);
            $this->Cell(25, 9, $this->cambiarVariable($comb, 2), 1, 0, 'R', 1);
            $this->Cell(20, 9, $this->cambiarVariable($indice, 2), 1, 0, 'R', 1);
            $this->Cell(20, 9, $this->cambiarVariable($indice, 2), 1, 0, 'R', 1);
            $this->Cell(20, 9, $this->cambiarVariable($arr->canthr, 0), 1, 0, 'R', 1);
            $this->Cell(30, 9, $arr->equipos, 1, 0, 'R', 1);
            $kmsTotal += $kms;
            $combTotal += $comb;
            $canthrTotal += (float) $arr->canthr;
            $posY += 9;
            $nro++;
        }

        $indiceReal = $combTotal > 0 ? round($kmsTotal / $combTotal, 2) : 0;
        $this->SetFont('Arial', 'B', 12);
        $this->SetXY(15, $posY);
        $this->SetFillColor(220, 220, 220);
        $this->Cell(85, 9, 'TOTAL', 1, 0, 'C', 1);
        $this->Cell(25, 9, $this->cambiarVariable($kmsTotal, 2), 1, 0, 'R', 1);
        $this->Cell(25, 9, $this->cambiarVariable($combTotal, 2), 1, 0, 'R', 1);
        $this->Cell(20, 9, $this->cambiarVariable($indiceReal, 2), 1, 0, 'R', 1);
        $this->Cell(20, 9, '', 1, 0, 'R', 1);
        $this->Cell(20, 9, $this->cambiarVariable($canthrTotal, 0), 1, 0, 'R', 1);
        $this->Cell(30, 9, '', 1, 0, 'R', 1);

        return $this->salida($titulo.'.pdf');
    }
}
