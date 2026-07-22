<?php

namespace App\Console\Commands;

use App\Events\KpisUpdated;
use App\Services\KpiService;
use Illuminate\Console\Command;

class CalcularKpis extends Command
{
    protected $signature = 'emcarga:kpis {--broadcast : Emitir evento de broadcast}';
    protected $description = 'Recalcula y muestra los KPIs del dashboard';

    public function handle(KpiService $kpiService)
    {
        $kpis = $kpiService->calcular();

        $this->table(['Indicador', 'Valor', 'Ícono'], array_map(fn ($k) => [
            $k['label'],
            $k['valor'],
            $k['icono'],
        ], $kpis));

        if ($this->option('broadcast')) {
            KpisUpdated::dispatch($kpis);
            $this->info('KPIs broadcast enviado.');
        }
    }
}
