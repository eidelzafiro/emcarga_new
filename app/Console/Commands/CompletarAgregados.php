<?php

namespace App\Console\Commands;

use App\Models\Tractivo;
use App\Services\AgregadosTractivoService;
use Illuminate\Console\Command;

/**
 * Backfill: garantiza motor, caja y diferencial a TODOS los tractivos que
 * no los tengan (regla: todo tractivo opera con sus 3 agregados). Idempotente.
 */
class CompletarAgregados extends Command
{
    protected $signature = 'zafiro:completar-agregados';

    protected $description = 'Crea motor/caja/diferencial faltantes para cada tractivo (M-/C-/D- + código del tractivo)';

    public function handle(AgregadosTractivoService $servicio): int
    {
        $tractivos = Tractivo::orderBy('id')->get(['id', 'codigo', 'marca', 'modelo']);
        $bar = $this->output->createProgressBar($tractivos->count());
        $bar->start();

        $motores = 0;
        $cajas = 0;
        $diferenciales = 0;

        foreach ($tractivos as $tractivo) {
            $creados = $servicio->asegurar($tractivo);
            $motores += isset($creados['motor']) ? 1 : 0;
            $cajas += isset($creados['caja']) ? 1 : 0;
            $diferenciales += isset($creados['diferencial']) ? 1 : 0;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Completado. Motores creados: {$motores} · Cajas creadas: {$cajas} · Diferenciales creados: {$diferenciales}");

        return self::SUCCESS;
    }
}
