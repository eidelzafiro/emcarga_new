<?php

namespace App\Console\Commands;

use App\Models\SolicitudesServicio;
use Illuminate\Console\Command;

/**
 * Recalcula el estado de todas las solicitudes de servicio a partir de sus
 * cartas de porte vigentes (criterio `SolicitudesServicio::recalcularEstado`:
 * ejecutada si alguna carta está recepcionada o tiene aforo). Se usa para
 * corregir el estado tras la migración legacy.
 */
class RecalcularSolicitudes extends Command
{
    protected $signature = 'zafiro:recalcular-solicitudes';

    protected $description = 'Recalcula el estado de las solicitudes de servicio según sus cartas de porte';

    public function handle(): int
    {
        $solicitudes = SolicitudesServicio::withCount(['cartasPorte' => fn ($q) => $q->where('estado', '!=', 'cancelada')])
            ->orderBy('id')
            ->get();

        $bar = $this->output->createProgressBar($solicitudes->count());
        $bar->start();

        $ejecutadas = 0;
        $pendientes = 0;
        $enProceso = 0;

        foreach ($solicitudes as $sol) {
            $sol->recalcularEstado();
            match ($sol->fresh()->estado) {
                'ejecutada' => $ejecutadas++,
                'en_proceso' => $enProceso++,
                default => $pendientes++,
            };
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Recalculadas: {$solicitudes->count()} solicitudes.");
        $this->line("Ejecutadas: {$ejecutadas} | En proceso: {$enProceso} | Pendientes: {$pendientes}");

        return self::SUCCESS;
    }
}
