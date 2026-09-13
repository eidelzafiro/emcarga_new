<?php

namespace App\Services\Reports\Fpdf\Concerns;

use App\Models\Bateria;
use App\Models\BateriasMovimiento;
use App\Models\Bolsa;
use App\Models\CatalogoItem;
use App\Models\ControlLubricante;
use App\Models\GastosOrden;
use App\Models\Motore;
use App\Models\OrdenesTaller;
use App\Models\OrdenesOperacione;
use App\Models\Tractivo;
use Illuminate\Support\Facades\DB;

/**
 * Reportes de taller/técnica (CONTROL TALLER / TECNICA). Replican el layout
 * del legacy `Reportestec.php` usando el esquema nuevo.
 *
 * Métodos:
 *   137 pdfCt2MttoEventuales      legacy pdf_ct2($codEquipo,$mes)
 *   142 pdfCt5MovimientoMes       legacy pdf_ordentaller_mes($mes,$unidad)
 *   146 pdfCt8VidaUtilBateria     legacy pdf_ct8()
 *   147 pdfCt3TiempoAgregados     legacy pdf_ct3($mes,$unidad)
 *   148 pdfCt1Expediente          legacy pdf_ct1($idtractivo)
 *   149 pdfCt7ControlLubricantes  legacy pdf_control_lub($mes,$unidad)
 *   159 pdfCt6AnalisisMotores     legacy pdf_ct6($idmotores)
 *   160 pdfCt4ReparacionMantenimiento legacy pdf_ct4($idordentaller)
 *   163 pdfCt5MovimientoFecha     legacy pdf_ordentaller_mes_fecha($fecha)
 *   343 pdfDatosGeneralesParque   legacy pdf_tractivos($unidad)
 *   344 pdfCrtCirculacion         legacy pdf_seguridadautomotor($unidad)
 *   361 pdfMttosExterior          legacy pdf_mttos_exterior($mes,$idunidad)
 */
trait TecnicaTaller
{
    // ---------------------------------------------------------------------
    // Utilidades internas
    // ---------------------------------------------------------------------

    /** Replica Reportestec::ajustar_notas(). */
    protected function ctAjustarNotas($string, int $largo): array
    {
        $palabras = explode(' ', (string) $string);
        $arrpalabras = [];
        $linea = '';
        for ($i = 0; $i < count($palabras); $i++) {
            if (strlen($linea) + strlen($palabras[$i]) <= $largo) {
                $linea .= ' '.$palabras[$i];
            } else {
                $arrpalabras[] = $linea;
                $linea = $palabras[$i];
            }
        }
        $arrpalabras[] = $linea;

        return $arrpalabras;
    }

    /** Página "NO EXISTEN DATOS PARA MOSTRAR" del legacy. */
    protected function tecnicaNoData(string $titulo, string $fecha = ''): void
    {
        $this->inicio($titulo, $fecha);
        $this->SetFont('Arial', 'B', 25);
        $this->SetFillColor(255, 255, 255);
        $this->SetXY(10, 65);
        $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
    }

    /** Fecha legible dd/mm/aaaa. */
    protected function tecnicaFecha($valor): string
    {
        if (empty($valor) || $valor === '0000-00-00') {
            return '';
        }
        try {
            return \Carbon\Carbon::parse($valor)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $valor;
        }
    }

    /** Hora HH:MM. */
    protected function tecnicaHora($valor): string
    {
        if (empty($valor)) {
            return '';
        }
        try {
            return \Carbon\Carbon::parse($valor)->format('H:i');
        } catch (\Throwable) {
            return (string) $valor;
        }
    }

    /** Diferencia absoluta en días (paridad restaFechas legacy). */
    protected function tecnicaDias($desde, $hasta): ?int
    {
        if (empty($desde) || empty($hasta)) {
            return null;
        }
        try {
            return (int) round(abs(\Carbon\Carbon::parse($desde)->diffInDays(\Carbon\Carbon::parse($hasta))));
        } catch (\Throwable) {
            return null;
        }
    }

    /** Diferencia con signo (hasta - desde) en días. */
    protected function tecnicaDiasSigno($desde, $hasta): ?int
    {
        if (empty($desde) || empty($hasta)) {
            return null;
        }
        try {
            return (int) round(\Carbon\Carbon::parse($hasta)->diffInDays(\Carbon\Carbon::parse($desde), false));
        } catch (\Throwable) {
            return null;
        }
    }

    /** Encabezado de columna gris. */
    protected function th(float $w, float $h, $txt, $border = 1, string $align = 'C'): void
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(200, 200, 200);
        $this->Cell($w, $h, $this->txt((string) $txt), $border, 0, $align, true);
    }

    /** Celda de datos blanca. */
    protected function td(float $w, float $h, $txt, $border = 1, string $align = 'C'): void
    {
        $this->SetFont('Arial', '', 9);
        $this->SetFillColor(255, 255, 255);
        $this->Cell($w, $h, $this->txt((string) $txt), $border, 0, $align, true);
    }

    /** Busca un tractivo por id o código. */
    protected function buscarTractivo($valor): ?Tractivo
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        $q = Tractivo::query();
        if (is_numeric($valor)) {
            $q->where('id', (int) $valor);
        } else {
            $q->where('codigo', (string) $valor);
        }

        return $q->first();
    }

    /** Nombre de catálogo por id (catalogo_items). */
    protected function catalogoNombre($id): string
    {
        if (empty($id)) {
            return '';
        }
        static $cache = [];
        if (! array_key_exists($id, $cache)) {
            $cache[$id] = (string) (CatalogoItem::where('id', $id)->value('nombre') ?? '');
        }

        return $cache[$id];
    }

    /** Nombre completo de una persona de bolsa. */
    protected function bolsaNombre($id): string
    {
        if (empty($id)) {
            return '';
        }
        static $cache = [];
        if (! array_key_exists($id, $cache)) {
            $b = Bolsa::find($id);
            $cache[$id] = $b ? trim($b->nombre.' '.$b->apellidos) : '';
        }

        return $cache[$id];
    }

    // ---------------------------------------------------------------------
    // 137 · CT-2 CONTROL MTTO Y REPARACIONES EVENTUALES
    // ---------------------------------------------------------------------

    public function pdfCt2MttoEventuales(?string $tractivo = null)
    {
        $t = $this->buscarTractivo($tractivo);
        if (! $t) {
            $this->tecnicaNoData('CONTROL DE VEHICULOS EN TALLER (CT-2) '.($tractivo ?? ''));

            return $this->salida('CT-2.pdf');
        }

        [$anio, $m] = $this->anioMes(null);
        $mes = sprintf('%02d', $m);
        $titulo = 'CONTROL DE VEHICULOS EN TALLER (CT-2) '.$t->codigo;
        $this->inicio($titulo, $mes);

        $dias = (int) date('t', strtotime(sprintf('%04d-%02d-01', $anio, $m)));
        $posYTop = 32;
        $posX = 10;
        $posYMid = 40;

        $kmTotal = 0.0;
        $combTotal = 0.0;
        $combTaller = 0.0;
        $combTec = 0.0;
        $lubTotal = 0.0;
        $lubRellTotal = 0.0;
        $liquidoTotal = 0.0;

        $cantMtto = 0;
        $fechaTaller = null;

        // Bloque de planificación derecha (primera OT de mtto).
        $otPlan = OrdenesTaller::where('id_tractivo', $t->id)
            ->whereNotNull('planificacion')
            ->orderByDesc('fecha_ingreso')->first();
        $this->SetXY(186, $posYTop);
        $this->SetFillColor(200, 200, 200);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(13, 20, $otPlan?->planificacion !== null ? round((float) $otPlan->planificacion) : '', 1, 0, 'C', true);
        $this->Cell(13, 20, $otPlan?->km_mtto_prox !== null ? round((float) $otPlan->km_mtto_prox) : '', 1, 0, 'C', true);
        $cantMtto = 1;

        for ($dia = 1; $dia <= $dias; $dia++) {
            if ($dia === 16) {
                $posYMid = 40;
                $posX = 98;
            }
            $fecha = sprintf('%04d-%02d-%02d', $anio, $m, $dia);

            $ordenes = OrdenesTaller::with(['operaciones.tipoOperacion', 'gastos.tipoAgregado'])
                ->where('id_tractivo', $t->id)
                ->whereDate('fecha_ingreso', $fecha)
                ->orderBy('id')->get();
            $ot = $ordenes->first();

            $agregados = [];
            $operaciones = [];
            foreach ($ordenes as $o) {
                foreach ($o->gastos as $g) {
                    $agregados[] = $g->tipoAgregado?->nombre ?? $g->nombre;
                }
                foreach ($o->operaciones as $op) {
                    $operaciones[] = $op->tipoOperacion?->nombre ?? '';
                }
            }

            $hr = DB::table('hojas_ruta')
                ->where('id_tractivo', $t->id)
                ->whereDate('fecha_emision', $fecha)
                ->selectRaw('COALESCE(SUM(kms_totales),0) as kms, COALESCE(SUM(combustible_habilitado),0) as hab, COALESCE(SUM(combustible_tecnico),0) as tec, MAX(numero) as nrohr')
                ->first();
            $kmDia = (float) ($hr->kms ?? 0);
            $combDia = (float) ($hr->hab ?? 0) + (float) ($hr->tec ?? 0);
            $kmTotal += $kmDia;
            $combTotal += $combDia;
            $combTec += (float) ($hr->tec ?? 0);
            $combTaller += (float) ($ot->comb_taller ?? 0);

            $lub = ControlLubricante::where('id_tractivo', $t->id)
                ->whereDate('fecha_cambio', $fecha)
                ->where(function ($q) {
                    $q->whereNull('tipo_operacion')->orWhere('tipo_operacion', '!=', 'RELLENO');
                })->sum('litros_motor');
            $lubRell = ControlLubricante::where('id_tractivo', $t->id)
                ->whereDate('fecha_cambio', $fecha)
                ->where('tipo_operacion', 'RELLENO')->sum('litros_motor');
            $liquido = ControlLubricante::where('id_tractivo', $t->id)
                ->whereDate('fecha_cambio', $fecha)->sum('liquido_freno');
            $lubTotal += (float) $lub;
            $lubRellTotal += (float) $lubRell;
            $liquidoTotal += (float) $liquido;

            // Fila 1
            $this->SetXY($posX, $posYMid += 4);
            $this->SetFillColor(200, 200, 200);
            $this->SetFont('Arial', 'B', 9);
            $this->Cell(10, 4, '', 'LRT', 0, 'C', true);
            $this->Cell(15, 4, $ot->tipo_mtto ?? ($ot ? 'T' : ''), 1, 0, 'C', true);
            $this->Cell(15, 4, $operaciones[0] ?? ($hr->nrohr ?? ''), 1, 0, 'C', true);
            $this->SetFillColor(255, 255, 255);
            $this->SetFont('Arial', '', 9);
            $this->Cell(15, 4, $this->cambiarVariable($combDia ?: ''), 1, 0, 'C', true);
            $this->SetFont('Arial', 'B', 9);
            $this->Cell(15, 4, $this->cambiarVariable($combTotal), 1, 0, 'C', true);
            $this->SetFont('Arial', '', 9);
            $this->Cell(18, 4, $this->cambiarVariable($kmDia ?: ''), 1, 0, 'C', true);

            // Fila 2
            $this->SetXY($posX, $posYMid += 4);
            $this->SetFillColor(200, 200, 200);
            $this->SetFont('Arial', 'IB', 9);
            $this->Cell(10, 4, $dia, 'LR', 0, 'C', true);
            $this->SetFont('Arial', '', 9);
            $this->Cell(15, 4, $agregados[0] ?? '', 1, 0, 'C', true);
            $this->Cell(15, 4, $operaciones[1] ?? '', 1, 0, 'C', true);
            $this->SetFillColor(255, 255, 255);
            $this->Cell(15, 4, $this->cambiarVariable($lub ?: ''), 1, 0, 'C', true);
            $this->SetFont('Arial', 'B', 9);
            $this->Cell(15, 4, $this->cambiarVariable($lubTotal), 1, 0, 'C', true);
            $this->Cell(18, 4, $kmTotal + (float) ($t->kilometraje_actual ?? 0), 1, 0, 'C', true);

            // Fila 3
            $this->SetXY($posX, $posYMid += 4);
            $this->SetFillColor(200, 200, 200);
            $this->SetFont('Arial', 'IB', 9);
            $this->Cell(10, 4, '', 'LRB', 0, 'C', true);
            $this->SetFont('Arial', '', 9);
            $this->Cell(15, 4, $agregados[1] ?? '', 1, 0, 'C', true);
            $this->Cell(15, 4, $operaciones[2] ?? '', 1, 0, 'C', true);
            $this->SetFillColor(255, 255, 255);
            $this->Cell(15, 4, $this->cambiarVariable($liquido ?: ''), 1, 0, 'C', true);
            $this->Cell(15, 4, $this->cambiarVariable($liquidoTotal), 1, 0, 'C', true);
            $this->SetFillColor(200, 200, 200);
            $this->Cell(18, 4, '', 1, 0, 'C', true);

            // Planificación de la OT del día
            if ($ot && $ot->planificacion !== null) {
                $this->SetXY(186, $posYTop + ($cantMtto * 20));
                $this->SetFillColor(200, 200, 200);
                $this->SetFont('Arial', 'B', 9);
                $this->Cell(13, 20, round((float) $ot->planificacion), 1, 0, 'C', true);
                $this->Cell(13, 20, round((float) ($ot->km_mtto_prox ?? 0)), 1, 0, 'C', true);
                $cantMtto++;
            }
        }

        // Resumen inferior
        $posYB = $dias === 31 ? 236 : 224;
        $this->SetXY(10, $posYB);
        $this->SetFillColor(200, 200, 200);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(20, 4, 'KMS', 'LTR', 0, 'C', true);
        $this->Cell(100, 4, 'COMBUSTIBLE', 1, 0, 'C', true);
        $this->Cell(56, 4, 'ACEITE', 1, 0, 'C', true);
        $this->SetXY(10, $posYB += 4);
        $this->SetFillColor(200, 200, 200);
        $this->Cell(20, 4, 'MES', 'LBR', 0, 'C', true);
        $this->Cell(20, 4, 'TOTAL', 1, 0, 'C', true);
        $this->Cell(20, 4, 'HAB', 1, 0, 'C', true);
        $this->Cell(20, 4, 'TECN', 1, 0, 'C', true);
        $this->Cell(20, 4, 'TALLER', 1, 0, 'C', true);
        $this->Cell(20, 4, 'KMS/LTS', 1, 0, 'C', true);
        $this->Cell(19, 4, 'RELLENO', 1, 0, 'C', true);
        $this->Cell(19, 4, 'LIQ FRENO', 1, 0, 'C', true);
        $this->Cell(18, 4, 'LTS/KMS', 1, 0, 'C', true);

        $this->SetXY(10, $posYB += 4);
        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', '', 9);
        $hab = $combTotal - $combTec - $combTaller;
        $this->Cell(20, 4, $this->cambiarVariable($kmTotal, 2), 1, 0, 'C', true);
        $this->Cell(20, 4, $this->cambiarVariable($combTotal, 2), 1, 0, 'C', true);
        $this->Cell(20, 4, $this->cambiarVariable($hab, 2), 1, 0, 'C', true);
        $this->Cell(20, 4, $this->cambiarVariable($combTec, 2), 1, 0, 'C', true);
        $this->Cell(20, 4, $this->cambiarVariable($combTaller, 2), 1, 0, 'C', true);
        $this->Cell(20, 4, $hab != 0 ? $this->cambiarVariable(round($kmTotal / $hab, 2), 2) : '', 1, 0, 'C', true);
        $this->Cell(19, 4, $this->cambiarVariable($lubRellTotal, 2), 1, 0, 'C', true);
        $this->Cell(19, 4, $this->cambiarVariable($liquidoTotal, 2), 1, 0, 'C', true);
        $this->Cell(18, 4, $kmTotal != 0 ? $this->cambiarVariable(round($lubRellTotal / $kmTotal * 1000, 2), 2) : '', 1, 0, 'C', true);

        // Recorrido hasta el mes anterior
        $this->SetXY(10, $posYTop);
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(200, 200, 200);
        $this->Cell(44, 4, 'Recorrido hasta', 'LTR', 0, 'C', true);
        $this->Cell(44, 4, 'Motor: ', 1, 0, 'C', true);
        $this->Cell(44, 4, 'Caja: ', 1, 0, 'C', true);
        $this->Cell(44, 4, 'Diferencial: ', 1, 0, 'C', true);
        $this->SetXY(10, $posYTop += 4);
        $this->Cell(44, 4, 'el mes anterior: ', 'LBR', 0, 'C', true);
        $this->SetFont('Arial', '', 9);
        $this->SetFillColor(255, 255, 255);
        $this->Cell(44, 4, $t->motor?->kms_acumulados ?? '', 1, 0, 'C', true);
        $this->Cell(44, 4, $t->caja?->kms_acumulados ?? '', 1, 0, 'C', true);
        $this->Cell(44, 4, $t->diferencial?->kms_acumulados ?? '', 1, 0, 'C', true);

        return $this->salida('CT-2.pdf');
    }

    // ---------------------------------------------------------------------
    // 142 · CT-5 MOVIMIENTO VEHICULOS EN TALLER (MES)
    // ---------------------------------------------------------------------

    public function pdfCt5MovimientoMes(?string $mes = null)
    {
        [$anio, $m] = $this->anioMes($mes);
        $mesStr = sprintf('%02d', $m);
        $titulo = 'MOVIMIENTOS DE VEHICULOS EN TALLER (MODELO CT-5) ';

        $inicio = sprintf('%04d-%02d-01', $anio, $m);
        $fin = date('Y-m-t', strtotime($inicio));

        $ordenes = OrdenesTaller::with(['tractivo.motor', 'tractivo.tipoVehiculo', 'motivoEntrada'])
            ->whereIn('id_entidad', $this->entidadIds)
            ->whereBetween('fecha_ingreso', [$inicio, $fin])
            ->orderBy('fecha_ingreso')->orderBy('id')->get();

        if ($ordenes->isEmpty()) {
            $this->tecnicaNoData($titulo, $mesStr);

            return $this->salida('CT-5.pdf');
        }

        $porDia = $ordenes->groupBy(fn ($o) => $o->fecha_ingreso?->format('Y-m-d'));

        foreach ($porDia as $fecha => $grupo) {
            $this->dibujarMovimientoTaller($titulo, $mesStr, $fecha, $grupo, false);
        }

        return $this->salida('CT-5.pdf');
    }

    // ---------------------------------------------------------------------
    // 163 · CT-5 MOVIMIENTO VEHICULOS EN TALLER (FECHA)
    // ---------------------------------------------------------------------

    public function pdfCt5MovimientoFecha(?string $fecha = null)
    {
        $fecha = $fecha ?: $this->fechaOperaciones;
        $titulo = 'MOVIMIENTOS DE VEHICULOS EN TALLER (MODELO CT-5) ';

        $ordenes = OrdenesTaller::with(['tractivo.motor', 'tractivo.tipoVehiculo', 'motivoEntrada'])
            ->whereIn('id_entidad', $this->entidadIds)
            ->whereDate('fecha_ingreso', $fecha)
            ->orderBy('id')->get();

        if ($ordenes->isEmpty()) {
            $this->tecnicaNoData($titulo, $fecha);

            return $this->salida('CT-5-fecha.pdf');
        }

        $this->dibujarMovimientoTaller($titulo, $fecha, $fecha, $ordenes, true);

        return $this->salida('CT-5-fecha.pdf');
    }

    /** Render de una página del CT-5 (mes o fecha). */
    protected function dibujarMovimientoTaller(string $titulo, string $fechaCab, string $fecha, $ordenes, bool $esFecha): void
    {
        $campos = [];
        $campos[1] = ['titulo' => 'ENTRADAS', 'ancho' => 44, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos[2] = ['titulo' => 'SALIDAS', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos[3] = ['titulo' => 'PARALIZADO POR:', 'ancho' => 36, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos[4] = ['titulo' => 'ROTURA EN LINEA', 'ancho' => 27, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];

        $campos1 = [];
        $campos1[0] = ['titulo' => 'No:', 'ancho' => 11, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos1[1] = ['titulo' => 'VEHICULO', 'ancho' => 18, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos1[2] = ['titulo' => 'MARCA', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos1[3] = ['titulo' => 'TIPO', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos1[4] = ['titulo' => 'MOTIVO O CAUSA', 'ancho' => 55, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos1[5] = ['titulo' => 'DIAS ANTERIORES', 'ancho' => 27, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos1[6] = ['titulo' => 'HORA HOY', 'ancho' => 17, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos1[7] = ['titulo' => 'HORA', 'ancho' => 16, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos1[8] = ['titulo' => 'Valla', 'ancho' => 9, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos1[9] = ['titulo' => 'F.T.', 'ancho' => 9, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos1[10] = ['titulo' => 'Piezas', 'ancho' => 9, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos1[11] = ['titulo' => 'Mtto', 'ancho' => 9, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos1[12] = ['titulo' => 'HORA', 'ancho' => 27, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];

        $dia = (int) substr($fecha, 8, 2);
        $campos[0] = ['titulo' => 'DIA: '.$dia, 'ancho' => 139, 'direccion' => 'L', 'bordes' => 'LTRB', 'letra' => '8'];

        $this->inicio($titulo, $esFecha ? '' : $fechaCab);
        $this->titulos(6, 10, 30, $campos1, $campos);
        $this->firmasRevisadoAprobado(190);

        $posY = 42;
        $max = 23;
        $i = 1;

        foreach ($ordenes as $orden) {
            $notas = $this->ctAjustarNotas($orden->notas ?: ($orden->motivoEntrada?->nombre ?? ''), 30);
            foreach ($notas as $index => $linea) {
                $primera = $index === 0;
                $this->SetFont('Arial', 'U', 10);
                $this->SetXY(10, $posY);
                $this->SetFillColor(255, 255, 255);
                $borde = $index < count($notas) - 1 ? ($primera ? 'LRT' : 'LR') : (count($notas) === 1 ? 'LRTB' : 'LRB');
                $this->Cell(11, 6, $primera ? $orden->numero : '', $borde, 0, 'C', true);
                $this->SetFont('Arial', '', 10);
                $this->Cell(18, 6, $primera ? ($orden->tractivo?->codigo ?? '') : '', $borde, 0, 'C', true);
                $this->Cell(30, 6, $primera ? ($orden->tractivo?->motor?->marca ?? '') : '', $borde, 0, 'C', true);
                $this->Cell(25, 6, $primera ? substr((string) ($orden->tractivo?->tipoVehiculo?->nombre ?? ''), 0, 10) : '', $borde, 0, 'C', true);
                $this->SetFont('Arial', 'B', 8);
                $this->Cell(55, 6, $this->txt((string) $linea), $borde, 0, 'L', true);
                $this->SetFont('Arial', '', 10);
                $mismoDia = $orden->fecha_ingreso?->format('Y-m-d') === $fecha;
                $this->Cell(27, 6, $primera ? ($mismoDia ? '' : $this->tecnicaFecha($orden->fecha_ingreso)) : '', $borde, 0, 'C', true);
                $this->Cell(17, 6, $primera ? ($mismoDia ? $this->tecnicaHora($orden->hora_ingreso) : '') : '', $borde, 0, 'C', true);
                $salidaMismo = $orden->fecha_salida?->format('Y-m-d') === $fecha;
                $this->Cell(16, 6, $primera ? ($salidaMismo ? $this->tecnicaHora($orden->hora_salida) : '') : '', $borde, 0, 'C', true);

                $par = strtoupper((string) $orden->ot_paralizado);
                $marca = function ($tipo) use ($par, $primera) {
                    return $primera && $par === $tipo ? 'X' : '';
                };
                $this->Cell(9, 6, $marca('VALLA'), $borde, 0, 'C', true);
                $this->Cell(9, 6, $marca('F.T.'), $borde, 0, 'C', true);
                $this->Cell(9, 6, $marca('PIEZAS'), $borde, 0, 'C', true);
                $this->Cell(9, 6, $marca('MTTO'), $borde, 0, 'C', true);
                $this->Cell(27, 6, $primera && $mismoDia ? ($orden->ot_rotura_en_linea ?? '') : '', $borde, 0, 'C', true);

                $posY += 6;
                $i++;
                if ($i >= $max) {
                    $this->inicio($titulo, $esFecha ? '' : $fechaCab);
                    $this->titulos(6, 10, 30, $campos1, $campos);
                    $this->firmasRevisadoAprobado(190);
                    $posY = 42;
                    $i = 0;
                }
            }
        }
    }

    // ---------------------------------------------------------------------
    // 146 · CT-8 CONTROL VIDA UTIL BATERIA
    // ---------------------------------------------------------------------

    public function pdfCt8VidaUtilBateria()
    {
        $titulo = 'CONTROL DE LA VIDA UTIL DE LA BATERIA (MODELO CT-8)';
        $campos1 = [];
        $campos1[] = ['titulo' => 'VEHICULO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'];
        $campos1[] = ['titulo' => 'F/ INSTALADA', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'];
        $campos1[] = ['titulo' => 'F/ RETIRADA', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'];
        $campos1[] = ['titulo' => 'T/ TRABAJO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRBT', 'letra' => '8'];
        $campos1[] = ['titulo' => 'DURACION', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos1[] = ['titulo' => 'DESTINO', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos1[] = ['titulo' => 'OBSERVACIONES', 'ancho' => 60, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];

        $baterias = Bateria::with(['marca', 'modelo', 'tractivo'])
            ->whereIn('id_entidad', $this->entidadIds)->orderBy('folio')->get();

        $flag = false;
        foreach ($baterias as $bateria) {
            $movs = BateriasMovimiento::with(['tractivo', 'destino'])
                ->where('id_bateria', $bateria->id)
                ->orderBy('fecha_movimiento')->get();
            if ($movs->isEmpty()) {
                continue;
            }
            $flag = true;
            $campos = [];
            $campos[0] = ['titulo' => 'NO. BATERIA: '.$bateria->folio, 'ancho' => 80, 'direccion' => 'L', 'bordes' => 'LTRB', 'letra' => '8'];
            $campos[1] = ['titulo' => 'MARCA: '.($bateria->marca?->nombre ?? ''), 'ancho' => 115, 'direccion' => 'L', 'bordes' => 'LTRB', 'letra' => '8'];

            $this->inicio($titulo);
            $this->titulos(6, 10, 30, $campos1, $campos);
            $posY = 42;
            $i = 1;
            $max = 27;

            foreach ($movs as $mov) {
                $obs = $this->ctAjustarNotas($mov->observaciones, 20);
                foreach ($obs as $index => $linea) {
                    $primera = $index === 0;
                    $borde = $index < count($obs) - 1 ? ($primera ? 'LRT' : 'LR') : (count($obs) === 1 ? 'LRTB' : 'LRB');
                    $this->SetFont('Arial', '', 10);
                    $this->SetXY(10, $posY);
                    $this->SetFillColor(255, 255, 255);
                    $this->Cell(20, 6, $primera ? ($mov->destino ? '' : ($mov->tractivo?->codigo ?? '')) : '', $borde, 0, 'C', true);
                    $this->Cell(20, 6, $primera ? $this->tecnicaFecha($mov->fecha_movimiento) : '', $borde, 0, 'C', true);
                    $this->Cell(20, 6, $primera ? $this->tecnicaFecha($mov->fecha_retiro) : '', $borde, 0, 'C', true);
                    $this->Cell(20, 6, $primera ? trim($mov->tiempo_trabajo.' MESES') : '', $borde, 0, 'C', true);
                    $dias = $this->tecnicaDias($bateria->fecha_instalacion, $this->fechaOperaciones);
                    $this->Cell(25, 6, $primera && $dias !== null ? round($dias / 30).' MESES' : '', $borde, 0, 'C', true);
                    $this->Cell(30, 6, $primera ? ($mov->destino?->nombre ?? '') : '', $borde, 0, 'L', true);
                    $this->Cell(60, 6, $this->txt((string) $linea), $borde, 0, 'L', true);
                    $posY += 6;
                    $i++;
                    if ($i >= $max) {
                        $this->inicio($titulo);
                        $this->titulos(6, 10, 30, $campos1, $campos);
                        $posY = 42;
                        $i = 1;
                    }
                }
            }
        }

        if (! $flag) {
            $this->tecnicaNoData($titulo);
        }

        return $this->salida('CT-8.pdf');
    }

    // ---------------------------------------------------------------------
    // 147 · CT-3 TIEMPO DE TRABAJO DE LOS AGREGADOS INTERCAMBIADOS
    // ---------------------------------------------------------------------

    public function pdfCt3TiempoAgregados(?string $mes = null)
    {
        [$anio, $m] = $this->anioMes($mes);
        $mesStr = sprintf('%02d', $m);
        $inicio = sprintf('%04d-%02d-01', $anio, $m);
        $fin = date('Y-m-t', strtotime($inicio));
        $titulo = '(MODELO CT-3) TIEMPO DE TRABAJO DE LOS AGREGADOS INTERCAMBIADOS';

        $campos = [];
        $campos[0] = ['titulo' => 'VEHICULO', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 1, 'letra' => '10'];
        $campos[1] = ['titulo' => 'DIA', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 1, 'letra' => '10'];
        $campos[2] = ['titulo' => 'KMS', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 1, 'letra' => '10'];
        $campos[3] = ['titulo' => 'MOTIVOS', 'ancho' => 188, 'direccion' => 'C', 'bordes' => 1, 'letra' => '10'];

        $agregados = CatalogoItem::where('tipo', 'tipos_agregados')->orderBy('codigo')->get();
        $flag = true;
        $inicioPagina = true;
        $posY = 42;
        $i = 0;
        $max = 22;

        foreach ($agregados as $agregado) {
            $rows = DB::table('gastos_orden as g')
                ->join('ordenes_taller as o', 'o.id', '=', 'g.id_orden_taller')
                ->join('tractivos as t', 't.id', '=', 'o.id_tractivo')
                ->where('g.id_tipo_agregado', $agregado->id)
                ->whereBetween('o.fecha_ingreso', [$inicio, $fin])
                ->whereIn('o.id_entidad', $this->entidadIds)
                ->select('t.codigo as tractivo', 'o.fecha_salida as fsalida', 'o.ottiempo as duracion', 'g.motivo as motivo', 'g.nombre as nombre')
                ->orderBy('o.fecha_salida')->get();

            if ($rows->isEmpty()) {
                continue;
            }
            $flag = false;
            if ($inicioPagina) {
                $this->inicio($titulo, $mesStr);
                $this->titulos(6, 10, 36, $campos);
                $inicioPagina = false;
            }
            $this->SetFillColor(200, 200, 200);
            $this->SetXY(10, $posY);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(258, 6, $this->txt((string) $agregado->nombre), 1, 0, 'C', true);
            $posY += 6;
            $kms = 0;
            $cont = 0;
            foreach ($rows as $row) {
                $this->SetFillColor(255, 255, 255);
                $this->SetFont('Arial', '', 10);
                $this->SetXY(10, $posY);
                $this->Cell(25, 6, $this->txt((string) $row->tractivo), 1, 0, 'C', true);
                $this->Cell(25, 6, $this->tecnicaFecha($row->fsalida), 1, 0, 'C', true);
                $this->Cell(20, 6, round((float) $row->duracion, 2), 1, 0, 'C', true);
                $kms += (float) $row->duracion;
                $cont++;
                $this->Cell(188, 6, $this->txt((string) ($row->motivo ?: $row->nombre)), 1, 0, 'C', true);
                $posY += 6;
                $i++;
                if ($i >= $max) {
                    $this->inicio($titulo, $mesStr);
                    $this->titulos(6, 10, 36, $campos);
                    $posY = 42;
                    $i = 0;
                }
            }
            $this->SetFillColor(200, 200, 200);
            $this->SetFont('Arial', 'B', 10);
            $this->SetXY(10, $posY);
            $this->Cell(50, 6, 'PROMEDIO DE DURACION', 1, 0, 'C', true);
            $this->SetFillColor(255, 255, 255);
            $this->SetFont('Arial', '', 10);
            $this->Cell(20, 6, $cont > 0 ? round($kms / $cont, 2) : '', 1, 0, 'C', true);
            $this->SetFillColor(200, 200, 200);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(188, 6, '', 1, 0, 'C', true);
            $posY += 6;
        }

        if ($flag) {
            $this->tecnicaNoData($titulo, $mesStr);
        }

        return $this->salida('CT-3.pdf');
    }

    // ---------------------------------------------------------------------
    // 148 · CT-1 DATOS TECNICOS (EXPEDIENTE)
    // ---------------------------------------------------------------------

    public function pdfCt1Expediente($tractivo)
    {
        $t = $this->buscarTractivo($tractivo);
        if (! $t) {
            $this->tecnicaNoData('DATOS TECNICOS (MODELO CT-1)');

            return $this->salida('CT-1.pdf');
        }

        $titulo = 'DATOS TECNICOS (MODELO CT-1)';
        $this->inicio($titulo);
        $t->loadMissing(['motor', 'caja', 'diferencial', 'documentacion', 'grupo', 'tipoVehiculo', 'tipoServicio', 'colorPrimario', 'colorSecundario']);

        $posY = 28;
        $fila = function (array $cols, bool $header) use (&$posY) {
            $this->SetXY(10, $posY += 6);
            foreach ($cols as $col) {
                $w = $col[0];
                $text = $col[1] ?? '';
                $align = $col[2] ?? 'C';
                if ($header) {
                    $this->SetFont('Arial', 'B', 10);
                    $this->SetFillColor(200, 200, 200);
                } else {
                    $this->SetFont('Arial', '', 10);
                    $this->SetFillColor(255, 255, 255);
                }
                $this->Cell($w, 6, $this->txt((string) $text), 1, 0, $align, true);
            }
        };

        $doc = $t->documentacion;

        // Cabecera de dos niveles
        $this->SetXY(10, $posY += 6);
        foreach ([[20, 'NO'], [40, 'TIPO'], [40, 'MARCA'], [40, 'MODELO'], [35, 'PAIS'], [25, 'AÑO DE'], [30, 'NO '], [30, 'CHAPA']] as $c) {
            $this->SetFont('Arial', 'B', 10);
            $this->SetFillColor(200, 200, 200);
            $this->Cell($c[0], 6, $this->txt($c[1]), 'LRT', 0, 'C', true);
        }
        $this->SetXY(10, $posY += 6);
        foreach ([[20, 'VEHICULO'], [40, ''], [40, ''], [40, ''], [35, 'DE ORIGEN'], [25, 'FABRICACION'], [30, 'CIRCULACION'], [30, '']] as $c) {
            $this->SetFont('Arial', 'B', 10);
            $this->SetFillColor(200, 200, 200);
            $this->Cell($c[0], 6, $this->txt($c[1]), 'LRB', 0, 'C', true);
        }
        $fila([
            [20, $t->codigo], [40, $t->tipoVehiculo?->nombre ?? ''],
            [40, $t->motor?->marca ?? ''], [40, $t->motor?->modelo ?? ''],
            [35, ''], [25, $t->anno], [30, $doc?->circulacion ?? ''], [30, $t->placa],
        ], false);

        $fila([[50, 'CHASIS'], [90, 'SERVICIO QUE PRESTA'], [65, 'FECHA ULTIMA RECONSTRUCCION'], [28, 'DIST /EJES (I/T)'], [27, '# EJES']], true);
        $fila([[50, $doc?->nro_chasis ?? ''], [90, $t->tipoServicio?->nombre ?? ''], [65, $this->tecnicaFecha($doc?->f_reconstruccion)], [28, ''], [27, '']], false);

        $fila([[50, 'ACUMULADOR'], [90, 'NEUMATICOS'], [65, 'COMBUSTIBLE'], [55, 'CAMA VEHICULO/ARRASTRE']], true);
        $fila([[35, 'CANTIDAD'], [15, ''], [30, 'DELANTERO'], [30, 'TRASERO'], [30, 'RESPUESTA'], [65, $t->tipoCombustible?->nombre ?? ''], [18, 'LARGO'], [9, ''], [18, 'ALTURA'], [10, '']], false);
        $fila([[35, 'VOLTAJE'], [15, ''], [30, ''], [30, ''], [30, ''], [55, 'CAPACIDAD TANQUE LITROS'], [10, $t->cap_deposito], [18, 'ANCHO'], [9, ''], [18, 'm3'], [10, '']], false);
        $fila([[35, 'AMPERAJE'], [15, ''], [10, 'MED'], [20, ''], [10, 'MED'], [20, ''], [10, 'MED'], [20, ''], [120, '']], false);

        $fila([[40, 'NO RESOLUCION'], [25, $doc?->nro_resolucion ?? ''], [35, 'VIN'], [40, $doc?->vin ?? ''], [30, 'COLOR'], [40, $t->colorPrimario?->nombre ?? ''], [50, 'NO CARROCERIA O CABINA']], true);
        $fila([[40, 'PATRIMONIO ESTATAL'], [25, ''], [35, ''], [40, ''], [30, ''], [40, $t->colorSecundario?->nombre ?? ''], [50, $doc?->nro_carroceria ?? '']], false);

        $fila([[30, 'TARA TN'], [20, $t->tara], [30, 'CAPACIDAD TN'], [20, $t->capacidad_toneladas], [45, 'SISTEMA HIDRAULICO'], [50, 'TIPO LUBRICANTE'], [65, $t->lubricanteHidraulico?->nombre ?? '']], true);
        $fila([[30, 'FECHA CRT'], [20, $this->tecnicaFecha($doc?->femision_ficav)], [30, 'VENCE CRT'], [20, $this->tecnicaFecha($doc?->fvence_ficav)], [45, ''], [50, 'CAPACIDAD DEPOSITO'], [65, $t->cap_hidraulico]], true);

        $fila([[260, 'ESTADO TECNICO EN EL MOMENTO DE TOMAR EL INVENTARIO']], true);
        $fila([[260, 'AGRAGADOS MAYORES']], true);
        $fila([[87, 'MOTOR'], [86, 'DIFERENCIAL'], [87, 'CAJA DE VELOCIDAD']], true);
        $fila([[18, 'FECHA'], [23, '# SERIE'], [23, 'MARCA'], [23, 'MODELO'], [17, 'FECHA'], [23, '# SERIE'], [23, 'MARCA'], [23, 'MODELO'], [18, 'FECHA'], [23, '# SERIE'], [23, 'MARCA'], [23, 'MODELO']], true);
        $fila([
            [18, $this->tecnicaFecha($t->motor?->fecha_instalacion)], [23, $t->motor?->numero_serie ?? ''], [23, $t->motor?->marca ?? ''], [23, $t->motor?->modelo ?? ''],
            [17, $this->tecnicaFecha($t->diferencial?->fecha_instalacion)], [23, $t->diferencial?->numero_serie ?? ''], [23, $t->diferencial?->marca ?? ''], [23, $t->diferencial?->modelo ?? ''],
            [18, $this->tecnicaFecha($t->caja?->fecha_instalacion)], [23, $t->caja?->numero_serie ?? ''], [23, $t->caja?->marca ?? ''], [23, $t->caja?->modelo ?? ''],
        ], false);

        $fila([[41, 'CPL'], [46, $t->motor?->cpl ?? ''], [173, '']], true);
        $fila([[41, 'NO REGISTRO'], [46, $doc?->nro_registro ?? ''], [173, '']], true);
        $fila([[41, 'TIPO DE LUBRICANTE'], [46, $t->motor?->lubricante?->nombre ?? ''], [40, 'TIPO DE LUBRICANTE'], [46, $t->diferencial?->lubricante?->nombre ?? ''], [41, 'TIPO DE LUBRICANTE'], [46, $t->caja?->lubricante?->nombre ?? '']], true);
        $fila([[41, 'CAPACIDAD CARTER'], [46, $t->motor?->capacidad_carter], [40, 'CAPACIDAD CARTER'], [46, $t->caja?->capacidad_carter], [41, 'CAPACIDAD CARTER'], [46, $t->diferencial?->capacidad_carter]], true);

        return $this->salida('CT-1.pdf');
    }

    // ---------------------------------------------------------------------
    // 149 · CT-7 CONTROL DE LUBRICANTE
    // ---------------------------------------------------------------------

    public function pdfCt7ControlLubricantes(?string $mes = null)
    {
        [$anio, $m] = $this->anioMes($mes);
        $mesStr = sprintf('%02d', $m);
        $titulo = 'CONTROL DE LUBRICANTE (MODELO CT-7)';
        $dias = (int) date('t', strtotime(sprintf('%04d-%02d-01', $anio, $m)));

        $campos = [];
        $campos[0] = ['titulo' => 'DIA', 'ancho' => 110, 'direccion' => 'L', 'bordes' => 1, 'letra' => '8'];
        $campos[1] = ['titulo' => 'GRASAS', 'ancho' => 40, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos[2] = ['titulo' => 'LIQUIDOS', 'ancho' => 40, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];

        $campos1 = [];
        $campos1[0] = ['titulo' => 'VEHICULO', 'ancho' => 22, 'direccion' => 'C', 'bordes' => '1', 'letra' => '8'];
        $campos1[1] = ['titulo' => 'MOTOR', 'ancho' => 22, 'direccion' => 'C', 'bordes' => '1', 'letra' => '8'];
        $campos1[2] = ['titulo' => 'TRANSMISION', 'ancho' => 22, 'direccion' => 'C', 'bordes' => '1', 'letra' => '8'];
        $campos1[3] = ['titulo' => 'DIRECCION', 'ancho' => 22, 'direccion' => 'C', 'bordes' => '1', 'letra' => '8'];
        $campos1[4] = ['titulo' => 'HIDRAULICO', 'ancho' => 22, 'direccion' => 'C', 'bordes' => '1', 'letra' => '8'];
        $campos1[5] = ['titulo' => 'ROLLETE', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos1[6] = ['titulo' => 'COPILLAS', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos1[7] = ['titulo' => 'LIQ FRENO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];
        $campos1[8] = ['titulo' => 'AGUA REF', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTRB', 'letra' => '8'];

        $flag = false;
        $resumen = [];
        for ($dia = 1; $dia <= $dias; $dia++) {
            $resumen[$dia] = ['motor' => 0, 'transmision' => 0, 'direccion' => 0, 'hidraulico' => 0, 'grasaR' => 0, 'grasaC' => 0, 'liqF' => 0, 'agua' => 0];
        }

        for ($dia = 1; $dia <= $dias; $dia++) {
            $fecha = sprintf('%04d-%02d-%02d', $anio, $m, $dia);
            $data = ControlLubricante::with('tractivo')
                ->whereIn('id_entidad', $this->entidadIds)
                ->whereDate('fecha_cambio', $fecha)
                ->orderBy('id')->get();
            if ($data->isEmpty()) {
                continue;
            }
            $flag = true;
            $campos[0]['titulo'] = 'DIA:'.$dia;
            $this->inicio($titulo, $mesStr);
            $this->titulos(6, 10, 30, $campos1, $campos);
            $posY = 42;
            $i = 1;
            $max = 33;
            $tot = ['motor' => 0, 'transmision' => 0, 'direccion' => 0, 'hidraulico' => 0, 'grasaR' => 0, 'grasaC' => 0, 'liqF' => 0, 'agua' => 0];
            foreach ($data as $d) {
                $this->SetFont('Arial', '', 10);
                $this->SetXY(10, $posY);
                $this->SetFillColor(255, 255, 255);
                $this->Cell(22, 6, $this->txt((string) ($d->tractivo?->codigo ?? '')), 1, 0, 'C', true);
                $this->Cell(22, 6, $this->cambiarVariable($d->litros_motor), 1, 0, 'C', true);
                $this->Cell(22, 6, $this->cambiarVariable($d->litros_transmision), 1, 0, 'C', true);
                $this->Cell(22, 6, $this->cambiarVariable($d->litros_direccion), 1, 0, 'C', true);
                $this->Cell(22, 6, $this->cambiarVariable($d->litros_hidraulico), 1, 0, 'C', true);
                $this->Cell(20, 6, $this->cambiarVariable($d->grasa_rollete), 1, 0, 'C', true);
                $this->Cell(20, 6, $this->cambiarVariable($d->grasa_copillas), 1, 0, 'C', true);
                $this->Cell(20, 6, $this->cambiarVariable($d->liquido_freno), 1, 0, 'C', true);
                $this->Cell(20, 6, $this->cambiarVariable($d->agua_refrigerada), 1, 0, 'C', true);
                $tot['motor'] += (float) $d->litros_motor;
                $tot['transmision'] += (float) $d->litros_transmision;
                $tot['direccion'] += (float) $d->litros_direccion;
                $tot['hidraulico'] += (float) $d->litros_hidraulico;
                $tot['grasaR'] += (float) $d->grasa_rollete;
                $tot['grasaC'] += (float) $d->grasa_copillas;
                $tot['liqF'] += (float) $d->liquido_freno;
                $tot['agua'] += (float) $d->agua_refrigerada;
                $posY += 6;
                $i++;
                if ($i >= $max) {
                    $this->inicio($titulo, $mesStr);
                    $this->titulos(6, 10, 30, $campos1, $campos);
                    $posY = 42;
                    $i = 1;
                }
            }
            $this->SetFont('Arial', 'B', 10);
            $this->SetXY(10, $posY);
            $this->SetFillColor(200, 200, 200);
            $this->Cell(22, 6, 'TOTALES', 1, 0, 'C', true);
            foreach (['motor', 'transmision', 'direccion', 'hidraulico', 'grasaR', 'grasaC', 'liqF', 'agua'] as $k) {
                $w = in_array($k, ['grasaR', 'grasaC', 'liqF', 'agua'], true) ? 20 : 22;
                $this->Cell($w, 6, $this->cambiarVariable($tot[$k]), 1, 0, 'C', true);
            }
            foreach ($resumen[$dia] as $k => $v) {
                $resumen[$dia][$k] = $v + $tot[$k];
            }
        }

        if (! $flag) {
            $this->tecnicaNoData($titulo, $mesStr);

            return $this->salida('CT-7.pdf');
        }

        // Página de resumen diario
        $campos[0]['titulo'] = 'TOTALES';
        $campos1[0]['titulo'] = 'DIA';
        $this->inicio($titulo, $mesStr);
        $this->titulos(6, 10, 30, $campos1, $campos);
        $posY = 42;
        $totales = ['motor' => 0, 'transmision' => 0, 'direccion' => 0, 'hidraulico' => 0, 'grasaR' => 0, 'grasaC' => 0, 'liqF' => 0, 'agua' => 0];
        for ($dia = 1; $dia <= $dias; $dia++) {
            $r = $resumen[$dia];
            if (array_sum($r) == 0) {
                continue;
            }
            $this->SetFont('Arial', '', 10);
            $this->SetXY(10, $posY);
            $this->SetFillColor(255, 255, 255);
            $this->Cell(22, 6, $dia, 1, 0, 'C', true);
            foreach (['motor', 'transmision', 'direccion', 'hidraulico', 'grasaR', 'grasaC', 'liqF', 'agua'] as $k) {
                $w = in_array($k, ['grasaR', 'grasaC', 'liqF', 'agua'], true) ? 20 : 22;
                $this->Cell($w, 6, $this->cambiarVariable($r[$k]), 1, 0, 'C', true);
                $totales[$k] += $r[$k];
            }
            $posY += 6;
        }
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(10, $posY);
        $this->SetFillColor(200, 200, 200);
        $this->Cell(22, 6, 'TOTALES', 1, 0, 'C', true);
        foreach (['motor', 'transmision', 'direccion', 'hidraulico', 'grasaR', 'grasaC', 'liqF', 'agua'] as $k) {
            $w = in_array($k, ['grasaR', 'grasaC', 'liqF', 'agua'], true) ? 20 : 22;
            $this->Cell($w, 6, $this->cambiarVariable($totales[$k]), 1, 0, 'C', true);
        }

        return $this->salida('CT-7.pdf');
    }

    // ---------------------------------------------------------------------
    // 159 · CT-6 ANALISIS DE LA VIDA DE LOS MOTORES
    // ---------------------------------------------------------------------

    public function pdfCt6AnalisisMotores($motor)
    {
        $m = null;
        if (is_numeric($motor)) {
            $m = Motore::find((int) $motor);
        }
        if (! $m && $motor) {
            $m = Motore::where('codigo', $motor)->first();
        }
        if (! $m) {
            $this->tecnicaNoData('ANALISIS DE LA VIDA DE LOS MOTORES (MODELO CT-6)');

            return $this->salida('CT-6.pdf');
        }

        $m->loadMissing('tractivo');
        $titulo = 'ANALISIS DE LA VIDA DE LOS MOTORES (MODELO CT-6)';
        $campos = [];
        $campos[] = ['titulo' => 'VEHICULO: '.($m->tractivo?->codigo ?? ''), 'ancho' => 45, 'direccion' => 'L', 'bordes' => 'LTRB', 'letra' => '10'];
        $campos[] = ['titulo' => '# MOTOR: '.($m->numero_serie ?? ''), 'ancho' => 45, 'direccion' => 'L', 'bordes' => 'LTRB', 'letra' => '10'];
        $campos[] = ['titulo' => 'MARCA: '.($m->marca ?? ''), 'ancho' => 45, 'direccion' => 'L', 'bordes' => 'LTRB', 'letra' => '10'];
        $campos[] = ['titulo' => 'MODELO: '.($m->modelo ?? ''), 'ancho' => 45, 'direccion' => 'L', 'bordes' => 'LTRB', 'letra' => '10'];
        $campos[] = ['titulo' => 'KMS: '.($m->kms_acumulados ?? ''), 'ancho' => 40, 'direccion' => 'L', 'bordes' => 'LTRB', 'letra' => '10'];
        $campos[] = ['titulo' => 'FECHA: '.$this->tecnicaFecha($m->fecha_instalacion), 'ancho' => 40, 'direccion' => 'L', 'bordes' => 'LTRB', 'letra' => '10'];

        $this->inicio($titulo);
        $this->titulos(6, 10, 30, $campos);

        $posY = 36;
        $max = 22;
        $i = 0;

        // Cambio de agregados
        $this->SetFillColor(200, 200, 200);
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(10, $posY);
        $this->Cell(260, 6, 'CAMBIO DE AGREGADOS', 1, 0, 'C', true);
        $posY += 6;
        $this->SetXY(10, $posY);
        $this->Cell(25, 6, 'FECHA', 1, 0, 'C', true);
        $this->Cell(50, 6, 'NOMBRE AGREGADO', 1, 0, 'C', true);
        $this->Cell(30, 6, 'DURACION', 1, 0, 'C', true);
        $this->Cell(155, 6, 'OBSERVACIONES', 1, 0, 'C', true);
        $posY += 6;

        $cambios = GastosOrden::with('tipoAgregado')
            ->where('id_motor', $m->id)->orderBy('created_at')->get();
        foreach ($cambios as $g) {
            $this->SetFillColor(255, 255, 255);
            $this->SetFont('Arial', '', 10);
            $this->SetXY(10, $posY);
            $this->Cell(25, 6, $this->tecnicaFecha($g->created_at), 1, 0, 'L', true);
            $this->Cell(50, 6, $this->txt((string) ($g->tipoAgregado?->nombre ?? $g->nombre)), 1, 0, 'L', true);
            $this->Cell(30, 6, '', 1, 0, 'L', true);
            $this->Cell(155, 6, $this->txt((string) $g->motivo), 1, 0, 'L', true);
            $posY += 6;
            $i++;
            if ($i >= $max) {
                $this->inicio($titulo);
                $this->titulos(6, 10, 30, $campos);
                $posY = 36;
                $i = 0;
            }
        }

        // Pruebas de funcionamiento
        $pruebas = OrdenesTaller::where('id_motor', $m->id)->whereNotNull('pl_cons_comb')->orderBy('fecha_ingreso')->get();
        if ($pruebas->isNotEmpty()) {
            $this->SetFillColor(200, 200, 200);
            $this->SetFont('Arial', 'B', 10);
            $this->SetXY(10, $posY);
            $this->Cell(260, 6, 'RESULTADOS DE LAS PRUEBAS DE FUNCIONAMIENTO DEL MOTOR', 1, 0, 'C', true);
            $posY += 6;
            $this->SetXY(10, $posY);
            $this->Cell(40, 6, '', 1, 0, 'C', true);
            $this->Cell(22, 6, 'CONSUMO', 1, 0, 'C', true);
            $this->Cell(50, 6, 'ACEITES', 1, 0, 'C', true);
            $this->Cell(64, 6, 'COMPRESION', 1, 0, 'C', true);
            $this->Cell(30, 6, 'TEMPERATURA', 1, 0, 'C', true);
            $this->Cell(54, 6, '', 1, 0, 'C', true);
            $posY += 6;
            $this->SetXY(10, $posY);
            foreach ([[20, 'KMS'], [20, 'FECHA'], [22, 'COMB(LTS)'], [20, 'CONS(LTS)'], [30, 'PRESION'], [64, 'CILINDROS'], [15, 'AGUA'], [15, 'ACEITE'], [54, 'OBSERVACIONES']] as $c) {
                $this->Cell($c[0], 6, $c[1], 1, 0, 'C', true);
            }
            $posY += 6;
            foreach ($pruebas as $p) {
                $obs = $this->ctAjustarNotas($p->pl_observacion, 24);
                foreach ($obs as $index => $linea) {
                    $primera = $index === 0;
                    $borde = $index < count($obs) - 1 ? ($primera ? 'LRT' : 'LR') : (count($obs) === 1 ? 'LRTB' : 'LRB');
                    $this->SetFont('Arial', '', 10);
                    $this->SetXY(10, $posY);
                    $this->SetFillColor(255, 255, 255);
                    $this->Cell(20, 6, $primera ? $p->cant_clasificacion : '', $borde, 0, 'C', true);
                    $this->Cell(20, 6, $primera ? $this->tecnicaFecha($p->fecha_ingreso) : '', $borde, 0, 'C', true);
                    $this->Cell(22, 6, $primera ? $p->pl_cons_comb : '', $borde, 0, 'C', true);
                    $this->Cell(20, 6, $primera ? $p->pl_cons_aceite : '', $borde, 0, 'C', true);
                    $this->Cell(15, 6, $primera ? $p->pl_presion_aceite_baja : '', $borde, 0, 'C', true);
                    $this->Cell(15, 6, $primera ? $p->pl_presion_aceite_alta : '', $borde, 0, 'C', true);
                    $this->Cell(8, 6, $primera ? $p->pl_cil1 : '', $borde, 0, 'L', true);
                    $this->Cell(8, 6, $primera ? $p->pl_cil2 : '', $borde, 0, 'L', true);
                    $this->Cell(8, 6, $primera ? $p->pl_cil3 : '', $borde, 0, 'L', true);
                    $this->Cell(8, 6, $primera ? $p->pl_cil4 : '', $borde, 0, 'L', true);
                    $this->Cell(8, 6, $primera ? $p->pl_cil5 : '', $borde, 0, 'L', true);
                    $this->Cell(8, 6, $primera ? $p->pl_cil6 : '', $borde, 0, 'L', true);
                    $this->Cell(8, 6, $primera ? $p->pl_cil7 : '', $borde, 0, 'L', true);
                    $this->Cell(8, 6, $primera ? $p->pl_cil8 : '', $borde, 0, 'L', true);
                    $this->Cell(15, 6, $primera ? $p->pl_temp_agua : '', $borde, 0, 'L', true);
                    $this->Cell(15, 6, $primera ? $p->pl_temp_aceite : '', $borde, 0, 'L', true);
                    $this->Cell(54, 6, $this->txt((string) $linea), $borde, 0, 'L', true);
                    $posY += 6;
                    $i++;
                    if ($i >= $max) {
                        $this->inicio($titulo);
                        $this->titulos(6, 10, 30, $campos);
                        $posY = 36;
                        $i = 0;
                    }
                }
            }
        }

        // Reparaciones anteriores
        $reparaciones = OrdenesTaller::with(['operaciones.tipoOperacion'])
            ->where('id_motor', $m->id)->orderBy('fecha_ingreso')->get();
        if ($reparaciones->isNotEmpty()) {
            $this->SetFillColor(200, 200, 200);
            $this->SetFont('Arial', 'B', 10);
            $this->SetXY(10, $posY += 6);
            $this->Cell(260, 6, 'REPARACIONES ANTERIORES', 1, 0, 'C', true);
            $posY += 6;
            $this->SetXY(10, $posY);
            $this->Cell(40, 6, 'FECHA', 1, 0, 'C', true);
            $this->Cell(22, 6, 'KMS', 1, 0, 'C', true);
            $this->Cell(198, 6, 'TIPO OPERACION', 1, 0, 'C', true);
            $posY += 6;
            foreach ($reparaciones as $r) {
                $tipos = $r->operaciones->map(fn ($o) => $o->tipoOperacion?->nombre ?? '')->filter()->implode(', ');
                $this->SetFillColor(255, 255, 255);
                $this->SetFont('Arial', '', 10);
                $this->SetXY(10, $posY);
                $this->Cell(40, 6, $this->tecnicaFecha($r->fecha_ingreso), 1, 0, 'C', true);
                $this->Cell(22, 6, $r->cant_clasificacion, 1, 0, 'C', true);
                $this->Cell(198, 6, $this->txt($tipos), 1, 0, 'C', true);
                $posY += 6;
                $i++;
                if ($i >= $max) {
                    $this->inicio($titulo);
                    $this->titulos(6, 10, 30, $campos);
                    $posY = 36;
                    $i = 0;
                }
            }
        }

        return $this->salida('CT-6.pdf');
    }

    // ---------------------------------------------------------------------
    // 160 · CT-4 REPORTE DE REPARACION Y MANTENIMIENTO
    // ---------------------------------------------------------------------

    public function pdfCt4ReparacionMantenimiento($orden)
    {
        $o = null;
        if (is_numeric($orden)) {
            $o = OrdenesTaller::find((int) $orden);
        }
        if (! $o && $orden) {
            $o = OrdenesTaller::where('numero', $orden)->first();
        }
        if (! $o) {
            $this->tecnicaNoData('REPORTE DE REPARACION Y MANTENIMIENTO (MODELO CT-4)');

            return $this->salida('CT-4.pdf');
        }

        $o->loadMissing(['tractivo.motor', 'tractivo.tipoVehiculo', 'clasificacion', 'taller', 'gastos.tipoAgregado', 'operaciones.tipoOperacion', 'operaciones.operario', 'motor']);
        $titulo = 'REPORTE DE REPARACION Y MANTENIMIENTO (MODELO CT-4)';
        $this->inicio($titulo);
        $posY = 30;
        $max = 234;

        $this->SetFillColor(200, 200, 200);
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(10, $posY += 6);
        $this->Cell(52, 6, 'ORDEN: '.$o->numero, 1, 0, 'C', true);
        $this->Cell(45, 6, 'ENTRADA', 1, 0, 'C', true);
        $this->Cell(45, 6, 'SALIDA', 1, 0, 'C', true);
        $this->Cell(50, 6, 'TIEMPO', 1, 0, 'C', true);

        $this->SetXY(10, $posY += 6);
        foreach ([[20, 'VEHICULO'], [32, 'MARCA'], [25, 'FECHA'], [20, 'HORA'], [25, 'EN TALLER'], [25, 'REPARACION']] as $c) {
            $this->Cell($c[0], 6, $c[1], 1, 0, 'C', true);
        }
        $tiempoRep = (float) $o->operaciones->sum('tiempo');
        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', '', 10);
        $this->SetXY(10, $posY += 6);
        $this->Cell(20, 6, $this->txt((string) ($o->tractivo?->codigo ?? '')), 1, 0, 'C', true);
        $this->Cell(32, 6, $this->txt((string) ($o->tractivo?->motor?->marca ?? '')), 1, 0, 'C', true);
        $this->Cell(25, 6, $this->tecnicaFecha($o->fecha_ingreso), 1, 0, 'C', true);
        $this->Cell(20, 6, $this->tecnicaHora($o->hora_ingreso), 1, 0, 'C', true);
        $this->Cell(25, 6, $this->tecnicaFecha($o->fecha_salida), 1, 0, 'C', true);
        $this->Cell(20, 6, $this->tecnicaHora($o->hora_salida), 1, 0, 'C', true);
        $this->Cell(25, 6, $o->ottiempo, 1, 0, 'C', true);
        $this->Cell(25, 6, $tiempoRep, 1, 0, 'C', true);

        $this->SetFillColor(200, 200, 200);
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(10, $posY += 6);
        $this->Cell(30, 6, 'KMS ENTRADA', 1, 0, 'C', true);
        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', '', 10);
        $this->Cell(22, 6, $o->cant_clasificacion, 1, 0, 'C', true);
        $this->SetFillColor(200, 200, 200);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(30, 6, 'COMPLEJIDAD', 1, 0, 'C', true);
        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', '', 10);
        $this->Cell(25, 6, $this->txt((string) ($o->clasificacion?->nombre ?? '')), 1, 0, 'C', true);
        $this->SetFillColor(200, 200, 200);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(35, 6, 'TALLER EXTERIOR', 1, 0, 'C', true);
        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', '', 9);
        $this->Cell(50, 6, $this->txt(substr((string) ($o->taller?->nombre ?? ''), 0, 22)), 1, 0, 'L', true);

        $this->SetFillColor(200, 200, 200);
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(10, $posY += 6);
        $this->Cell(192, 6, 'OBSERVACIONES', 1, 0, 'C', true);
        $notas = $this->ctAjustarNotas($o->notas ?: $o->observaciones, 85);
        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', '', 10);
        foreach ($notas as $index => $linea) {
            $borde = $index < count($notas) - 1 ? ($index === 0 ? 'LRT' : 'LR') : (count($notas) === 1 ? 'LRTB' : 'LRB');
            $this->SetXY(10, $posY += 6);
            $this->Cell(192, 6, $this->txt((string) $linea), $borde, 0, 'L', true);
        }

        if ($o->pl_cons_comb) {
            $this->SetFillColor(200, 200, 200);
            $this->SetFont('Arial', 'B', 9);
            $this->SetXY(10, $posY += 6);
            $this->Cell(192, 6, 'RESULTADO DE LA PRUEBA DE FUNCIONAMIENTO DEL MOTOR', 1, 0, 'C', true);
            $this->SetXY(10, $posY += 6);
            $this->Cell(36, 6, '', 1, 0, 'C', true);
            $this->Cell(18, 6, 'CONSUMO', 1, 0, 'C', true);
            $this->Cell(44, 6, 'ACEITES', 1, 0, 'C', true);
            $this->Cell(64, 6, 'COMPRESION', 1, 0, 'C', true);
            $this->Cell(30, 6, 'TEMPERATURA', 1, 0, 'C', true);
            $this->SetXY(10, $posY += 6);
            foreach ([[18, 'KMS'], [18, 'FECHA'], [18, 'COMB(LTS)'], [19, 'CONS(LTS)'], [13, 'BAJA'], [12, 'ALTA'], [8, '1'], [8, '2'], [8, '3'], [8, '4'], [8, '5'], [8, '6'], [8, '7'], [8, '8'], [15, 'AGUA'], [15, 'ACEITE']] as $c) {
                $this->Cell($c[0], 6, $c[1], 1, 0, 'C', true);
            }
            $this->SetFillColor(255, 255, 255);
            $this->SetFont('Arial', '', 9);
            $this->SetXY(10, $posY += 6);
            $this->Cell(18, 6, $o->cant_clasificacion, 1, 0, 'C', true);
            $this->Cell(18, 6, $this->tecnicaFecha($o->fecha_ingreso), 1, 0, 'C', true);
            $this->Cell(18, 6, $o->pl_cons_comb, 1, 0, 'C', true);
            $this->Cell(19, 6, $o->pl_cons_aceite, 1, 0, 'C', true);
            $this->Cell(13, 6, $o->pl_presion_aceite_baja, 1, 0, 'C', true);
            $this->Cell(12, 6, $o->pl_presion_aceite_alta, 1, 0, 'C', true);
            $this->Cell(8, 6, $o->pl_cil1, 1, 0, 'L', true);
            $this->Cell(8, 6, $o->pl_cil2, 1, 0, 'L', true);
            $this->Cell(8, 6, $o->pl_cil3, 1, 0, 'L', true);
            $this->Cell(8, 6, $o->pl_cil4, 1, 0, 'L', true);
            $this->Cell(8, 6, $o->pl_cil5, 1, 0, 'L', true);
            $this->Cell(8, 6, $o->pl_cil6, 1, 0, 'L', true);
            $this->Cell(8, 6, $o->pl_cil7, 1, 0, 'L', true);
            $this->Cell(8, 6, $o->pl_cil8, 1, 0, 'L', true);
            $this->Cell(15, 6, $o->pl_temp_agua, 1, 0, 'L', true);
            $this->Cell(15, 6, $o->pl_temp_aceite, 1, 0, 'L', true);
            $this->SetFillColor(200, 200, 200);
            $this->SetFont('Arial', 'B', 10);
            $this->SetXY(10, $posY += 6);
            $this->Cell(192, 6, 'OBSERVACIONES DE LA PRUEBA', 1, 0, 'C', true);
            $obs = $this->ctAjustarNotas($o->pl_observacion, 90);
            $this->SetFillColor(255, 255, 255);
            $this->SetFont('Arial', '', 10);
            foreach ($obs as $index => $linea) {
                $borde = $index < count($obs) - 1 ? ($index === 0 ? 'LRT' : 'LR') : (count($obs) === 1 ? 'LRTB' : 'LRB');
                $this->SetXY(10, $posY += 6);
                $this->Cell(192, 6, $this->txt((string) $linea), $borde, 0, 'L', true);
            }
        }

        $this->SetFillColor(200, 200, 200);
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(10, $posY += 6);
        $this->Cell(96, 6, 'REPORTADO POR', 1, 0, 'C', true);
        $this->Cell(96, 6, 'CONFECCIONADO POR', 1, 0, 'C', true);
        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', '', 10);
        $this->SetXY(10, $posY += 6);
        $this->Cell(96, 6, $this->txt($this->bolsaNombre($o->id_user)), 1, 0, 'C', true);
        $this->Cell(96, 6, $this->txt($this->bolsaNombre($o->id_confeccionado)), 1, 0, 'C', true);

        if ($o->gastos->isNotEmpty()) {
            $this->SetFillColor(200, 200, 200);
            $this->SetFont('Arial', 'B', 10);
            $this->SetXY(10, $posY += 6);
            $this->Cell(192, 6, 'GASTO DE PIEZAS Y MATERIALES', 1, 0, 'C', true);
            $this->SetXY(10, $posY += 6);
            $this->Cell(15, 6, 'VALE', 1, 0, 'C', true);
            $this->Cell(15, 6, 'COD', 1, 0, 'C', true);
            $this->Cell(147, 6, 'DESCRIPCION', 1, 0, 'C', true);
            $this->Cell(15, 6, 'CANT', 1, 0, 'C', true);
            $this->SetFillColor(255, 255, 255);
            $this->SetFont('Arial', '', 10);
            foreach ($o->gastos as $g) {
                $this->SetXY(10, $posY += 6);
                $this->Cell(15, 6, $g->vale, 1, 0, 'C', true);
                $this->Cell(15, 6, $g->codigo_pieza, 1, 0, 'C', true);
                $this->Cell(147, 6, $this->txt(trim(($g->tipoAgregado?->nombre ?? '').' - '.$g->nombre, ' -')), 1, 0, 'C', true);
                $this->Cell(15, 6, $g->cantidad, 1, 0, 'C', true);
                if ($posY >= $max) {
                    $this->inicio($titulo);
                    $posY = 30;
                }
            }
        }

        if ($o->operaciones->isNotEmpty()) {
            $this->SetFillColor(200, 200, 200);
            $this->SetFont('Arial', 'B', 10);
            $this->SetXY(10, $posY += 6);
            $this->Cell(192, 6, 'OPERACIONES REALIZADAS', 1, 0, 'C', true);
            $this->SetXY(10, $posY += 6);
            $this->Cell(30, 6, 'COMIENZA', 1, 0, 'C', true);
            $this->Cell(30, 6, 'TERMINA', 1, 0, 'C', true);
            $this->Cell(20, 6, 'DURACION', 1, 0, 'C', true);
            $this->Cell(56, 6, 'DESCRIPCION', 1, 0, 'C', true);
            $this->Cell(56, 6, 'OPERARIO', 1, 0, 'C', true);
            $this->SetFillColor(255, 255, 255);
            $this->SetFont('Arial', '', 9);
            foreach ($o->operaciones as $op) {
                $desc = $this->ctAjustarNotas($op->tipoOperacion?->nombre, 30);
                foreach ($desc as $index => $linea) {
                    $primera = $index === 0;
                    $borde = $index < count($desc) - 1 ? ($primera ? 'LRT' : 'LR') : (count($desc) === 1 ? 'LRTB' : 'LRB');
                    $this->SetXY(10, $posY += 6);
                    $this->Cell(30, 6, $primera ? ($this->tecnicaFecha($op->fecha_inicio).' '.$this->tecnicaHora($op->hora_inicio)) : '', $borde, 0, 'C', true);
                    $this->Cell(30, 6, $primera ? ($this->tecnicaFecha($op->fecha_final).' '.$this->tecnicaHora($op->hora_final)) : '', $borde, 0, 'C', true);
                    $this->Cell(20, 6, $primera ? $op->tiempo : '', $borde, 0, 'C', true);
                    $this->Cell(56, 6, $this->txt((string) $linea), $borde, 0, 'L', true);
                    $this->Cell(56, 6, $primera ? $this->txt($op->operario?->nombre.' '.$op->operario?->apellidos) : '', $borde, 0, 'C', true);
                    if ($posY >= $max) {
                        $this->inicio($titulo);
                        $posY = 30;
                    }
                }
            }
        }

        return $this->salida('CT-4.pdf');
    }

    // ---------------------------------------------------------------------
    // 343 · DATOS GENERALES DEL PARQUE AUTOMOTOR
    // ---------------------------------------------------------------------

    public function pdfDatosGeneralesParque()
    {
        $titulo = 'PARQUE TRACTIVO ';
        $campos = [];
        $campos[] = ['titulo' => '# INV', 'ancho' => 15, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'MARCA', 'ancho' => 32, 'direccion' => 'L'];
        $campos[] = ['titulo' => 'MODELO', 'ancho' => 26, 'direccion' => 'L'];
        $campos[] = ['titulo' => 'TIPO', 'ancho' => 26, 'direccion' => 'L'];
        $campos[] = ['titulo' => 'VIN', 'ancho' => 35, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'CHAPA', 'ancho' => 20, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'CARROCERIA', 'ancho' => 30, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'CHASIS', 'ancho' => 24, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'CAP', 'ancho' => 15, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'INDICE', 'ancho' => 20, 'direccion' => 'C'];

        $data = Tractivo::with(['motor', 'documentacion', 'tipoVehiculo', 'grupo'])
            ->whereIn('id_entidad', $this->entidadIds)
            ->orderBy('id_grupo')->orderBy('codigo')->get();

        if ($data->isEmpty()) {
            $this->tecnicaNoData($titulo);

            return $this->salida('Parque.pdf');
        }

        $porGrupo = $data->groupBy('id_grupo');
        foreach ($porGrupo as $grupoId => $tractivos) {
            $nombreGrupo = $this->catalogoNombre($grupoId) ?: ($tractivos->first()->grupo?->nombre ?? '');
            $tituloGrupo = 'PARQUE TRACTIVO DEL GRUPO '.$nombreGrupo;
            $this->inicio($tituloGrupo);
            $this->titulos(10, 15, 35, $campos);
            $posY = 45;
            $i = 1;
            $max = 20;
            $linea = 7;
            foreach ($tractivos as $arr) {
                $this->SetFillColor(255, 255, 255);
                $this->SetFont('Arial', '', 10);
                $this->SetXY(15, $posY);
                $this->Cell(15, $linea, $this->txt((string) $arr->codigo), 1, 0, 'L', true);
                $this->Cell(32, $linea, $this->txt((string) ($arr->motor?->marca ?? '')), 1, 0, 'L', true);
                $this->Cell(26, $linea, substr($this->txt((string) ($arr->motor?->modelo ?? '')), 0, 10), 1, 0, 'L', true);
                $this->Cell(26, $linea, substr($this->txt((string) ($arr->tipoVehiculo?->nombre ?? '')), 0, 10), 1, 0, 'L', true);
                $this->SetFont('Arial', '', 9);
                $this->Cell(35, $linea, $this->txt((string) ($arr->documentacion?->vin ?? '')), 1, 0, 'C', true);
                $this->SetFont('Arial', '', 10);
                $this->Cell(20, $linea, $this->txt((string) $arr->placa), 1, 0, 'C', true);
                $this->Cell(30, $linea, $this->txt((string) ($arr->documentacion?->nro_carroceria ?? '')), 1, 0, 'C', true);
                $this->Cell(24, $linea, $this->txt((string) ($arr->documentacion?->nro_chasis ?? '')), 1, 0, 'C', true);
                $this->Cell(15, $linea, $this->cambiarVariable($arr->capacidad_toneladas, 0), 1, 0, 'C', true);
                $this->Cell(20, $linea, $this->cambiarVariable($arr->indice_consumo, 2), 1, 0, 'C', true);
                $posY += $linea;
                $i++;
                if ($i > $max) {
                    $this->inicio($tituloGrupo);
                    $this->titulos(10, 15, 35, $campos);
                    $posY = 45;
                    $i = 0;
                }
            }
        }

        return $this->salida('Parque.pdf');
    }

    // ---------------------------------------------------------------------
    // 344 · CRT - CIRCULACION - LICENCIA OPERATIVA
    // ---------------------------------------------------------------------

    public function pdfCrtCirculacion()
    {
        $campos1 = [];
        $campos1[] = ['titulo' => '#', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR'];
        $campos1[] = ['titulo' => 'CHAPA', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTR'];
        $campos1[] = ['titulo' => 'CRT', 'ancho' => 75, 'direccion' => 'C'];
        $campos1[] = ['titulo' => 'CIRCULACION', 'ancho' => 75, 'direccion' => 'C'];
        $campos1[] = ['titulo' => 'LICENCIA OPERATIVA', 'ancho' => 75, 'direccion' => 'C'];

        $campos = [];
        $campos[] = ['titulo' => 'INV', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB'];
        $campos[] = ['titulo' => '', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB'];
        $campos[] = ['titulo' => 'No', 'ancho' => 20, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'EMISION', 'ancho' => 20, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'VENCE', 'ancho' => 20, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'DIAS', 'ancho' => 15, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'No', 'ancho' => 20, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'EMISION', 'ancho' => 20, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'VENCE', 'ancho' => 20, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'DIAS', 'ancho' => 15, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'No', 'ancho' => 20, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'EMISION', 'ancho' => 20, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'VENCE', 'ancho' => 20, 'direccion' => 'C'];
        $campos[] = ['titulo' => 'DIAS', 'ancho' => 15, 'direccion' => 'C'];

        $data = Tractivo::with(['documentacion', 'grupo'])
            ->whereIn('id_entidad', $this->entidadIds)
            ->orderBy('id_grupo')->orderBy('codigo')->get();

        if ($data->isEmpty()) {
            $this->tecnicaNoData('CRT - CIRCULACION - LICENCIA OPERATIVA');

            return $this->salida('CRT.pdf');
        }

        $porGrupo = $data->groupBy('id_grupo');
        foreach ($porGrupo as $grupoId => $tractivos) {
            $nombreGrupo = $this->catalogoNombre($grupoId) ?: '';
            $titulo = 'CRT - CIRCULACION - LICENCIA OPERATIVA '.$nombreGrupo;
            $this->inicio($titulo);
            $this->titulos(6, 10, 35, $campos, $campos1);
            $posY = 47;
            $i = 1;
            $max = 20;
            $linea = 7;
            foreach ($tractivos as $arr) {
                $doc = $arr->documentacion;
                $diasF = $this->tecnicaDiasSigno($this->fechaOperaciones, $doc?->fvence_ficav);
                $diasC = $this->tecnicaDiasSigno($this->fechaOperaciones, $doc?->fvence_circ);
                $diasL = $this->tecnicaDiasSigno($this->fechaOperaciones, $doc?->fvence_lot);

                $vencido = ($diasF !== null && $diasF < 0) || ($diasC !== null && $diasC < 0) || ($diasL !== null && $diasL < 0);
                $this->SetFillColor($vencido ? 240 : 255, $vencido ? 220 : 255, $vencido ? 220 : 255);
                $this->SetFont('Arial', '', 10);
                $this->SetXY(10, $posY);
                $this->Cell(15, $linea, $this->txt((string) $arr->codigo), 1, 0, 'L', true);
                $this->Cell(25, $linea, $this->txt((string) $arr->placa), 1, 0, 'L', true);
                $this->Cell(20, $linea, $this->txt((string) ($doc?->ficav ?? '')), 1, 0, 'L', true);
                $this->Cell(20, $linea, $this->tecnicaFecha($doc?->femision_ficav), 1, 0, 'L', true);
                $this->Cell(20, $linea, $this->tecnicaFecha($doc?->fvence_ficav), 1, 0, 'L', true);
                $this->Cell(15, $linea, $diasF, 1, 0, 'C', true);
                $this->Cell(20, $linea, $this->txt((string) ($doc?->circulacion ?? '')), 1, 0, 'L', true);
                $this->Cell(20, $linea, $this->tecnicaFecha($doc?->femision_circ), 1, 0, 'L', true);
                $this->Cell(20, $linea, $this->tecnicaFecha($doc?->fvence_circ), 1, 0, 'L', true);
                $this->Cell(15, $linea, $diasC, 1, 0, 'C', true);
                $this->Cell(20, $linea, $this->txt((string) ($doc?->lot ?? '')), 1, 0, 'L', true);
                $this->Cell(20, $linea, $this->tecnicaFecha($doc?->femision_lot), 1, 0, 'L', true);
                $this->Cell(20, $linea, $this->tecnicaFecha($doc?->fvence_lot), 1, 0, 'L', true);
                $this->Cell(15, $linea, $diasL, 1, 0, 'C', true);
                $posY += $linea;
                $i++;
                if ($i > $max) {
                    $this->inicio($titulo);
                    $this->titulos(6, 10, 35, $campos, $campos1);
                    $posY = 47;
                    $i = 0;
                }
            }
        }

        return $this->salida('CRT.pdf');
    }

    // ---------------------------------------------------------------------
    // 361 · MANTENIMIENTOS REALIZADOS EN OTRAS ENTIDADES
    // ---------------------------------------------------------------------

    public function pdfMttosExterior(?string $mes = null)
    {
        [$anio, $m] = $this->anioMes($mes);
        $mesStr = sprintf('%02d', $m);
        $inicio = sprintf('%04d-%02d-01', $anio, $m);
        $fin = date('Y-m-t', strtotime($inicio));
        $titulo = 'MANTENIMIENTOS REALIZADOS EN OTRAS ENTIDADES';

        $campos1 = [];
        $campos1[] = ['titulo' => '# ORDEN', 'ancho' => 25, 'direccion' => 'C', 'letra' => '10'];
        $campos1[] = ['titulo' => 'FECHA', 'ancho' => 25, 'direccion' => 'C', 'letra' => '10'];
        $campos1[] = ['titulo' => 'VEHICULO', 'ancho' => 25, 'direccion' => 'C', 'letra' => '10'];
        $campos1[] = ['titulo' => 'KMS ENTRADA', 'ancho' => 30, 'direccion' => 'C', 'letra' => '10'];
        $campos1[] = ['titulo' => 'TALLER', 'ancho' => 75, 'direccion' => 'C', 'letra' => '10'];

        $data = OrdenesTaller::with(['tractivo', 'taller'])
            ->whereIn('id_entidad', $this->entidadIds)
            ->whereNotNull('id_taller')
            ->whereBetween('fecha_ingreso', [$inicio, $fin])
            ->orderBy('fecha_ingreso')->get();

        if ($data->isEmpty()) {
            $this->tecnicaNoData($titulo, $mesStr);

            return $this->salida('MttosExterior.pdf');
        }

        $this->inicio($titulo, $mesStr);
        $this->titulos(7, 15, 35, $campos1);
        $posY = 42;
        $i = 1;
        $max = 35;
        foreach ($data as $arr) {
            $this->SetFillColor(255, 255, 255);
            $this->SetFont('Arial', '', 10);
            $this->SetXY(15, $posY);
            $this->Cell(25, 6, $this->txt((string) $arr->numero), 1, 0, 'C', true);
            $this->Cell(25, 6, $this->tecnicaFecha($arr->fecha_ingreso), 1, 0, 'C', true);
            $this->Cell(25, 6, $this->txt((string) ($arr->tractivo?->codigo ?? '')), 1, 0, 'C', true);
            $this->Cell(30, 6, $arr->cant_clasificacion, 1, 0, 'C', true);
            $this->Cell(75, 6, $this->txt((string) ($arr->taller?->nombre ?? '')), 1, 0, 'C', true);
            $posY += 6;
            $i++;
            if ($i > $max) {
                $this->inicio($titulo, $mesStr);
                $this->titulos(7, 15, 35, $campos1);
                $posY = 42;
                $i = 0;
            }
        }

        return $this->salida('MttosExterior.pdf');
    }
}
