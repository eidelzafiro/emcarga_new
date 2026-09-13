<?php

namespace App\Services\Reports\Fpdf;

use App\Models\CombustibleDescarga;
use App\Services\Reports\Fpdf\Concerns\Combustible126;
use Illuminate\Support\Facades\DB;

/**
 * Reportes de COMBUSTIBLE (dominio de Contabilidad) replicando el layout exacto
 * del legacy `Reportes2.php` con FPDF.
 *
 * Se portan por lotes. Ya migrados:
 *   1012 LISTADO DE DESCARGAS SUPERIOR AL TANQUE (pdf_combustible_tanque)
 *   1013 LISTADO DE DESCARGAS EQUIPOS EN UNA TARJETA (pdf_combustible_tarjetas)
 */
class CombustibleFpdfReport extends DocumentosFpdfBase
{
    use Combustible126;

    /** @var int[] */
    protected array $entidadIds;

    public function __construct(
        string $orientation,
        string $paper,
        ?object $entidad,
        bool $multiEntidad,
        string $fechaOperaciones,
        array $entidadIds = [],
    ) {
        parent::__construct($orientation, $paper, $entidad, $multiEntidad, $fechaOperaciones);
        $this->entidadIds = $entidadIds ?: [23];
    }

    // =====================================================================
    // 1012 — LISTADO DE DESCARGAS SUPERIOR AL TANQUE DE COMBUSTIBLE
    // =====================================================================

    public function pdfDescargasSuperior(?string $mes): \Illuminate\Http\Response
    {
        $titulo = 'VALIDACION DESCARGAS SUPERIOR A TANQUE DE COMBUSTIBLE';
        [$anio, $mesNum] = $this->anioMes($mes);

        $campos = [
            ['titulo' => 'FECHA CHIP', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'EQUIPO', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'EMPLEADO', 'ancho' => 80, 'direccion' => 'C'],
            ['titulo' => 'CHIP', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'IMPORTE', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'LITROS', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'TANQUE', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'DIF', 'ancho' => 15, 'direccion' => 'C'],
        ];

        $data = $this->descargasSuperiorTanque($anio, $mesNum);

        $posY = 41;
        $i = 1;
        $max = 26;

        if ($data->isNotEmpty()) {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->titulos(6, 15, 35, $campos);
            foreach ($data as $arr) {
                $this->SetFont('Arial', '', 12);
                $this->SetXY(15, $posY);
                $this->SetFillColor(255, 255, 255);
                $this->Cell(30, 6, $this->txt((string) $arr->fchip), 1, 0, 'C', 1);
                $this->Cell(20, 6, (string) $arr->codtractivo, 1, 0, 'L', 1);
                $this->Cell(80, 6, ucwords(mb_strtolower($this->txt((string) $arr->nombrecompleto))), 1, 0, 'L', 1);
                $this->Cell(20, 6, $this->cambiarVariable($arr->folio, 0), 1, 0, 'R', 1);
                $this->Cell(20, 6, $this->cambiarVariable($arr->saldomon, 2), 1, 0, 'R', 1);
                $this->Cell(20, 6, $this->cambiarVariable($arr->saldolts, 2), 1, 0, 'R', 1);
                $this->Cell(20, 6, $this->cambiarVariable($arr->tanqueinicio, 2), 1, 0, 'R', 1);
                $this->Cell(15, 6, $this->cambiarVariable((float) $arr->saldolts - (float) $arr->tanqueinicio, 2), 1, 0, 'R', 1);
                $posY += 6;
                $i++;
                if ($i > $max) {
                    $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                    $this->titulos(6, 15, 35, $campos);
                    $posY = 41;
                    $i = 1;
                }
            }
        } else {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
        }

        return $this->salida($titulo.'.pdf');
    }

    // =====================================================================
    // 1013 — LISTADO DE DESCARGAS EQUIPOS EN UNA TARJETA
    // =====================================================================

    public function pdfDescargasEquiposTarjeta(?string $mes): \Illuminate\Http\Response
    {
        $titulo = 'VALIDACION DESCARGAS DE VARIOS EQUIPOS EN UNA TARJETA';
        [$anio, $mesNum] = $this->anioMes($mes);

        $campos = [
            ['titulo' => 'TARJETAS', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'TRACTIVO', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'IMPORTE', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'LITROS', 'ancho' => 20, 'direccion' => 'C'],
        ];

        $data = $this->descargasEquiposEnTarjeta($anio, $mesNum);

        $posY = 41;
        $i = 1;
        $max = 34;

        if ($data->isNotEmpty()) {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->titulos(6, 15, 35, $campos);
            foreach ($data as $arr) {
                $this->SetFont('Arial', '', 12);
                $this->SetXY(15, $posY);
                $this->SetFillColor(255, 255, 255);
                $this->Cell(30, 6, (string) $arr->codtm, 1, 0, 'C', 1);
                $this->Cell(30, 6, (string) $arr->codtractivo, 1, 0, 'L', 1);
                $this->Cell(20, 6, $this->cambiarVariable($arr->saldomon, 2), 1, 0, 'R', 1);
                $this->Cell(20, 6, $this->cambiarVariable($arr->saldolts, 2), 1, 0, 'R', 1);
                $posY += 6;
                $i++;
                if ($i >= $max) {
                    $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                    $this->titulos(6, 15, 35, $campos);
                    $posY = 41;
                    $i = 0;
                }
            }
        } else {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
        }

        return $this->salida($titulo.'.pdf');
    }

    // =====================================================================
    // 43 / 44 / 45 — TARJETAS COMBUSTIBLE CON / SIN SALDO
    // =====================================================================

    public function pdfTarjetasSaldo(bool $conSaldo): \Illuminate\Http\Response
    {
        $titulo = $conSaldo
            ? 'TARJETAS MAGNETICAS COMBUSTIBLE CON SALDO'
            : 'TARJETAS MAGNETICAS COMBUSTIBLE SIN SALDO';

        $campos1 = [
            ['titulo' => 'NRO', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'CODIGO', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'NOMBRE DEL RESPONSABLE', 'ancho' => 75, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'FECHA ULTIMO', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'ACTUAL', 'ancho' => 50, 'direccion' => 'C'],
        ];
        $campos = [
            ['titulo' => '', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 75, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'MOVIMIENTO', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'IMP', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'LTS', 'ancho' => 25, 'direccion' => 'C'],
        ];

        $data = DB::table('tarjetas as t')
            ->leftJoin('bolsa as b', 'b.id', '=', 't.idempleado')
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('t.id_entidad', $this->entidadIds))
            ->whereNull('t.fcancelado')
            ->when($conSaldo, fn ($q) => $q->where('t.saldo_actual', '>', 0), fn ($q) => $q->where('t.saldo_actual', 0))
            ->orderBy('t.numero')
            ->selectRaw('t.id, t.numero as codtm, t.fmovimiento,
                TRIM(CONCAT(COALESCE(b.nombre,""), " ", COALESCE(b.apellidos,""))) as nombrecompleto,
                COALESCE(t.saldo_actual,0) as saldoactualmon, COALESCE(t.saldoactuallts,0) as saldoactuallts')
            ->get();

        $posX = 15;
        $posY = 42;
        $i = 1;
        $nro = 1;
        $max = 37;
        $saldoMon = 0.0;
        $saldoLts = 0.0;

        if ($data->isNotEmpty()) {
            $this->inicio($titulo, '', 50, 5);
            $this->titulos(6, 15, 30, $campos, $campos1);
            foreach ($data as $arr) {
                $this->SetFont('Arial', '', 12);
                $this->SetXY($posX, $posY);
                $this->SetFillColor(255, 255, 255);
                $this->Cell(15, 6, (string) $nro, 1, 0, 'C', 1);
                $this->SetFont('Arial', 'U', 14);
                $this->Cell(25, 6, (string) $arr->codtm, 1, 0, 'C', 1);
                $this->SetFont('Arial', '', 11);
                $this->Cell(75, 6, ucwords(mb_strtolower($this->txt((string) $arr->nombrecompleto))), 1, 0, 'L', 1);
                $this->SetFont('Arial', '', 14);
                $this->Cell(30, 6, (string) $arr->fmovimiento, 1, 0, 'C', 1);
                $this->Cell(25, 6, $this->cambiarVariable($arr->saldoactualmon, 2), 1, 0, 'R', 1);
                $this->Cell(25, 6, $this->cambiarVariable($arr->saldoactuallts, 2), 1, 0, 'R', 1);
                $posY += 6;
                $i++;
                $nro++;
                $saldoMon += (float) $arr->saldoactualmon;
                $saldoLts += (float) $arr->saldoactuallts;
                if ($i === $max) {
                    $this->inicio($titulo, '', 50, 5);
                    $this->titulos(6, 15, 30, $campos, $campos1);
                    $posY = 42;
                    $posX = 15;
                    $i = 1;
                }
            }
            $this->SetXY($posX, $posY);
            $this->SetFillColor(200, 200, 200);
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(145, 10, 'TOTAL', 1, 0, 'L', 1);
            $this->Cell(25, 10, $this->cambiarVariable($saldoMon, 2), 1, 0, 'R', 1);
            $this->Cell(25, 10, $this->cambiarVariable($saldoLts, 2), 1, 0, 'R', 1);
        } else {
            $this->inicio($titulo, '', 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
        }

        return $this->salida($titulo.'.pdf');
    }

    // =====================================================================
    // 1001 — TARJETAS MAGNETICAS FECHA DE VENCIMIENTO
    // =====================================================================

    public function pdfTarjetasVencimiento(?string $mes): \Illuminate\Http\Response
    {
        $titulo = 'TARJETAS MAGNETICAS FECHA DE VENCIMIENTO';
        [$anio, $mesNum] = $this->anioMes($mes);
        $diasMes = (int) date('t', strtotime(sprintf('%04d-%02d-01', $anio, $mesNum))) - 1;
        $fechaCorte = sprintf('%04d/%02d/%02d', $anio, $mesNum, $diasMes);

        $campos = [
            ['titulo' => 'NRO', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'CODIGO', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'FECHA VENCE', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'DIAS', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LTR'],
        ];

        $data = DB::table('tarjetas as t')
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('t.id_entidad', $this->entidadIds))
            ->where('t.cancelado', 0)
            ->orderBy('t.numero')
            ->selectRaw('t.numero as codtm, t.fvence')
            ->get();

        $posX = 15;
        $posY = 36;
        $i = 1;
        $nro = 1;
        $max = 37;

        if ($data->isNotEmpty()) {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->titulos(6, 15, 30, $campos);
            foreach ($data as $arr) {
                $dias = $arr->fvence ? (strtotime((string) $arr->fvence) - strtotime($fechaCorte)) : 0;
                $vencida = $dias < 0;
                $this->SetFont('Arial', $vencida ? 'B' : '', 12);
                $this->SetXY($posX, $posY);
                $this->SetFillColor($vencida ? 200 : 255, $vencida ? 200 : 255, $vencida ? 200 : 255);
                $this->Cell(15, 6, (string) $nro, 1, 0, 'C', 1);
                $this->SetFont('Arial', 'U', 14);
                $this->Cell(25, 6, (string) $arr->codtm, 1, 0, 'C', 1);
                $this->Cell(30, 6, (string) $arr->fvence, 1, 0, 'C', 1);
                $this->Cell(30, 6, (string) round($dias / 60 / 60 / 24), 1, 0, 'C', 1);
                if ($vencida) {
                    $this->Cell(30, 6, '(VENCIDA)', 'L', 0, 'C', 1);
                }
                $posY += 6;
                $i++;
                $nro++;
                if ($i === $max) {
                    $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                    $this->titulos(6, 15, 30, $campos);
                    $posY = 36;
                    $posX = 15;
                    $i = 1;
                }
            }
        } else {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
        }

        return $this->salida($titulo.'.pdf');
    }

    // =====================================================================
    // 133 — TARJETAS COMBUSTIBLE SIN MOVIMIENTO EN LOS ULTIMOS 3 DIAS
    // =====================================================================

    public function pdfTarjetas3Dias(): \Illuminate\Http\Response
    {
        $titulo = 'TARJETAS COMBUSTIBLE SIN MOVIMIENTO EN LOS ULTIMOS 3DIAS';

        $campos1 = [
            ['titulo' => 'NRO', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'CODIGO', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'NOMBRE DEL RESPONSABLE', 'ancho' => 75, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'FECHA ULTIMO', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'ACTUAL', 'ancho' => 50, 'direccion' => 'C'],
        ];
        $campos = [
            ['titulo' => '', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 75, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'MOVIMIENTO', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'LTS', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'IMP', 'ancho' => 25, 'direccion' => 'C'],
        ];

        $idCajera = (int) ($this->entidad->id_cajera ?? 0);
        $data = DB::table('tarjetas as t')
            ->leftJoin('bolsa as b', 'b.id', '=', 't.idempleado')
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('t.id_entidad', $this->entidadIds))
            ->whereNull('t.fcancelado')
            ->when($idCajera, fn ($q) => $q->where('t.idempleado', '!=', $idCajera))
            ->orderBy('t.numero')
            ->selectRaw('t.id, t.numero as codtm, t.fmovimiento,
                TRIM(CONCAT(COALESCE(b.nombre,""), " ", COALESCE(b.apellidos,""))) as nombrecompleto,
                COALESCE(t.saldo_actual,0) as saldoactualmon, COALESCE(t.saldoactuallts,0) as saldoactuallts')
            ->get()
            ->filter(function ($arr) {
                $arr->dias = $this->restaFechas($this->fechaOperaciones, (string) $arr->fmovimiento);

                return $arr->dias > 3;
            })
            ->values();

        $posX = 15;
        $posY = 42;
        $i = 1;
        $nro = 1;
        $max = 25;
        $saldoMon = 0.0;
        $saldoLts = 0.0;

        if ($data->isNotEmpty()) {
            $this->inicio($titulo);
            $this->titulos(6, 15, 30, $campos, $campos1);
            foreach ($data as $arr) {
                $this->SetFont('Arial', 'B', 14);
                $this->SetXY($posX, $posY);
                $this->SetFillColor(255, 255, 255);
                $this->Cell(15, 8, (string) $nro, 1, 0, 'C', 1);
                $this->SetFont('Arial', 'U', 14);
                $this->Cell(25, 8, (string) $arr->codtm, 1, 0, 'C', 1);
                $this->SetFont('Arial', 'B', 14);
                $this->Cell(75, 8, ucwords(mb_strtolower($this->txt((string) $arr->nombrecompleto))), 1, 0, 'L', 1);
                $this->Cell(30, 8, (string) $arr->fmovimiento, 1, 0, 'C', 1);
                $this->Cell(25, 8, $this->cambiarVariable($arr->saldoactuallts, 2), 1, 0, 'R', 1);
                $this->Cell(25, 8, $this->cambiarVariable($arr->saldoactualmon, 2), 1, 0, 'R', 1);
                $posY += 8;
                $i++;
                $nro++;
                $saldoMon += (float) $arr->saldoactualmon;
                $saldoLts += (float) $arr->saldoactuallts;
                if ($i === $max) {
                    $this->inicio($titulo);
                    $this->titulos(6, 15, 30, $campos, $campos1);
                    $posY = 42;
                    $posX = 15;
                    $i = 1;
                }
            }
            $this->SetXY($posX, $posY);
            $this->SetFillColor(200, 200, 200);
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(145, 10, 'TOTAL', 1, 0, 'L', 1);
            $this->Cell(25, 10, $this->cambiarVariable($saldoLts, 2), 1, 0, 'R', 1);
            $this->Cell(25, 10, $this->cambiarVariable($saldoMon, 2), 1, 0, 'R', 1);
        } else {
            $this->inicio($titulo);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
        }

        return $this->salida($titulo.'.pdf');
    }

    // =====================================================================
    // 46 — TARJETAS MAGNETICAS POR RESPONSABLE
    // =====================================================================

    public function pdfTarjetasResponsable(): \Illuminate\Http\Response
    {
        $titulo = 'TARJETAS MAGNETICAS POR RESPONSABLE';

        $campos1 = [
            ['titulo' => 'NRO', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'NOMBRE DEL RESPONSABLE', 'ancho' => 95, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'CANT', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'ACTUAL', 'ancho' => 60, 'direccion' => 'C'],
        ];
        $campos = [
            ['titulo' => '', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 95, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'LTS', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'IMP', 'ancho' => 30, 'direccion' => 'C'],
        ];

        $idCajera = (int) ($this->entidad->id_cajera ?? 0);
        $data = DB::table('tarjetas as t')
            ->join('bolsa as b', 'b.id', '=', 't.idempleado')
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('t.id_entidad', $this->entidadIds))
            ->whereNull('t.fcancelado')
            ->where('t.saldo_actual', '>', 0)
            ->groupBy('t.idempleado', 'b.nombre', 'b.apellidos')
            ->orderBy('t.idempleado')
            ->selectRaw('COUNT(t.numero) as cant,
                SUM(t.saldo_actual) as saldoactualmon,
                SUM(COALESCE(t.saldoactuallts,0)) as saldoactuallts,
                t.idempleado,
                TRIM(CONCAT(COALESCE(b.nombre,""), " ", COALESCE(b.apellidos,""))) as nombrecompleto')
            ->get();

        $posX = 15;
        $posY = 42;
        $i = 1;
        $nro = 1;
        $max = 30;
        $cant = 0;
        $saldoMon = 0.0;
        $saldoLts = 0.0;

        if ($data->isNotEmpty()) {
            $this->inicio($titulo);
            $this->titulos(6, 15, 30, $campos, $campos1);
            foreach ($data as $arr) {
                $this->SetFont('Arial', '', 12);
                $this->SetXY($posX, $posY);
                $this->SetFillColor(255, 255, 255);
                $this->Cell(15, 6, (string) $nro, 1, 0, 'C', 1);
                if ((int) $arr->idempleado === $idCajera && $idCajera > 0) {
                    $this->SetTextColor(255, 0, 0);
                    $this->SetFont('Arial', 'U', 16);
                    $this->Cell(95, 6, 'CAJA', 1, 0, 'L', 1);
                } elseif ((int) $arr->idempleado === 394) {
                    $this->SetTextColor(255, 0, 0);
                    $this->SetFont('Arial', 'U', 16);
                    $this->Cell(95, 6, 'PUESTO MANDO', 1, 0, 'L', 1);
                } else {
                    $this->SetTextColor(0, 0, 0);
                    $this->SetFont('Arial', 'BU', 13);
                    $this->Cell(95, 6, ucwords(mb_strtolower($this->txt((string) $arr->nombrecompleto))), 1, 0, 'L', 1);
                }
                $this->SetFont('Arial', '', 14);
                $this->SetTextColor(0, 0, 0);
                $this->Cell(20, 6, $this->cambiarVariable($arr->cant, 0), 1, 0, 'C', 1);
                $this->Cell(30, 6, $this->cambiarVariable($arr->saldoactuallts, 2), 1, 0, 'R', 1);
                $this->Cell(30, 6, $this->cambiarVariable($arr->saldoactualmon, 2), 1, 0, 'R', 1);
                $posY += 6;
                $i++;
                $nro++;
                $cant += (int) $arr->cant;
                $saldoMon += (float) $arr->saldoactualmon;
                $saldoLts += (float) $arr->saldoactuallts;
                if ($i === $max) {
                    $this->inicio($titulo);
                    $this->titulos(6, 15, 30, $campos, $campos1);
                    $posY = 42;
                    $posX = 15;
                    $i = 1;
                }
            }
            $this->SetXY($posX, $posY);
            $this->SetFillColor(200, 200, 200);
            $this->SetFont('Arial', 'B', 18);
            $this->Cell(110, 10, 'TOTAL', 1, 0, 'L', 1);
            $this->Cell(20, 10, $this->cambiarVariable($cant, 0), 1, 0, 'C', 1);
            $this->Cell(30, 10, $this->cambiarVariable($saldoLts, 2), 1, 0, 'R', 1);
            $this->Cell(30, 10, $this->cambiarVariable($saldoMon, 2), 1, 0, 'R', 1);
        } else {
            $this->inicio($titulo);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
        }

        return $this->salida($titulo.'.pdf');
    }

    // =====================================================================
    // 377 — PARTE DIARIO DE LAS EXISTENCIAS DE COMBUSTIBLE PETROLEO
    // =====================================================================

    public function pdfParteExistencias(?string $fecha): \Illuminate\Http\Response
    {
        $titulo = 'PARTE DIARIO DE LAS EXISTENCIAS DE COMBUSTIBLE PETROLEO';

        $campos = [
            ['titulo' => 'DIA', 'ancho' => 15, 'bordes' => 'LTR', 'direccion' => 'C'],
            ['titulo' => 'INVENTARIO', 'ancho' => 35, 'bordes' => 'LTR', 'direccion' => 'C'],
            ['titulo' => 'HABILITADO', 'ancho' => 35, 'bordes' => 'LTR', 'direccion' => 'C'],
            ['titulo' => 'INVENTARIO', 'ancho' => 35, 'bordes' => 'LTR', 'direccion' => 'C'],
            ['titulo' => 'HABILITADO', 'ancho' => 35, 'bordes' => 'LTR', 'direccion' => 'C'],
            ['titulo' => 'COMBUSTIBLE', 'ancho' => 35, 'bordes' => 'LTR', 'direccion' => 'C'],
            ['titulo' => 'CONSUMO', 'ancho' => 35, 'bordes' => 'LTR', 'direccion' => 'C'],
            ['titulo' => 'COBERTURA', 'ancho' => 35, 'bordes' => 'LTR', 'direccion' => 'C'],
        ];
        $campos1 = [
            ['titulo' => '', 'ancho' => 15, 'bordes' => 'LRB', 'direccion' => 'C'],
            ['titulo' => 'INICIAL ', 'ancho' => 35, 'bordes' => 'LRB', 'direccion' => 'C'],
            ['titulo' => 'EN EL DIA', 'ancho' => 35, 'bordes' => 'LRB', 'direccion' => 'C'],
            ['titulo' => 'FINAL', 'ancho' => 35, 'bordes' => 'LRB', 'direccion' => 'C'],
            ['titulo' => 'ACUMULADO', 'ancho' => 35, 'bordes' => 'LRB', 'direccion' => 'C'],
            ['titulo' => 'CARGADO', 'ancho' => 35, 'bordes' => 'LRB', 'direccion' => 'C'],
            ['titulo' => 'PROMEDIO', 'ancho' => 35, 'bordes' => 'LRB', 'direccion' => 'C'],
            ['titulo' => 'DE DIAS', 'ancho' => 35, 'bordes' => 'LRB', 'direccion' => 'C'],
        ];

        $fechaOps = $fecha ?: $this->fechaOperaciones;
        $anio = (int) substr($fechaOps, 0, 4);
        $mesNum = (int) substr($fechaOps, 5, 2);
        $dia = (int) substr($fechaOps, 8, 2);

        $sinicial = (float) $this->resumenInicialDiesel($anio, $mesNum);

        $posY = 47;
        $saldolts = $sinicial;
        $habilitado = 0.0;
        $tdescargas = 0.0;
        $tcargas = 0.0;

        $this->inicio($titulo, $fechaOps, 50, 5);
        $this->titulos(6, 10, 35, $campos1, $campos);

        for ($i = 1; $i <= $dia; $i++) {
            $descargas = $this->descargasDieselDia($anio, $mesNum, $i);
            $cargas = $this->cargasDieselDia($anio, $mesNum, $i);
            $inventario = $saldolts > 0 ? round($saldolts - $descargas + $cargas, 2) : 0.0;
            $habilitado += $descargas;
            $promedio = $i > 0 ? round($habilitado / $i, 2) : 0.0;
            $cobertura = $promedio > 0 ? round($inventario / $promedio, 2) : '';

            $this->SetFont('Arial', 'B', 16);
            $this->SetXY(10, $posY);
            $this->SetFillColor(255, 255, 255);
            $this->Cell(15, 6, (string) $i, 1, 0, 'C', 1);
            $this->Cell(35, 6, $this->cambiarVariable($saldolts, 2), 1, 0, 'R', 1);
            $this->Cell(35, 6, $this->cambiarVariable($descargas, 2), 1, 0, 'R', 1);
            $this->Cell(35, 6, $this->cambiarVariable($inventario, 2), 1, 0, 'R', 1);
            $this->Cell(35, 6, $this->cambiarVariable($habilitado, 2), 1, 0, 'R', 1);
            $this->Cell(35, 6, $this->cambiarVariable($cargas, 2), 1, 0, 'R', 1);
            $this->Cell(35, 6, $this->cambiarVariable($promedio, 2), 1, 0, 'R', 1);
            $this->Cell(35, 6, $this->cambiarVariable($cobertura, 2), 1, 0, 'R', 1);
            $posY += 6;
            $saldolts = $inventario;
            $tdescargas += $descargas;
            $tcargas += $cargas;

            if ($i === 24) {
                $this->inicio($titulo, $fechaOps, 50, 5);
                $this->titulos(6, 10, 35, $campos1, $campos);
                $posY = 47;
            }
        }

        $this->SetXY(10, $posY);
        $this->SetFillColor(255, 255, 255);
        $this->Cell(50, 10, 'TOTAL', 1, 0, 'C', 1);
        $this->Cell(35, 10, $this->cambiarVariable($tdescargas, 2), 1, 0, 'R', 1);
        $this->Cell(35, 10, '', 1, 0, 'L', 1);
        $this->Cell(35, 10, '', 1, 0, 'L', 1);
        $this->Cell(35, 10, $this->cambiarVariable($tcargas, 2), 1, 0, 'R', 1);
        $this->Cell(35, 10, '', 1, 0, 'L', 1);
        $this->Cell(35, 10, '', 1, 0, 'L', 1);

        return $this->salida($titulo.'.pdf');
    }

    // =====================================================================
    // 1023 / 1031 — VALIDACION TARJETAS MAGNETICAS SEGUN ONURE
    // =====================================================================

    public function pdfValidacionOnure(?string $mes): \Illuminate\Http\Response
    {
        return $this->renderValidacionOnure($mes, false);
    }

    public function pdfValidacionOnure2(?string $mes): \Illuminate\Http\Response
    {
        return $this->renderValidacionOnure($mes, true);
    }

    private function renderValidacionOnure(?string $mes, bool $porEquipo): \Illuminate\Http\Response
    {
        $titulo = 'VALIDACION TARJETAS MAGNETICAS SEGUN ONURE';
        [$anio, $mesNum] = $this->anioMes($mes);

        $campos = [
            ['titulo' => 'FECHA', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 7],
            ['titulo' => 'HORA', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'COMERCIO', 'ancho' => 65, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 10],
            ['titulo' => 'CONSUMO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'EQUIPO', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'INDICE', 'ancho' => 45, 'direccion' => 'C'],
            ['titulo' => 'NIVEL DE ACTIVIDAD', 'ancho' => 60, 'direccion' => 'C'],
        ];
        $campos1 = [
            ['titulo' => '', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 7],
            ['titulo' => '', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 65, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'LITROS', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 10],
            ['titulo' => '  ', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'PLAN', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'REAL', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'DIFERENCIA', 'ancho' => 15, 'direccion' => 'C', 'letra' => 6],
            ['titulo' => 'PLANIFICADO', 'ancho' => 20, 'direccion' => 'C', 'letra' => 7],
            ['titulo' => 'REAL', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'DIFERENCIA', 'ancho' => 20, 'direccion' => 'C'],
        ];

        $data = $this->detalleDescargaOnure($anio, $mesNum);

        if ($data->isEmpty()) {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');

            return $this->salida($titulo.'.pdf');
        }

        $posY = 42;
        $i = 1;
        $max = $porEquipo ? 25 : 26;
        $equipo = null;

        $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
        $this->titulos(6, 10, 30, $campos1, $campos);

        foreach ($data as $arr) {
            if ($porEquipo && $equipo !== null && $equipo !== $arr->codtractivo) {
                $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                $this->titulos(6, 10, 30, $campos1, $campos);
                $posY = 42;
                $i = 0;
            }
            $equipo = $arr->codtractivo;

            $this->filaValidacionOnure($arr, $posY);
            $posY += 6;
            $i++;

            if ($i === $max) {
                $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                $this->titulos(6, 10, 30, $campos1, $campos);
                $posY = 42;
                $i = 0;
            }
        }

        return $this->salida($titulo.'.pdf');
    }

    private function filaValidacionOnure(object $arr, float $posY): void
    {
        $this->SetFont('Arial', '', 7);
        $this->SetXY(10, $posY);
        $this->SetFillColor(255, 255, 255);
        $this->Cell(15, 6, (string) $arr->fdescarga, 1, 0, 'C', 1);
        $this->Cell(10, 6, (string) $arr->hdescarga, 1, 0, 'C', 1);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(65, 6, $this->txt((string) $arr->servicentros), 1, 0, 'L', 1);
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(20, 6, $this->cambiarVariable($arr->saldolts, 2), 1, 0, 'R', 1);
        $this->Cell(25, 6, (string) $arr->codtractivo, 1, 0, 'C', 1);
        $this->Cell(15, 6, $this->cambiarVariable($arr->indice, 2), 1, 0, 'R', 1);
        $this->Cell(15, 6, $this->cambiarVariable($arr->indicereal, 2), 1, 0, 'R', 1);
        $this->Cell(15, 6, $this->cambiarVariable($arr->difindice, 2), 1, 0, 'R', 1);
        $this->Cell(20, 6, $this->cambiarVariable($arr->kmsplan, 2), 1, 0, 'R', 1);
        $this->Cell(20, 6, $this->cambiarVariable($arr->kms, 2), 1, 0, 'R', 1);
        $this->Cell(20, 6, $this->cambiarVariable($arr->difkms, 2), 1, 0, 'R', 1);
    }

    // =====================================================================
    // 1011 — LISTADO TRACTIVOS CON TANQUE DE COMBUSTIBLE
    // =====================================================================

    public function pdfTractivosTanque(): \Illuminate\Http\Response
    {
        $titulo = 'LISTADO TRACTIVOS CON TANQUE DE COMBUSTIBLE';

        $campos = [
            ['titulo' => 'NUMERO', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'CHAPA', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'CHOFER', 'ancho' => 80, 'direccion' => 'C'],
            ['titulo' => 'COMBUSTIBLE', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'TANQUE', 'ancho' => 20, 'direccion' => 'C'],
        ];

        $data = DB::table('tractivos as t')
            ->leftJoin('catalogo_items as ci', 'ci.id', '=', 't.id_tipo_combustible')
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('t.id_entidad', $this->entidadIds))
            ->where(fn ($q) => $q->whereNull('t.id_grupo')->orWhere('t.id_grupo', '!=', 8))
            ->whereNull('t.fecha_baja')
            ->orderBy('t.codigo')
            ->selectRaw('t.codigo as codtractivo, t.placa as chapa, ci.nombre as tipocombustibles,
                COALESCE(t.cap_deposito,0) as captanque,
                (SELECT TRIM(CONCAT(COALESCE(b2.nombre,""), " ", COALESCE(b2.apellidos,"")))
                    FROM hojas_ruta h2 JOIN bolsa b2 ON b2.id = h2.id_chofer
                    WHERE h2.id_tractivo = t.id
                    ORDER BY h2.fecha_emision DESC, h2.id DESC LIMIT 1) as nombrecompleto')
            ->get();

        $posY = 45;
        $i = 1;
        $max = 37;
        $tanque = 0.0;

        if ($data->isNotEmpty()) {
            $this->inicio($titulo, '', 50, 5);
            $this->titulos(10, 15, 35, $campos);
            foreach ($data as $arr) {
                $this->SetFont('Arial', '', 12);
                $this->SetXY(15, $posY);
                $this->SetFillColor(255, 255, 255);
                $this->Cell(25, 6, (string) $arr->codtractivo, 1, 0, 'C', 1);
                $this->Cell(30, 6, (string) $arr->chapa, 1, 0, 'C', 1);
                $this->Cell(80, 6, ucwords(mb_strtolower($this->txt((string) $arr->nombrecompleto))), 1, 0, 'L', 1);
                $this->Cell(30, 6, $this->txt((string) $arr->tipocombustibles), 1, 0, 'L', 1);
                $this->Cell(20, 6, $this->cambiarVariable($arr->captanque, 0), 1, 0, 'C', 1);
                $posY += 6;
                $i++;
                $tanque += (float) $arr->captanque;
                if ($i === $max) {
                    $this->inicio($titulo, '', 50, 5);
                    $this->titulos(10, 15, 35, $campos);
                    $posY = 45;
                    $i = 1;
                }
            }
            $this->SetFont('Arial', 'B', 14);
            $this->SetXY(15, $posY);
            $this->SetFillColor(200, 200, 200);
            $this->Cell(165, 10, 'TOTALES', 1, 0, 'C', 1);
            $this->Cell(20, 10, $this->cambiarVariable($tanque, 0), 1, 0, 'C', 1);
        } else {
            $this->inicio($titulo, '', 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
        }

        return $this->salida($titulo.'.pdf');
    }

    // =====================================================================
    // 40 / 42 — SUBMAYOR TARJETAS COMBUSTIBLE (todas / detalle)
    // =====================================================================

    public function pdfSubmayorTodas(?string $mes): \Illuminate\Http\Response
    {
        [$anio, $mesNum] = $this->anioMes($mes);

        $ids = DB::table('tarjetas')
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('id_entidad', $this->entidadIds))
            ->whereNull('fcancelado')
            ->orderBy('numero')
            ->pluck('id')
            ->all();

        if (empty($ids)) {
            $this->paginaVacia('SUBMAYOR TARJETA MAGNETICA', $mesNum);

            return $this->salida('SUBMAYOR TARJETAS.pdf');
        }

        foreach ($ids as $id) {
            $this->renderSubmayorTarjeta((int) $id, $anio, $mesNum);
        }

        return $this->salida('SUBMAYOR TARJETAS.pdf');
    }

    public function pdfSubmayor(?int $idTarjeta, ?string $mes): \Illuminate\Http\Response
    {
        [$anio, $mesNum] = $this->anioMes($mes);

        if (! $idTarjeta) {
            $this->paginaVacia('SUBMAYOR TARJETA MAGNETICA', $mesNum);

            return $this->salida('SUBMAYOR TARJETA.pdf');
        }

        $this->renderSubmayorTarjeta($idTarjeta, $anio, $mesNum);

        return $this->salida('SUBMAYOR TARJETA.pdf');
    }

    private function renderSubmayorTarjeta(int $idTarjeta, int $anio, int $mes): void
    {
        $tarjeta = DB::table('tarjetas as ta')
            ->leftJoin('monedas as mo', 'mo.id', '=', 'ta.idmonedas')
            ->where('ta.id', $idTarjeta)
            ->selectRaw('ta.numero as codtm, ta.idtipocombustibles, mo.nombre as moneda,
                COALESCE(ta.saldoinicialmon,0) as saldoinicialmon, COALESCE(ta.saldoiniciallts,0) as saldoiniciallts,
                COALESCE(ta.saldo_actual,0) as saldoactualmon, COALESCE(ta.saldoactuallts,0) as saldoactuallts')
            ->first();

        if (! $tarjeta) {
            return;
        }

        $combustible = $this->combustibleNombre($tarjeta->idtipocombustibles);
        $titulo = 'SUBMAYOR TARJETA MAGNETICA : '.$tarjeta->codtm.'  '.$combustible.' '.($tarjeta->moneda ?? '');

        // Filas: cargas (asignaciones) + descargas (consumos), ordenadas por día.
        $arr = [];
        foreach ($this->detalleCargaTarjeta($idTarjeta, $anio, $mes) as $c) {
            $arr[] = (object) [
                'fecha' => $c->fcarga,
                'referencia' => $c->folio,
                'asignacionmon' => $c->saldomon,
                'asignacionlts' => $c->saldolts,
                'consumomon' => '',
                'consumolts' => '',
                'hr' => '',
                'equipo' => '',
                'prueba' => (int) substr((string) $c->fcarga, 8, 2),
            ];
        }
        foreach ($this->detalleDescargaTarjeta($idTarjeta, $anio, $mes) as $d) {
            $fecha = $d->f_chip ?: $d->fdescarga;
            $arr[] = (object) [
                'fecha' => $fecha,
                'referencia' => $d->folio,
                'asignacionmon' => '',
                'asignacionlts' => '',
                'consumomon' => $d->saldomon,
                'consumolts' => $d->saldolts,
                'hr' => $d->nrohr,
                'equipo' => $d->codtractivo,
                'prueba' => (int) substr((string) $fecha, 8, 2) + 1,
            ];
        }
        usort($arr, fn ($a, $b) => $a->prueba <=> $b->prueba);

        $saldomon = (float) $tarjeta->saldoinicialmon;
        $saldolts = (float) $tarjeta->saldoiniciallts;
        foreach ($arr as $row) {
            $saldomon = $saldomon + (float) $row->asignacionmon - (float) $row->consumomon;
            $saldolts = $saldolts + (float) $row->asignacionlts - (float) $row->consumolts;
            $row->existenciamon = $saldomon;
            $row->existencialts = $saldolts;
        }

        $this->inicio($titulo);
        $this->submayorEncabezado();

        $posY = 47;
        $this->SetFont('Arial', 'B', 13);
        $this->SetXY(15, $posY);
        $this->SetFillColor(200, 200, 200);
        $this->Cell(150, 10, 'SALDO INICIAL', 1, 0, 'C', 1);
        $this->Cell(25, 10, $this->cambiarVariable($tarjeta->saldoinicialmon, 2), 1, 0, 'C', 1);
        $this->Cell(25, 10, $this->cambiarVariable($tarjeta->saldoiniciallts, 2), 1, 0, 'C', 1);
        $this->Cell(60, 10, '', 1, 0, 'L', 1);
        $posY += 10;

        $asignacionmon = 0.0;
        $asignacionlts = 0.0;
        $consumomon = 0.0;
        $consumolts = 0.0;
        $max = 18;

        foreach ($arr as $a => $row) {
            $this->SetFont('Arial', '', 12);
            $this->SetXY(15, $posY);
            $this->SetFillColor(255, 255, 255);
            $this->Cell(25, 6, (string) $row->fecha, 1, 0, 'C', 1);
            $this->Cell(25, 6, (string) $row->referencia, 1, 0, 'C', 1);
            $this->Cell(25, 6, $this->cambiarVariable($row->asignacionmon, 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->cambiarVariable($row->asignacionlts, 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->cambiarVariable($row->consumomon, 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->cambiarVariable($row->consumolts, 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->cambiarVariable($row->existenciamon, 2), 1, 0, 'C', 1);
            $this->Cell(25, 6, $this->cambiarVariable($row->existencialts, 2), 1, 0, 'C', 1);
            $this->Cell(20, 6, (string) $row->hr, 1, 0, 'L', 1);
            $this->Cell(40, 6, (string) $row->equipo, 1, 0, 'L', 1);
            $posY += 6;
            $asignacionmon += (float) $row->asignacionmon;
            $asignacionlts += (float) $row->asignacionlts;
            $consumomon += (float) $row->consumomon;
            $consumolts += (float) $row->consumolts;

            if ($a === $max) {
                $this->inicio($titulo);
                $this->submayorEncabezado();
                $posY = 47;
            }
        }

        $this->SetFont('Arial', 'B', 13);
        $this->SetXY(15, $posY);
        $this->SetFillColor(200, 200, 200);
        $this->Cell(50, 10, 'SALDO FINAL', 1, 0, 'C', 1);
        $this->Cell(25, 10, $this->cambiarVariable($asignacionmon, 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->cambiarVariable($asignacionlts, 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->cambiarVariable($consumomon, 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->cambiarVariable($consumolts, 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->cambiarVariable($tarjeta->saldoactualmon, 2), 1, 0, 'C', 1);
        $this->Cell(25, 10, $this->cambiarVariable($tarjeta->saldoactuallts, 2), 1, 0, 'C', 1);
        $this->Cell(20, 10, '', 1, 0, 'L', 1);
        $this->Cell(40, 10, '', 1, 0, 'L', 1);
    }

    private function submayorEncabezado(): void
    {
        $this->SetFont('Arial', 'B', 11);
        $this->SetXY(15, 35);
        $this->SetFillColor(200, 200, 200);
        $this->Cell(25, 6, 'FECHA', 'LTR', 0, 'C', 1);
        $this->Cell(25, 6, 'REFER', 'LTR', 0, 'C', 1);
        $this->Cell(50, 6, 'ASIGNACION ', 1, 0, 'C', 1);
        $this->Cell(50, 6, 'CONSUMO', 1, 0, 'C', 1);
        $this->Cell(50, 6, 'EXISTENCIA', 1, 0, 'C', 1);
        $this->Cell(20, 6, 'AREA', 'LTR', 0, 'C', 1);
        $this->Cell(40, 6, 'OBSERVACIONES', 'LTR', 0, 'C', 1);

        $this->SetXY(15, 41);
        $this->SetFillColor(200, 200, 200);
        $this->Cell(25, 6, '', 'LRB', 0, 'C', 1);
        $this->Cell(25, 6, '', 'LRB', 0, 'C', 1);
        $this->Cell(25, 6, 'IMPORTE ', 1, 0, 'C', 1);
        $this->Cell(25, 6, 'LITROS ', 1, 0, 'C', 1);
        $this->Cell(25, 6, 'IMPORTE ', 1, 0, 'C', 1);
        $this->Cell(25, 6, 'LITROS ', 1, 0, 'C', 1);
        $this->Cell(25, 6, 'IMPORTE ', 1, 0, 'C', 1);
        $this->Cell(25, 6, 'LITROS ', 1, 0, 'C', 1);
        $this->Cell(20, 6, '', 'LRB', 0, 'C', 1);
        $this->Cell(40, 6, '', 'LRB', 0, 'C', 1);
    }

    private function detalleCargaTarjeta(int $idTarjeta, int $anio, int $mes)
    {
        return DB::table('detalles_carga_combustible as dc')
            ->where('dc.id_tarjeta', $idTarjeta)
            ->whereYear('dc.fcarga', $anio)
            ->when($mes > 0, fn ($q) => $q->whereMonth('dc.fcarga', $mes))
            ->orderBy('dc.fcarga')
            ->selectRaw('dc.fcarga, dc.folio, COALESCE(dc.saldo_mon,0) as saldomon, COALESCE(dc.saldo_lts,0) as saldolts')
            ->get();
    }

    private function detalleDescargaTarjeta(int $idTarjeta, int $anio, int $mes)
    {
        return DB::table('combustible_descargas as d')
            ->leftJoin('hojas_ruta as h', 'h.id', '=', 'd.id_hoja_ruta')
            ->leftJoin('tractivos as t', 't.id', '=', 'd.id_tractivo')
            ->where('d.id_tarjeta', $idTarjeta)
            ->whereYear('d.fdescarga', $anio)
            ->when($mes > 0, fn ($q) => $q->whereMonth('d.fdescarga', $mes))
            ->orderBy('d.fdescarga')
            ->selectRaw('d.fdescarga, d.f_chip, d.folio,
                COALESCE(d.saldo_mon,0) as saldomon, COALESCE(d.saldo_lts,0) as saldolts,
                h.numero as nrohr, t.codigo as codtractivo')
            ->get();
    }

    private function combustibleNombre($legacyId): string
    {
        return (string) (DB::table('catalogo_items')
            ->where('tipo', 'tipos_combustibles')
            ->where('origen_id', $legacyId)
            ->value('nombre') ?? '');
    }

    private function paginaVacia(string $titulo, int $mesNum): void
    {
        $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
        $this->SetFont('Arial', 'B', 25);
        $this->SetXY(10, 65);
        $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
    }

    // =====================================================================
    // 113 — RESUMEN DESCARGAS X VARIABLES
    // =====================================================================

    public function pdfCombustibleResumen(?string $mes, ?string $tipo): \Illuminate\Http\Response
    {
        [$anio, $mesNum] = $this->anioMes($mes);
        $cfg = $this->cfgResumenDescargas($tipo);
        $titulo = 'RESUMEN DESCARGAS X '.$cfg['label'].' '.$this->combustibleNombre(14).' '.$this->monedaNombre(1);

        $campos = [
            ['titulo' => 'NRO', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => $cfg['label'], 'ancho' => $cfg['ancho'], 'direccion' => 'C'],
            ['titulo' => 'CANTIDAD $', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'CANTIDAD LTS', 'ancho' => 30, 'direccion' => 'C'],
        ];

        $data = $this->resumenDescargas($anio, $mesNum, $cfg);

        $posY = 36;
        $i = 1;
        $nro = 1;
        $max = 37;
        $saldomon = 0.0;
        $saldolts = 0.0;

        if ($data->isNotEmpty()) {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->titulos(6, 15, 30, $campos);
            foreach ($data as $arr) {
                $this->SetFont('Arial', 'B', 18);
                $this->SetXY(15, $posY);
                $this->SetFillColor(255, 255, 255);
                $this->Cell(15, 6, (string) $nro, 1, 0, 'C', 1);
                $this->SetFont('Arial', 'U', 14);
                $this->Cell($cfg['ancho'], 6, $this->txt((string) $arr->campo), 1, 0, 'L', 1);
                $this->SetFont('Arial', 'B', 14);
                $this->Cell(30, 6, $this->cambiarVariable($arr->saldomon, 2), 1, 0, 'R', 1);
                $this->Cell(30, 6, $this->cambiarVariable($arr->saldolts, 2), 1, 0, 'R', 1);
                $saldomon += (float) $arr->saldomon;
                $saldolts += (float) $arr->saldolts;
                $posY += 6;
                $nro++;
                $i++;
                if ($i === $max) {
                    $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                    $this->titulos(6, 15, 30, $campos);
                    $posY = 36;
                    $i = 0;
                }
            }
            $this->SetFont('Arial', 'B', 16);
            $this->SetXY(15, $posY);
            $this->SetFillColor(200, 200, 200);
            $this->Cell($cfg['ancho'] + 15, 10, 'TOTAL', 1, 0, 'L', 1);
            $this->Cell(30, 10, $this->cambiarVariable($saldomon, 2), 1, 0, 'R', 1);
            $this->Cell(30, 10, $this->cambiarVariable($saldolts, 2), 1, 0, 'R', 1);
        } else {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
        }

        return $this->salida($titulo.'.pdf');
    }

    // =====================================================================
    // 114 — DESCARGA COMBUSTIBLE POR GRUPO-FECHAS
    // =====================================================================

    public function pdfDescargasGrupoFechas(?string $mes): \Illuminate\Http\Response
    {
        $titulo = 'DESCARGA COMBUSTIBLE POR GRUPO-FECHAS';
        [$anio, $mesNum] = $this->anioMes($mes);

        $campos = [
            ['titulo' => 'FECHA', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'PRODUCTIVO', 'ancho' => 35, 'direccion' => 'C'],
            ['titulo' => 'ADMINISTRATIVO', 'ancho' => 35, 'direccion' => 'C'],
            ['titulo' => 'APOYO', 'ancho' => 35, 'direccion' => 'C'],
            ['titulo' => 'TECNOLOGICO', 'ancho' => 35, 'direccion' => 'C'],
            ['titulo' => 'TOTAL', 'ancho' => 35, 'direccion' => 'C'],
        ];

        $entidadSistema = (int) ($this->entidad->id_sistema ?? 0);
        $gTecnologico = $entidadSistema === 0 ? 9 : 4;

        $dias = (int) date('t', strtotime(sprintf('%04d-%02d-01', $anio, $mesNum)));

        $posY = 45;
        $i = 0;
        $max = 35;
        $tTransp = 0.0;
        $tAdmin = 0.0;
        $tApoyo = 0.0;
        $tTec = 0.0;
        $tTotal = 0.0;

        $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
        $this->titulos(10, 15, 35, $campos);

        for ($d = 1; $d <= $dias; $d++) {
            $totalDia = $this->descargasGrupoDia($anio, $mesNum, $d, 0);
            if ($totalDia <= 0) {
                continue;
            }
            $transp = $this->descargasGrupoDia($anio, $mesNum, $d, 1);
            $admin = $this->descargasGrupoDia($anio, $mesNum, $d, 7);
            $apoyo = $this->descargasGrupoDia($anio, $mesNum, $d, 3);
            $tec = $this->descargasGrupoDia($anio, $mesNum, $d, $gTecnologico);
            $total = $transp + $admin + $apoyo + $tec;

            $this->SetFont('Arial', 'B', 18);
            $this->SetXY(15, $posY);
            $this->SetFillColor(255, 255, 255);
            $this->Cell(20, 6, (string) $d, 1, 0, 'C', 1);
            $this->Cell(35, 6, $this->cambiarVariable($transp, 2), 1, 0, 'R', 1);
            $this->Cell(35, 6, $this->cambiarVariable($admin, 2), 1, 0, 'R', 1);
            $this->Cell(35, 6, $this->cambiarVariable($apoyo, 2), 1, 0, 'R', 1);
            $this->Cell(35, 6, $this->cambiarVariable($tec, 2), 1, 0, 'R', 1);
            $this->Cell(35, 6, $this->cambiarVariable($total, 2), 1, 0, 'R', 1);
            $posY += 6;
            $i++;
            $tTransp += $transp;
            $tAdmin += $admin;
            $tApoyo += $apoyo;
            $tTec += $tec;
            $tTotal += $total;

            if ($i === $max) {
                $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                $this->titulos(10, 15, 35, $campos);
                $posY = 45;
                $i = 0;
            }
        }

        $this->SetFont('Arial', 'B', 20);
        $this->SetXY(15, $posY);
        $this->SetFillColor(200, 200, 200);
        $this->Cell(20, 10, '', 1, 0, 'L', 1);
        $this->Cell(35, 10, $this->cambiarVariable($tTransp, 2), 1, 0, 'R', 1);
        $this->Cell(35, 10, $this->cambiarVariable($tAdmin, 2), 1, 0, 'R', 1);
        $this->Cell(35, 10, $this->cambiarVariable($tApoyo, 2), 1, 0, 'R', 1);
        $this->Cell(35, 10, $this->cambiarVariable($tTec, 2), 1, 0, 'R', 1);
        $this->Cell(35, 10, $this->cambiarVariable($tTotal, 2), 1, 0, 'R', 1);

        return $this->salida($titulo.'.pdf');
    }

    private function cfgResumenDescargas(?string $tipo): array
    {
        return match ($tipo) {
            'fchip' => ['key' => 'fchip', 'label' => 'F/CHIP', 'ancho' => 40],
            'codtractivo', 'equipos' => ['key' => 'tractivo', 'label' => 'TRACTIVO', 'ancho' => 45],
            'grupo' => ['key' => 'grupo', 'label' => 'GRUPO', 'ancho' => 60],
            'chofer' => ['key' => 'chofer', 'label' => 'CHOFERES', 'ancho' => 40],
            'moneda' => ['key' => 'moneda', 'label' => 'MONEDA', 'ancho' => 40],
            'tipocomb' => ['key' => 'tipocomb', 'label' => 'TIPO COMBUSTIBLE', 'ancho' => 40],
            default => ['key' => 'fdescarga', 'label' => 'F/PARTE', 'ancho' => 40],
        };
    }

    private function resumenDescargas(int $anio, int $mes, array $cfg)
    {
        $base = DB::table('combustible_descargas as d')
            ->join('tractivos as t', 't.id', '=', 'd.id_tractivo')
            ->join('tarjetas as ta', 'ta.id', '=', 'd.id_tarjeta')
            ->leftJoin('monedas as mo', 'mo.id', '=', 'ta.idmonedas')
            ->leftJoin('catalogo_items as ci', function ($j) {
                $j->on('ci.origen_id', '=', 'ta.idtipocombustibles')
                    ->where('ci.tipo', 'tipos_combustibles');
            })
            ->leftJoin('hojas_ruta as h', 'h.id', '=', 'd.id_hoja_ruta')
            ->leftJoin('catalogo_items as g', 'g.id', '=', 'h.id_grupo')
            ->leftJoin('bolsa as b', 'b.id', '=', 'd.id_empleado')
            ->where('d.id_tractivo', '!=', 0)
            ->where('ta.idmonedas', 1)
            ->where('ta.idtipocombustibles', 14)
            ->whereYear('d.fdescarga', $anio)
            ->when($mes > 0, fn ($q) => $q->whereMonth('d.fdescarga', $mes))
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('t.id_entidad', $this->entidadIds));

        $campo = match ($cfg['key']) {
            'fchip' => 'd.f_chip',
            'tractivo' => 't.codigo',
            'grupo' => 'g.nombre',
            'chofer' => 'b.nombre',
            'moneda' => 'mo.nombre',
            'tipocomb' => 'ci.nombre',
            default => 'd.fdescarga',
        };

        if ($cfg['key'] === 'tractivo') {
            $base->whereIn('h.id_grupo', [
                \App\Support\Catalogos::idDe('grupos', 1),
                \App\Support\Catalogos::idDe('grupos', 4),
            ])->where('t.codigo', '!=', '8-03');
        }

        return $base
            ->orderBy($campo)
            ->groupBy($campo)
            ->selectRaw("$campo as campo, SUM(d.saldo_mon) as saldomon, SUM(d.saldo_lts) as saldolts")
            ->get();
    }

    private function descargasGrupoDia(int $anio, int $mes, int $dia, int $legacyGrupo): float
    {
        $idGrupo = $legacyGrupo > 0 ? \App\Support\Catalogos::idDe('grupos', $legacyGrupo) : null;

        return (float) DB::table('combustible_descargas as d')
            ->join('tractivos as t', 't.id', '=', 'd.id_tractivo')
            ->whereYear('d.fdescarga', $anio)
            ->whereMonth('d.fdescarga', $mes)
            ->whereDay('d.fdescarga', $dia)
            ->when($idGrupo, fn ($q) => $q->where('t.id_grupo', $idGrupo))
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('t.id_entidad', $this->entidadIds))
            ->sum('d.saldo_lts');
    }

    private function monedaNombre($id): string
    {
        return (string) (DB::table('monedas')->where('id', $id)->value('nombre') ?? '');
    }

    // =====================================================================
    // 1020 / 1021 — ANALISIS DEL COMPORTAMIENTO DE LAS TARJETAS (ONURE)
    // =====================================================================

    public function pdfOnureSubmayorTodas(?string $mes): \Illuminate\Http\Response
    {
        [$anio, $mesNum] = $this->anioMes($mes);

        $ids = DB::table('tarjetas')
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('id_entidad', $this->entidadIds))
            ->whereNull('fcancelado')
            ->orderBy('numero')
            ->pluck('id')
            ->all();

        if (empty($ids)) {
            $this->paginaVacia('ANALISIS DEL COMPORTAMIENTO DE LAS TARJETAS MAGNETICAS', $mesNum);

            return $this->salida('ANALISIS TARJETAS ONURE.pdf');
        }

        foreach ($ids as $id) {
            $this->renderOnureSubmayor((int) $id, $anio, $mesNum);
        }

        return $this->salida('ANALISIS TARJETAS ONURE.pdf');
    }

    public function pdfOnureSubmayor(?int $idTarjeta, ?string $mes): \Illuminate\Http\Response
    {
        [$anio, $mesNum] = $this->anioMes($mes);

        if (! $idTarjeta) {
            $this->paginaVacia('ANALISIS DEL COMPORTAMIENTO DE LAS TARJETAS MAGNETICAS', $mesNum);

            return $this->salida('ANALISIS TARJETA ONURE.pdf');
        }

        $this->renderOnureSubmayor($idTarjeta, $anio, $mesNum);

        return $this->salida('ANALISIS TARJETA ONURE.pdf');
    }

    private function renderOnureSubmayor(int $idTarjeta, int $anio, int $mes): void
    {
        $tarjeta = DB::table('tarjetas as ta')
            ->leftJoin('monedas as mo', 'mo.id', '=', 'ta.idmonedas')
            ->leftJoin('tipos_combustibles as tc', 'tc.id', '=', 'ta.idtipocombustibles')
            ->where('ta.id', $idTarjeta)
            ->selectRaw('ta.numero as codtm, ta.idtipocombustibles, mo.nombre as moneda,
                tc.nombre as combustible, tc.preciomn as precio,
                COALESCE(ta.saldoinicialmon,0) as saldoinicialmon, COALESCE(ta.saldoiniciallts,0) as saldoiniciallts,
                COALESCE(ta.saldo_actual,0) as saldoactualmon, COALESCE(ta.saldoactuallts,0) as saldoactuallts')
            ->first();

        if (! $tarjeta) {
            return;
        }

        $titulo = 'ANALISIS DEL COMPORTAMIENTO DE LAS TARJETAS MAGNETICAS';

        $campos = [
            ['titulo' => 'FECHA', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 7],
            ['titulo' => 'HORA', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'COMERCIO', 'ancho' => 50, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 10],
            ['titulo' => 'ZONA', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'SERVICIO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'OPERACIONES EN IMPORTE', 'ancho' => 60, 'direccion' => 'C'],
            ['titulo' => 'OPERACIONES EN LITROS', 'ancho' => 60, 'direccion' => 'C'],
            ['titulo' => 'ASIGNADO AL ', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 5],
            ['titulo' => 'INDICE', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'RESPONSABLE', 'ancho' => 40, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 10],
            ['titulo' => 'CAPACIDAD ', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => 6],
            ['titulo' => 'NIVEL DE  ', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR'],
        ];
        $campos1 = [
            ['titulo' => '', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 7],
            ['titulo' => '', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 50, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'SALDO INICIAL', 'ancho' => 20, 'direccion' => 'C', 'letra' => 6],
            ['titulo' => 'IMPORTE', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'SALDO FINAL', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'LITROS INICIALES', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'CONSUMO O CARGA', 'ancho' => 20, 'direccion' => 'C', 'letra' => 5],
            ['titulo' => 'LITROS FINALES', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'AL EQUIPO', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB', 'letra' => 6],
            ['titulo' => 'PLAN', 'ancho' => 10, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 40, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'TANQUE', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'ACTIVIDAD', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB'],
        ];

        // Filas: cargas (asignaciones) + descargas (consumos).
        $arr = [];
        $idsistema = (int) ($this->entidad->id_sistema ?? 0);
        $provincia = $this->provinciaNombre($this->entidad->id_provincia ?? null);

        foreach ($this->detalleCargaTarjeta($idTarjeta, $anio, $mes) as $c) {
            $arr[] = (object) [
                'fecha' => $c->fcarga, 'hora' => '',
                'comercio' => $idsistema === 0 ? 'CARG.CHIP EMCARGA'.$provincia : '',
                'zona' => $idsistema === 0 ? $provincia.', CUBA' : '',
                'servicio' => 'Carga',
                'asignacionmon' => $c->saldomon, 'asignacionlts' => $c->saldolts,
                'consumomon' => '', 'consumolts' => '',
                'equipo' => '', 'indice' => '', 'chofer' => '', 'tanque' => '', 'kms' => '',
                'prueba' => (int) substr((string) $c->fcarga, 8, 2) - 1,
            ];
        }
        foreach ($this->detalleDescargaTarjetaOnure($idTarjeta, $anio, $mes) as $d) {
            $arr[] = (object) [
                'fecha' => $d->fdescarga, 'hora' => $d->hdescarga,
                'comercio' => $d->servicentros, 'zona' => $d->provincia,
                'servicio' => $tarjeta->combustible.' Autof.',
                'asignacionmon' => '', 'asignacionlts' => '',
                'consumomon' => $d->saldomon, 'consumolts' => $d->saldolts,
                'equipo' => $d->codtractivo, 'indice' => $d->indice,
                'chofer' => $d->nombrecompleto, 'tanque' => $d->captanque, 'kms' => $d->kms,
                'prueba' => (int) substr((string) $d->fdescarga, 8, 2),
            ];
        }
        usort($arr, fn ($a, $b) => $a->prueba <=> $b->prueba);

        $this->inicio($titulo, sprintf('%02d', $mes), 50, 5);
        $this->onureEncabezado($campos, $campos1, $tarjeta, $idsistema, $provincia);

        $posY = 54;
        $max = 18;
        $saldoMon = (float) $tarjeta->saldoinicialmon;
        $saldoLts = (float) $tarjeta->saldoiniciallts;

        foreach ($arr as $a => $row) {
            $this->SetFont('Arial', '', 7);
            $this->SetXY(10, $posY);
            $this->SetFillColor(255, 255, 255);
            $this->Cell(15, 6, (string) $row->fecha, 1, 0, 'C', 1);
            $this->Cell(10, 6, (string) $row->hora, 1, 0, 'C', 1);
            $this->SetFont('Arial', '', 6);
            $this->Cell(50, 6, $this->txt((string) $row->comercio), 1, 0, 'L', 1);
            $this->Cell(20, 6, $this->txt((string) $row->zona), 1, 0, 'L', 1);
            $this->Cell(20, 6, $this->txt((string) $row->servicio), 1, 0, 'L', 1);
            $this->SetFont('Arial', '', 12);
            $this->Cell(20, 6, $this->cambiarVariable($saldoMon, 2), 1, 0, 'R', 1);
            if ((float) $row->asignacionmon > 0) {
                $this->Cell(20, 6, $this->cambiarVariable($row->asignacionmon, 2), 1, 0, 'R', 1);
                $saldoMon = round($saldoMon + (float) $row->asignacionmon, 2);
            } else {
                $this->Cell(20, 6, $this->cambiarVariable($row->consumomon, 2), 1, 0, 'R', 1);
                $saldoMon = round($saldoMon - (float) $row->consumomon, 2);
            }
            $this->Cell(20, 6, $this->cambiarVariable($saldoMon, 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->cambiarVariable($saldoLts, 2), 1, 0, 'R', 1);
            if ((float) $row->asignacionlts > 0) {
                $this->Cell(20, 6, $this->cambiarVariable($row->asignacionlts, 2), 1, 0, 'R', 1);
                $saldoLts = round($saldoLts + (float) $row->asignacionlts, 2);
            } else {
                $this->Cell(20, 6, $this->cambiarVariable($row->consumolts, 2), 1, 0, 'R', 1);
                $saldoLts = round($saldoLts - (float) $row->consumolts, 2);
            }
            $this->Cell(20, 6, $this->cambiarVariable($saldoLts, 2), 1, 0, 'R', 1);
            $this->SetFont('Arial', '', 10);
            $this->Cell(15, 6, $this->txt((string) $row->equipo), 1, 0, 'L', 1);
            $this->Cell(10, 6, $this->cambiarVariable($row->indice, 2), 1, 0, 'R', 1);
            $this->SetFont('Arial', '', 7);
            $this->Cell(40, 6, $this->txt((string) $row->chofer), 1, 0, 'L', 1);
            $this->SetFont('Arial', '', 10);
            $this->Cell(15, 6, $this->cambiarVariable($row->tanque, 2), 1, 0, 'R', 1);
            $this->Cell(15, 6, $this->cambiarVariable($row->kms, 2), 1, 0, 'R', 1);
            $posY += 6;

            if ($a === $max) {
                $this->inicio($titulo, sprintf('%02d', $mes), 50, 5);
                $this->onureEncabezado($campos, $campos1, $tarjeta, $idsistema, $provincia);
                $posY = 54;
            }
        }
    }

    private function onureEncabezado(array $campos, array $campos1, object $tarjeta, int $idsistema, string $provincia): void
    {
        $this->titulos(6, 10, 42, $campos1, $campos);

        $nombEntidad = (string) ($this->entidad->nombre ?? '');
        $empresa = match ($idsistema) {
            0 => 'ENOC',
            1 => 'EMCARGA',
            2 => 'ETMICONS',
            3 => 'EMP.PRODUCCION INDUSTRIAL',
            default => '',
        };
        $oace = in_array($idsistema, [0, 1], true) ? 'GEA' : '';

        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(255, 255, 255);
        $this->SetXY(10, 28);
        $this->Cell(80, 6, 'OACE: '.$oace, 0, 0, 'L', 1);
        $this->Cell(80, 6, 'EMPRESA: '.$empresa, 0, 0, 'L', 1);
        $this->Cell(80, 6, 'UEB: '.$nombEntidad, 0, 0, 'L', 1);

        $this->SetXY(10, 34);
        $this->Cell(80, 6, 'CLIENTE: '.(string) ($this->entidad->cliente_fincimex_mn ?? ''), 0, 0, 'L', 1);
        $this->Cell(80, 6, 'TARJETA: '.$tarjeta->codtm, 0, 0, 'L', 1);
        $this->Cell(80, 6, 'PRECIO:  '.$tarjeta->precio, 0, 0, 'L', 1);
        $this->Cell(80, 6, 'MONEDA:  '.$tarjeta->moneda, 0, 0, 'L', 1);
    }

    private function detalleDescargaTarjetaOnure(int $idTarjeta, int $anio, int $mes)
    {
        return DB::table('combustible_descargas as d')
            ->leftJoin('tractivos as t', 't.id', '=', 'd.id_tractivo')
            ->leftJoin('servicentros as s', 's.id', '=', 'd.id_servicentro')
            ->leftJoin('provincias as p', 'p.id', '=', 's.id_provincia')
            ->leftJoin('bolsa as b', 'b.id', '=', 'd.id_empleado')
            ->where('d.id_tarjeta', $idTarjeta)
            ->whereYear('d.fdescarga', $anio)
            ->when($mes > 0, fn ($q) => $q->whereMonth('d.fdescarga', $mes))
            ->orderBy('d.fdescarga')
            ->selectRaw('d.fdescarga, d.hora_descarga as hdescarga, s.nombre as servicentros, p.nombre as provincia,
                COALESCE(d.saldo_mon,0) as saldomon, COALESCE(d.saldo_lts,0) as saldolts,
                t.codigo as codtractivo, t.indice_consumo as indice, COALESCE(t.cap_deposito,0) as captanque,
                COALESCE(d.kms,0) as kms,
                TRIM(CONCAT(COALESCE(b.nombre,""), " ", COALESCE(b.apellidos,""))) as nombrecompleto')
            ->get();
    }

    private function provinciaNombre($id): string
    {
        return $id ? (string) (DB::table('provincias')->where('id', $id)->value('nombre') ?? '') : '';
    }

    // =====================================================================
    // 41 — DETALLES DESCARGAS P/TECNICA (LUBRICANTES Y GRASAS CT-7)
    // =====================================================================

    public function pdfDescargasTecnica(?string $fecha): \Illuminate\Http\Response
    {
        $titulo = 'LUBRICANTES Y GRASAS  CT-7';
        $fechaOps = $this->fechaOperaciones;

        // Si llega solo el día (2 dígitos), se compone con la fecha de operaciones.
        if (strlen((string) $fecha) === 2) {
            $fecha = substr_replace($fechaOps, (string) $fecha, 8, 2);
        }
        $fecha = $fecha ?: $fechaOps;
        $anio = (int) substr($fecha, 0, 4);
        $mesNum = (int) substr($fecha, 5, 2);

        $campos = [
            ['titulo' => 'TURNO NO', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'VEHIC.NO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'COMBUSTIBLE', 'ancho' => 50, 'direccion' => 'C'],
            ['titulo' => 'LUBRICANTES', 'ancho' => 60, 'direccion' => 'C'],
            ['titulo' => 'GRASAS', 'ancho' => 30, 'direccion' => 'C'],
        ];
        $campos1 = [
            ['titulo' => '', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'DIESEL', 'ancho' => 15, 'direccion' => 'C', 'letra' => 8],
            ['titulo' => 'GASOL', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'CANTIDAD', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'VEHIC', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'MOTOR', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'TRANSM', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'DIRECC', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'ROLLETE', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'COPILLA', 'ancho' => 15, 'direccion' => 'C'],
        ];

        $data = $this->descargaTecnicaDia($fecha, $anio, $mesNum);

        if ($data->isEmpty()) {
            $this->inicio($titulo, $fecha, 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');

            return $this->salida($titulo.'.pdf');
        }

        $posY = 47;
        $max = 220;
        $saldolitros = 0.0;

        $this->inicio($titulo, $fecha, 50, 5);
        $this->titulos(6, 15, 35, $campos1, $campos);
        $this->firmasAbastecedor();

        foreach ($data as $arr) {
            $this->SetFont('Arial', '', 12);
            $this->SetXY(15, $posY);
            $this->SetFillColor(255, 255, 255);
            $this->Cell(25, 6, '', 1, 0, 'C', 1);
            $this->Cell(20, 6, (string) $arr->codtractivo, 1, 0, 'L', 1);
            $this->Cell(15, 6, $this->cambiarVariable($arr->saldolts, 2), 1, 0, 'R', 1);
            $this->Cell(15, 6, substr((string) $arr->codtm, 3, 4), 1, 0, 'C', 1);
            $this->Cell(20, 6, (string) $arr->nrohr, 1, 0, 'C', 1);
            $this->Cell(15, 6, '', 1, 0, 'C', 1);
            $this->Cell(15, 6, '', 1, 0, 'C', 1);
            $this->Cell(15, 6, '', 1, 0, 'C', 1);
            $this->Cell(15, 6, '', 1, 0, 'C', 1);
            $this->Cell(15, 6, '', 1, 0, 'C', 1);
            $this->Cell(15, 6, '', 1, 0, 'C', 1);
            $posY += 6;
            $saldolitros += (float) $arr->saldolts;

            if ($posY >= $max) {
                $this->inicio($titulo, $fecha, 50, 5);
                $this->titulos(6, 15, 35, $campos1, $campos);
                $posY = 47;
                $this->firmasAbastecedor();
            }
        }

        $this->SetFont('Arial', 'B', 12);
        $this->SetXY(15, $posY);
        $this->SetFillColor(200, 200, 200);
        $this->Cell(45, 8, 'TOTAL GENERAL', 1, 0, 'C', 1);
        $this->Cell(140, 8, $this->cambiarVariable($saldolitros, 2), 1, 0, 'L', 1);

        return $this->salida($titulo.'.pdf');
    }

    private function firmasAbastecedor(): void
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(255, 255, 255);
        $this->SetXY(15, 240);
        $this->Cell(50, 5, ' ', 'B', 0, 'C', 1);
        $this->Cell(10, 5, ' ', 0, 0, 'C', 1);
        $this->Cell(50, 5, ' ', 'B', 0, 'C', 1);
        $this->Cell(10, 5, ' ', 0, 0, 'C', 1);
        $this->Cell(50, 5, ' ', 'B', 0, 'C', 1);
        $this->SetXY(15, 245);
        $this->Cell(50, 5, 'NOMBRE DEL ABASTECEDOR', 0, 0, 'L', 1);
        $this->Cell(10, 5, ' ', 0, 0, 'C', 1);
        $this->Cell(50, 5, 'NOMBRE DEL ENGRASADOR', 0, 0, 'L', 1);
        $this->Cell(10, 5, ' ', 0, 0, 'C', 1);
        $this->Cell(50, 5, 'NOMBRE JEFE DE TALLER', 0, 0, 'L', 1);
        $this->SetXY(15, 250);
        $this->Cell(50, 5, 'FIRMA', 0, 0, 'L', 1);
        $this->Cell(10, 5, ' ', 0, 0, 'C', 1);
        $this->Cell(50, 5, 'FIRMA', 0, 0, 'L', 1);
        $this->Cell(10, 5, ' ', 0, 0, 'C', 1);
        $this->Cell(50, 5, 'FIRMA', 0, 0, 'L', 1);
    }

    private function descargaTecnicaDia(string $fecha, int $anio, int $mes)
    {
        return DB::table('combustible_descargas as d')
            ->leftJoin('tarjetas as ta', 'ta.id', '=', 'd.id_tarjeta')
            ->leftJoin('hojas_ruta as h', 'h.id', '=', 'd.id_hoja_ruta')
            ->leftJoin('tractivos as t', 't.id', '=', 'd.id_tractivo')
            ->where('d.fdescarga', $fecha)
            ->whereYear('d.fdescarga', $anio)
            ->whereMonth('d.fdescarga', $mes)
            ->whereNotNull('d.id_tractivo')
            ->where('d.id_tractivo', '!=', 0)
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('t.id_entidad', $this->entidadIds))
            ->orderBy('d.fdescarga')->orderBy('t.codigo')->orderBy('h.numero')
            ->selectRaw('d.saldo_lts as saldolts, ta.numero as codtm, h.numero as nrohr, t.codigo as codtractivo')
            ->get();
    }

    // =====================================================================
    // 39 — RESUMEN CARGAS TARJETAS COMBUSTIBLE
    // =====================================================================

    public function pdfResumenCargas(?string $mes): \Illuminate\Http\Response
    {
        $titulo = 'RESUMEN CARGAS TARJETAS COMBUSTIBLE ';
        [$anio, $mesNum] = $this->anioMes($mes);

        $campos = [
            ['titulo' => 'NRO', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'FECHA', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'RESPONSABLE DE CARGA', 'ancho' => 60, 'direccion' => 'C'],
            ['titulo' => 'MONEDA', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'FOLIO', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'IMPORTE', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'LTS', 'ancho' => 25, 'direccion' => 'C'],
        ];

        $data = DB::table('combustible_cargas as c')
            ->leftJoin('monedas as mo', 'mo.id', '=', 'c.id_monedas')
            ->leftJoin('bolsa as b', 'b.id', '=', 'c.id_responsable')
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('c.id_entidad', $this->entidadIds))
            ->whereYear('c.fcarga', $anio)
            ->when($mesNum > 0, fn ($q) => $q->whereMonth('c.fcarga', $mesNum))
            ->orderBy('c.fcarga')->orderBy('c.folio')
            ->selectRaw('c.id, c.fcarga, c.folio, COALESCE(c.saldocargado,0) as saldocargado, mo.nombre as monedas,
                TRIM(CONCAT(COALESCE(b.nombre,""), " ", COALESCE(b.apellidos,""))) as nombrecompleto,
                (SELECT COALESCE(SUM(dc.saldo_lts),0) FROM detalles_carga_combustible dc WHERE dc.id_carga = c.id) as saldolts')
            ->get();

        $posY = 45;
        $i = 1;
        $nro = 1;
        $max = 25;
        $totalMon = 0.0;
        $totalLts = 0.0;

        if ($data->isNotEmpty()) {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->titulos(10, 15, 35, $campos);
            foreach ($data as $arr) {
                $this->SetFont('Arial', '', 11);
                $this->SetXY(15, $posY);
                $this->SetFillColor(255, 255, 255);
                $this->Cell(15, 8, (string) $nro, 1, 0, 'C', 1);
                $this->SetFont('Arial', 'U', 12);
                $this->Cell(25, 8, (string) $arr->fcarga, 1, 0, 'L', 1);
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(60, 8, ucwords(mb_strtolower($this->txt((string) $arr->nombrecompleto))), 1, 0, 'L', 1);
                $this->SetFont('Arial', '', 14);
                $this->Cell(20, 8, (string) $arr->monedas, 1, 0, 'C', 1);
                $this->Cell(20, 8, (string) $arr->folio, 1, 0, 'C', 1);
                $this->Cell(25, 8, $this->cambiarVariable($arr->saldocargado, 2), 1, 0, 'R', 1);
                $this->Cell(25, 8, $this->cambiarVariable($arr->saldolts, 2), 1, 0, 'R', 1);
                $totalMon += (float) $arr->saldocargado;
                $totalLts += (float) $arr->saldolts;
                $posY += 8;
                $nro++;
                $i++;
                if ($i === $max) {
                    $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                    $this->titulos(10, 15, 35, $campos);
                    $posY = 45;
                    $i = 0;
                }
            }
            $this->SetFont('Arial', 'B', 14);
            $this->SetXY(15, $posY);
            $this->SetFillColor(200, 200, 200);
            $this->Cell(140, 10, 'TOTAL', 1, 0, 'L', 1);
            $this->Cell(25, 10, $this->cambiarVariable($totalMon, 2), 1, 0, 'R', 1);
            $this->Cell(25, 10, $this->cambiarVariable($totalLts, 2), 1, 0, 'R', 1);
        } else {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
        }

        return $this->salida($titulo.'.pdf');
    }

    // =====================================================================
    // 23 — CONCILIACION COMBUSTIBLE (HABILITADO HR vs CONTABILIDAD)
    // =====================================================================

    public function pdfCombustibleConciliacion(?string $mes): \Illuminate\Http\Response
    {
        [$anio, $mesNum] = $this->anioMes($mes);

        $this->renderConciliacion('CONCILIACION COMBUSTIBLE(HABILITADO SEGUN HOJA DE RUTA)', $this->conciliacionPorHr($anio, $mesNum), $mesNum);
        $this->renderConciliacion('CONCILIACION COMBUSTIBLE(HABILITADO SEGUN CONTABILIDAD)', $this->conciliacionPorCont($anio, $mesNum), $mesNum);

        return $this->salida('CONCILIACION COMBUSTIBLE.pdf');
    }

    private function renderConciliacion(string $titulo, $data, int $mesNum): void
    {
        $campos = [
            ['titulo' => 'NRO', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'HOJA RUTA', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'EQUIPO', 'ancho' => 35, 'direccion' => 'C'],
            ['titulo' => 'CONTABILIDAD', 'ancho' => 35, 'direccion' => 'C'],
            ['titulo' => 'OPERACIONES', 'ancho' => 35, 'direccion' => 'C'],
            ['titulo' => 'DIFERENCIA', 'ancho' => 35, 'direccion' => 'C'],
        ];

        $posY = 45;
        $i = 1;
        $nro = 1;
        $max = 35;
        $saldolts = 0.0;
        $combHab = 0.0;

        if ($data->isEmpty()) {
            $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');

            return;
        }

        $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
        $this->titulos(10, 15, 35, $campos);
        foreach ($data as $obj) {
            $this->SetFont('Arial', '', 14);
            $this->SetXY(15, $posY);
            $this->SetFillColor(255, 255, 255);
            $this->Cell(15, 6, (string) $nro, 1, 0, 'C', 1);
            $this->Cell(25, 6, (string) $obj->nrohr, 1, 0, 'C', 1);
            $this->Cell(35, 6, (string) $obj->codtractivo, 1, 0, 'C', 1);
            $this->Cell(35, 6, $this->cambiarVariable($obj->saldolts, 2), 1, 0, 'R', 1);
            $this->Cell(35, 6, $this->cambiarVariable($obj->comb_hab, 2), 1, 0, 'R', 1);
            $this->Cell(35, 6, $this->cambiarVariable($obj->diferencia, 2), 1, 0, 'R', 1);
            $posY += 6;
            $nro++;
            $i++;
            $saldolts += (float) $obj->saldolts;
            $combHab += (float) $obj->comb_hab;
            if ($i === $max) {
                $this->inicio($titulo, sprintf('%02d', $mesNum), 50, 5);
                $this->titulos(10, 15, 35, $campos);
                $posY = 45;
                $i = 0;
            }
        }
        $this->SetFont('Arial', '', 14);
        $this->SetXY(15, $posY);
        $this->SetFillColor(200, 200, 200);
        $this->Cell(75, 10, 'TOTALES', 1, 0, 'C', 1);
        $this->Cell(35, 10, $this->cambiarVariable($saldolts, 2), 1, 0, 'R', 1);
        $this->Cell(35, 10, $this->cambiarVariable($combHab, 2), 1, 0, 'R', 1);
        $this->Cell(35, 10, $this->cambiarVariable(round($combHab - $saldolts, 2), 2), 1, 0, 'R', 1);
    }

    private function conciliacionPorHr(int $anio, int $mes)
    {
        return DB::table('hojas_ruta as h')
            ->leftJoin('tractivos as t', 't.id', '=', 'h.id_tractivo')
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('t.id_entidad', $this->entidadIds))
            ->whereYear('h.fecha_cierre', $anio)
            ->when($mes > 0, fn ($q) => $q->whereMonth('h.fecha_cierre', $mes))
            ->groupBy('h.id', 'h.numero', 't.codigo', 'h.combustible_habilitado', 'h.combustible_tecnico')
            ->orderBy('t.codigo')->orderBy('h.numero')
            ->selectRaw('h.numero as nrohr, t.codigo as codtractivo,
                (COALESCE(h.combustible_habilitado,0)+COALESCE(h.combustible_tecnico,0)) as comb_hab,
                (SELECT COALESCE(SUM(d.saldo_lts),0) FROM combustible_descargas d WHERE d.id_hoja_ruta = h.id) as saldolts')
            ->get()
            ->map(function ($o) {
                $o->diferencia = abs(round((float) $o->saldolts - (float) $o->comb_hab, 2));

                return $o;
            })
            ->filter(fn ($o) => $o->diferencia != 0)
            ->values();
    }

    private function conciliacionPorCont(int $anio, int $mes)
    {
        return DB::table('combustible_descargas as d')
            ->join('hojas_ruta as h', 'h.id', '=', 'd.id_hoja_ruta')
            ->leftJoin('tractivos as t', 't.id', '=', 'd.id_tractivo')
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('t.id_entidad', $this->entidadIds))
            ->whereYear('d.fdescarga', $anio)
            ->when($mes > 0, fn ($q) => $q->whereMonth('d.fdescarga', $mes))
            ->groupBy('h.id', 'h.numero', 't.codigo', 'h.combustible_habilitado', 'h.combustible_tecnico')
            ->orderBy('t.codigo')->orderBy('h.numero')
            ->selectRaw('h.numero as nrohr, t.codigo as codtractivo,
                SUM(d.saldo_lts) as saldolts,
                (COALESCE(h.combustible_habilitado,0)+COALESCE(h.combustible_tecnico,0)) as comb_hab')
            ->get()
            ->map(function ($o) {
                $o->diferencia = (float) $o->saldolts - (float) $o->comb_hab;

                return $o;
            })
            ->filter(fn ($o) => $o->diferencia != 0)
            ->values();
    }

    // =====================================================================
    // 28 — PARTE RESUMEN DIFERENCIAS CON INDICADORES
    // =====================================================================

    public function pdfIndicadoresDiferencias(?string $mes): \Illuminate\Http\Response
    {
        $titulo = 'PARTE RESUMEN DIFERENCIAS CON INDICADORES ';
        [$anio, $mesNum] = $this->anioMes($mes);

        $campos = [
            ['titulo' => 'TRACTIVO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'HOJA RUTA', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'KMS X HOJA', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'KMS INDICADORES X CP', 'ancho' => 75, 'direccion' => 'C'],
            ['titulo' => 'DIFERENCIA', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LTR'],
        ];
        $campos1 = [
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'RUTA', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'KCARGA', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'KVACIOS', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'KTOTAL', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => '', 'ancho' => 30, 'direccion' => 'C', 'bordes' => 'LRB'],
        ];

        $data = $this->diferenciasIndicadores($anio, $mesNum);

        if ($data->isEmpty()) {
            $this->inicio($titulo);
            $this->SetFont('Arial', 'B', 25);
            $this->SetXY(10, 65);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');

            return $this->salida($titulo.'.pdf');
        }

        $posY = 42;
        $i = 1;
        $max = 35;
        $kmsHr = 0.0;
        $kmcarga = 0.0;
        $kmvacio = 0.0;
        $kmstot = 0.0;

        $this->inicio($titulo);
        $this->titulos(6, 15, 30, $campos1, $campos);
        foreach ($data as $arr) {
            $this->SetXY(15, $posY);
            $this->SetFillColor(255, 255, 255);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(20, 6, (string) $arr->codtractivo, 1, 0, 'L', 1);
            $this->Cell(30, 6, (string) $arr->nrohr, 1, 0, 'L', 1);
            $this->Cell(30, 6, $this->cambiarVariable($arr->kms_hr, 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->cambiarVariable($arr->kmcarga, 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->cambiarVariable($arr->kmvacio, 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->cambiarVariable($arr->kmstot, 2), 1, 0, 'R', 1);
            $this->Cell(30, 6, $this->cambiarVariable($arr->dif, 2), 1, 0, 'R', 1);
            $kmsHr += (float) $arr->kms_hr;
            $kmcarga += (float) $arr->kmcarga;
            $kmvacio += (float) $arr->kmvacio;
            $kmstot += (float) $arr->kmstot;
            $posY += 6;
            $i++;
            if ($i >= $max) {
                $this->inicio($titulo);
                $this->titulos(6, 15, 30, $campos1, $campos);
                $posY = 42;
                $i = 0;
            }
        }
        $this->SetFont('Arial', 'B', 12);
        $this->SetXY(15, $posY);
        $this->SetFillColor(200, 200, 200);
        $this->Cell(50, 6, 'TOTALES', 1, 0, 'C', 1);
        $this->Cell(30, 6, $this->cambiarVariable($kmsHr, 2), 1, 0, 'R', 1);
        $this->Cell(25, 6, $this->cambiarVariable($kmcarga, 2), 1, 0, 'R', 1);
        $this->Cell(25, 6, $this->cambiarVariable($kmvacio, 2), 1, 0, 'R', 1);
        $this->Cell(25, 6, $this->cambiarVariable($kmstot, 2), 1, 0, 'R', 1);
        $this->Cell(30, 6, $this->cambiarVariable(round($kmsHr - $kmstot, 2), 2), 1, 0, 'R', 1);

        return $this->salida($titulo.'.pdf');
    }

    private function diferenciasIndicadores(int $anio, int $mes)
    {
        $rows = DB::table('hojas_ruta as h')
            ->leftJoin('tractivos as t', 't.id', '=', 'h.id_tractivo')
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('t.id_entidad', $this->entidadIds))
            ->whereYear('h.fecha_cierre', $anio)
            ->when($mes > 0, fn ($q) => $q->whereMonth('h.fecha_cierre', $mes))
            ->groupBy('h.id', 'h.numero', 't.codigo', 'h.kms_totales')
            ->orderBy('t.codigo')->orderBy('h.numero')
            ->selectRaw('h.id, h.numero as nrohr, t.codigo as codtractivo, COALESCE(h.kms_totales,0) as kms_hr,
                (SELECT COALESCE(SUM(a.km_carga_total),0) FROM aforos a JOIN cartas_porte cp ON cp.id = a.id_carta_porte WHERE cp.id_hoja_ruta = h.id AND cp.estado != "cancelada") as kmcarga,
                (SELECT COALESCE(SUM(a.km_vacio_total),0) FROM aforos a JOIN cartas_porte cp ON cp.id = a.id_carta_porte WHERE cp.id_hoja_ruta = h.id AND cp.estado != "cancelada") as kmvacio,
                (SELECT COALESCE(SUM(a.km_total_total),0) FROM aforos a JOIN cartas_porte cp ON cp.id = a.id_carta_porte WHERE cp.id_hoja_ruta = h.id AND cp.estado != "cancelada") as kmstot')
            ->get()
            ->map(function ($o) {
                if ((float) $o->kmstot === 0.0) {
                    $o->kmvacio = $o->kms_hr;
                    $o->kmstot = $o->kms_hr;
                }
                $o->dif = (float) $o->kmstot > 0 ? round((float) $o->kmstot - (float) $o->kms_hr, 2) : 0;

                return $o;
            })
            ->filter(fn ($o) => $o->dif != 0)
            ->values();

        return $rows;
    }

    // =====================================================================
    // Datos
    // =====================================================================

    /**
     * Descargas agrupadas por chip + equipo cuyo total de litros supera la
     * capacidad del tanque (`tractivos.cap_deposito`). Equivale a
     * `modDescarga::mostrar_validacion_tanque()`.
     */
    private function descargasSuperiorTanque(int $anio, int $mes)
    {
        $rows = DB::table('combustible_descargas as d')
            ->join('tractivos as t', 't.id', '=', 'd.id_tractivo')
            ->join('tarjetas as ta', 'ta.id', '=', 'd.id_tarjeta')
            ->leftJoin('bolsa as b', 'b.id', '=', 'd.id_empleado')
            ->whereNotNull('d.id_tractivo')
            ->where('d.id_tractivo', '!=', 0)
            ->whereYear('d.f_chip', $anio)
            ->when($mes > 0, fn ($q) => $q->whereMonth('d.f_chip', $mes))
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('ta.id_entidad', $this->entidadIds))
            ->groupBy('d.f_chip', 't.codigo', 'b.nombre', 'b.apellidos', 't.cap_deposito')
            ->orderBy('d.f_chip')
            ->orderBy('t.codigo')
            ->selectRaw('d.f_chip as fchip, t.codigo as codtractivo,
                TRIM(CONCAT(COALESCE(b.nombre,""), " ", COALESCE(b.apellidos,""))) as nombrecompleto,
                COUNT(d.folio) as folio,
                SUM(d.saldo_mon) as saldomon,
                SUM(d.saldo_lts) as saldolts,
                COALESCE(t.cap_deposito,0) as tanqueinicio')
            ->havingRaw('SUM(d.saldo_lts) > COALESCE(t.cap_deposito,0)')
            ->get();

        return $rows;
    }

    /**
     * Descargas agrupadas por tarjeta + equipo. Equivale a
     * `modDescarga::mostrar_validacion_tarjetas()`.
     */
    private function descargasEquiposEnTarjeta(int $anio, int $mes)
    {
        return DB::table('combustible_descargas as d')
            ->join('tractivos as t', 't.id', '=', 'd.id_tractivo')
            ->join('tarjetas as ta', 'ta.id', '=', 'd.id_tarjeta')
            ->whereNotNull('d.id_tractivo')
            ->where('d.id_tractivo', '!=', 0)
            ->whereYear('d.f_chip', $anio)
            ->when($mes > 0, fn ($q) => $q->whereMonth('d.f_chip', $mes))
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('ta.id_entidad', $this->entidadIds))
            ->groupBy('t.codigo', 'ta.numero')
            ->orderBy('ta.numero')
            ->orderBy('t.codigo')
            ->selectRaw('ta.numero as codtm, t.codigo as codtractivo,
                SUM(d.saldo_mon) as saldomon,
                SUM(d.saldo_lts) as saldolts')
            ->get();
    }

    /** Id del combustible DIESEL (la tabla `tarjetas` conserva el id legacy 14). */
    private function dieselId(): int
    {
        return 14;
    }

    /** Saldo inicial de litros de diesel (legacy `obtener_resumen_sinicial`). */
    private function resumenInicialDiesel(int $anio, int $mes): float
    {
        return (float) DB::table('tarjetas')
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('id_entidad', $this->entidadIds))
            ->where('cancelado', 0)
            ->where('idtipocombustibles', $this->dieselId())
            ->sum('saldoiniciallts');
    }

    /** Litros descargados de diesel en un día (legacy `modDescarga::mostrar_fechas_parte`). */
    private function descargasDieselDia(int $anio, int $mes, int $dia): float
    {
        return (float) DB::table('combustible_descargas as d')
            ->join('tarjetas as ta', 'ta.id', '=', 'd.id_tarjeta')
            ->join('tractivos as t', 't.id', '=', 'd.id_tractivo')
            ->whereYear('d.fdescarga', $anio)
            ->whereMonth('d.fdescarga', $mes)
            ->whereDay('d.fdescarga', $dia)
            ->where('ta.idtipocombustibles', $this->dieselId())
            ->where('ta.cancelado', 0)
            ->whereNotNull('d.id_tractivo')
            ->where('d.id_tractivo', '!=', 0)
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('t.id_entidad', $this->entidadIds))
            ->sum('d.saldo_lts');
    }

    /** Litros cargados de diesel en un día (legacy `modCarga::mostrar_fechas_parte`). */
    private function cargasDieselDia(int $anio, int $mes, int $dia): float
    {
        return (float) DB::table('detalles_carga_combustible as dc')
            ->join('combustible_cargas as c', 'c.id', '=', 'dc.id_carga')
            ->join('tarjetas as ta', 'ta.id', '=', 'dc.id_tarjeta')
            ->whereYear('c.fcarga', $anio)
            ->whereMonth('c.fcarga', $mes)
            ->whereDay('c.fcarga', $dia)
            ->where('ta.idtipocombustibles', $this->dieselId())
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('c.id_entidad', $this->entidadIds))
            ->sum('dc.saldo_lts');
    }

    /**
     * Descargas del mes con índice real y desviación, filtrando las deterioradas
     * (legacy `modTarjetas::obtener_detalle_descarga_mes`).
     */
    private function detalleDescargaOnure(int $anio, int $mes)
    {
        $rows = DB::table('combustible_descargas as d')
            ->leftJoin('tarjetas as ta', 'ta.id', '=', 'd.id_tarjeta')
            ->leftJoin('tractivos as t', 't.id', '=', 'd.id_tractivo')
            ->leftJoin('servicentros as s', 's.id', '=', 'd.id_servicentro')
            ->whereYear('d.fdescarga', $anio)
            ->when($mes > 0, fn ($q) => $q->whereMonth('d.fdescarga', $mes))
            ->where('t.id_grupo', 1)
            ->when(! empty($this->entidadIds), fn ($q) => $q->whereIn('t.id_entidad', $this->entidadIds))
            ->orderBy('t.codigo')->orderBy('d.fdescarga')->orderBy('d.hora_descarga')
            ->selectRaw('d.fdescarga, d.hora_descarga as hdescarga, s.nombre as servicentros,
                COALESCE(d.saldo_lts,0) as saldolts, t.codigo as codtractivo,
                COALESCE(t.indice_consumo,0) as indice, COALESCE(d.kms,0) as kms')
            ->get();

        return $rows->map(function ($o) {
            $o->indicereal = $o->saldolts != 0 ? round($o->kms / $o->saldolts, 2) : 0;
            $o->difindice = round($o->indice - $o->indicereal, 2);
            $o->kmsplan = round($o->saldolts * $o->indice, 2);
            $o->difkms = round($o->kmsplan - $o->kms, 2);
            $o->porciento = $o->kmsplan != 0 ? round($o->difkms / $o->kmsplan * 100, 2) : 0;

            return $o;
        })->filter(fn ($o) => $o->kmsplan > $o->kms && $o->porciento > 5)->values();
    }

    /** Diferencia en días absolutos (paridad con `modGenerales::restaFechas`). */
    private function restaFechas(?string $dFecIni, ?string $dFecFin): float
    {
        if (! $dFecIni || ! $dFecFin) {
            return 0.0;
        }

        return (float) abs(round((strtotime($dFecFin) - strtotime($dFecIni)) / 86400, 2));
    }

    /** Devuelve [anio, mes] desde 'MM', 'YYYY-MM' o vacío (sesión). */
    private function anioMes(?string $mes): array
    {
        $fechaOps = $this->fechaOperaciones ?: now()->toDateString();
        $mes = trim((string) $mes);

        if ($mes === '' || $mes === 'TODOS') {
            return [(int) substr($fechaOps, 0, 4), (int) substr($fechaOps, 5, 2)];
        }
        if (strlen($mes) === 2) {
            return [(int) substr($fechaOps, 0, 4), (int) $mes];
        }
        if (strlen($mes) >= 7) {
            return [(int) substr($mes, 0, 4), (int) substr($mes, 5, 2)];
        }

        return [(int) substr($fechaOps, 0, 4), (int) substr($fechaOps, 5, 2)];
    }
}
