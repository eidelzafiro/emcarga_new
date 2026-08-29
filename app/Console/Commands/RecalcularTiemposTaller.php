<?php

namespace App\Console\Commands;

use App\Models\MovimientosTaller;
use App\Models\OrdenesOperacione;
use App\Models\OrdenesTaller;
use Illuminate\Console\Command;

/**
 * Backfill de los tiempos de las órdenes de taller.
 *
 * El bug histórico de OrdenTallerService::calcularTiempo() (usaba
 * max(0, $final->diffInMinutes($inicio)) que devuelve negativo para rangos
 * normales) dejó las columnas `tiempo` de operaciones/movimientos y `ottiempo`
 * de la OT en 0. Este comando recalcula esas columnas desde las fechas/horas
 * (siempre correctas en BD) para todos los registros existentes.
 *
 * Es idempotente: se puede ejecutar tantas veces como se quiera.
 */
class RecalcularTiemposTaller extends Command
{
    protected $signature = 'zafiro:recalcular-tiempos-taller';

    protected $description = 'Recalcula los tiempos (tiempo/ottiempo) de las órdenes de taller desde sus fechas/horas';

    public function handle(): int
    {
        $ops = OrdenesOperacione::query()->with(['orden:id,id,id_entidad'])->get();
        $bar = $this->output->createProgressBar($ops->count());
        $bar->start();

        foreach ($ops as $op) {
            $this->recalcularFila($op);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);
        $this->info("Operaciones recalculadas: {$ops->count()}");

        $mvs = MovimientosTaller::query()->get();
        $bar = $this->output->createProgressBar($mvs->count());
        $bar->start();

        foreach ($mvs as $mv) {
            $this->recalcularFila($mv);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);
        $this->info("Movimientos recalculados: {$mvs->count()}");

        $ots = OrdenesTaller::query()->get();
        $bar = $this->output->createProgressBar($ots->count());
        $bar->start();

        foreach ($ots as $ot) {
            $total = (float) $ot->operaciones()->sum('tiempo');
            if ($total <= 0 && $ot->fecha_ingreso && $ot->fecha_salida) {
                $total = $this->duracion(
                    $ot->fecha_ingreso, $ot->hora_ingreso,
                    $ot->fecha_salida, $ot->hora_salida
                );
            }
            $ot->update(['ottiempo' => round($total, 2)]);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);
        $this->info("Órdenes de taller recalculadas: {$ots->count()}");

        return self::SUCCESS;
    }

    private function recalcularFila($fila): void
    {
        if (! $fila->fecha_inicio || ! $fila->fecha_final) {
            return;
        }
        $tiempo = $this->duracion(
            $fila->fecha_inicio, $fila->hora_inicio,
            $fila->fecha_final, $fila->hora_final
        );
        if ((float) $fila->tiempo !== $tiempo) {
            $fila->update(['tiempo' => $tiempo]);
        }
    }

    private function duracion($fi, ?string $hi, $ff, ?string $hf): float
    {
        try {
            $inicio = \Carbon\Carbon::parse($fi->format('Y-m-d').' '.($hi ?: '00:00'));
            $final = \Carbon\Carbon::parse($ff->format('Y-m-d').' '.($hf ?: '00:00'));

            return round(abs($final->diffInMinutes($inicio)) / 60, 2);
        } catch (\Throwable) {
            return 0;
        }
    }
}
