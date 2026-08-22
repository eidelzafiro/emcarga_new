<?php

namespace App\Services\Reports;

use App\Models\Entidad;
use App\Models\HojasRuta;
use App\Models\Tractivo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

/**
 * Reportes del grupo TIEMPOS (migrado en R-1): resúmenes de tiempos por tractivo
 * y por empresa, y conciliación Hoja de Ruta–Tiempos. Basados en los campos de
 * tiempos de `hojas_ruta`.
 *
 * Filtro: 'mes' (YYYY-MM).
 */
class TiemposReportService
{
    private function entidadIds(): array
    {
        $activa = (int) entidadActivaId();
        if (! $activa) {
            return [23];
        }

        return Entidad::subEntidadesIds($activa);
    }

    private function rangoFiltros(array $filtros): array
    {
        $valor = $filtros['fecha'] ?? $filtros['mes'] ?? $filtros['mes1'] ?? null;
        if (! $valor) {
            $valor = now()->format('Y-m');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return [Carbon::parse($valor)->startOfDay(), Carbon::parse($valor)->endOfDay()];
        }

        return [Carbon::parse($valor.'-01')->startOfMonth(), Carbon::parse($valor.'-01')->endOfMonth()];
    }

    private function nombreMes(int $m): string
    {
        return match ($m) {
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
            default => '',
        };
    }

    private function reporteTablaPdf(string $titulo, array $columnas, array $filas, array $opts = []): \Illuminate\Http\Response
    {
        $periodo = $opts['periodo'] ?? '';
        $pdf = Pdf::loadHTML(view('reports.reporte_tabla', compact('titulo', 'columnas', 'filas', 'periodo'))->render());

        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf']);
    }

    private function columnasTiempos(bool $conTractivo): array
    {
        $cols = [];
        if ($conTractivo) {
            $cols[] = ['key' => 'tractivo', 'label' => 'Tractivo', 'num' => false];
        }
        $cols[] = ['key' => 'n', 'label' => 'HR', 'num' => true];
        $cols[] = ['key' => 't_mov', 'label' => 'Mov', 'num' => true];
        $cols[] = ['key' => 't_esp', 'label' => 'Espera', 'num' => true];
        $cols[] = ['key' => 't_car', 'label' => 'Carga', 'num' => true];
        $cols[] = ['key' => 't_tal', 'label' => 'Taller', 'num' => true];
        $cols[] = ['key' => 't_inac', 'label' => 'Inactivo', 'num' => true];
        $cols[] = ['key' => 't_otr', 'label' => 'Otras', 'num' => true];
        $cols[] = ['key' => 't_tot', 'label' => 'Total', 'num' => true];

        return $cols;
    }

    private function fmt(?float $v): string
    {
        return number_format((float) ($v ?? 0), 2);
    }

    // 128 — RESUMEN TIEMPOS TRACTIVOS
    public function resumenTiemposTractivos(array $filtros): \Illuminate\Http\Response
    {
        [$desde, $hasta] = $this->rangoFiltros($filtros);
        $ids = $this->entidadIds();

        $datos = HojasRuta::whereIn('id_entidad', $ids)
            ->whereBetween('fecha_emision', [$desde, $hasta])
            ->selectRaw('id_tractivo, COUNT(*) as n, SUM(tiempo_mov) as t_mov, SUM(tiempo_espera) as t_esp, SUM(tiempo_carga) as t_car, SUM(tiempo_taller) as t_tal, SUM(tiempo_inactivo) as t_inac, SUM(tiempo_otras_actividades) as t_otr, SUM(tiempo_total) as t_tot')
            ->groupBy('id_tractivo')
            ->orderByDesc('t_tot')
            ->get();

        $placas = Tractivo::whereIn('id', $datos->pluck('id_tractivo'))->pluck('placa', 'id');

        $filas = $datos->map(fn ($d) => [
            'tractivo' => $placas[$d->id_tractivo] ?? '—',
            'n' => $d->n,
            't_mov' => $this->fmt($d->t_mov),
            't_esp' => $this->fmt($d->t_esp),
            't_car' => $this->fmt($d->t_car),
            't_tal' => $this->fmt($d->t_tal),
            't_inac' => $this->fmt($d->t_inac),
            't_otr' => $this->fmt($d->t_otr),
            't_tot' => $this->fmt($d->t_tot),
        ])->all();

        $periodo = ! empty($filtros['mes']) ? 'Mes: '.$this->nombreMes(Carbon::parse($filtros['mes'])->month) : '';

        return $this->reporteTablaPdf('Resumen de Tiempos por Tractivos', $this->columnasTiempos(true), $filas, ['periodo' => $periodo]);
    }

    // 129 — RESUMEN TIEMPOS EMPRESA
    public function resumenTiemposEmpresa(array $filtros): \Illuminate\Http\Response
    {
        [$desde, $hasta] = $this->rangoFiltros($filtros);
        $ids = $this->entidadIds();

        $t = HojasRuta::whereIn('id_entidad', $ids)
            ->whereBetween('fecha_emision', [$desde, $hasta])
            ->selectRaw('COUNT(*) as n, SUM(tiempo_mov) as t_mov, SUM(tiempo_espera) as t_esp, SUM(tiempo_carga) as t_car, SUM(tiempo_taller) as t_tal, SUM(tiempo_inactivo) as t_inac, SUM(tiempo_otras_actividades) as t_otr, SUM(tiempo_total) as t_tot')
            ->first();

        $filas = [[
            'tractivo' => 'TOTAL EMPRESA',
            'n' => $t->n,
            't_mov' => $this->fmt($t->t_mov),
            't_esp' => $this->fmt($t->t_esp),
            't_car' => $this->fmt($t->t_car),
            't_tal' => $this->fmt($t->t_tal),
            't_inac' => $this->fmt($t->t_inac),
            't_otr' => $this->fmt($t->t_otr),
            't_tot' => $this->fmt($t->t_tot),
        ]];

        $periodo = ! empty($filtros['mes']) ? 'Mes: '.$this->nombreMes(Carbon::parse($filtros['mes'])->month) : '';

        return $this->reporteTablaPdf('Resumen de Tiempos de la Empresa', $this->columnasTiempos(true), $filas, ['periodo' => $periodo]);
    }

    // 136 — CONCILIACION HOJA RUTA-TIEMPOS
    public function conciliacionHojaRutaTiempos(array $filtros): \Illuminate\Http\Response
    {
        [$desde, $hasta] = $this->rangoFiltros($filtros);
        $ids = $this->entidadIds();

        $hr = HojasRuta::with('tractivo:id,placa')
            ->whereIn('id_entidad', $ids)
            ->whereBetween('fecha_emision', [$desde, $hasta])
            ->orderBy('fecha_emision')->orderBy('id')
            ->get();

        $filas = $hr->map(fn ($h) => [
            'tractivo' => optional($h->tractivo)->placa,
            'n' => 1,
            't_mov' => $this->fmt($h->tiempo_mov),
            't_esp' => $this->fmt($h->tiempo_espera),
            't_car' => $this->fmt($h->tiempo_carga),
            't_tal' => $this->fmt($h->tiempo_taller),
            't_inac' => $this->fmt($h->tiempo_inactivo),
            't_otr' => $this->fmt($h->tiempo_otras_actividades),
            't_tot' => $this->fmt($h->tiempo_total),
        ])->all();

        $periodo = ! empty($filtros['mes']) ? 'Mes: '.$this->nombreMes(Carbon::parse($filtros['mes'])->month) : '';

        return $this->reporteTablaPdf('Conciliación Hoja de Ruta – Tiempos', $this->columnasTiempos(true), $filas, ['periodo' => $periodo]);
    }
}
