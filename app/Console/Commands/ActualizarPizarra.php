<?php

namespace App\Console\Commands;

use App\Events\PizarraUpdated;
use App\Models\Pizarra;
use Illuminate\Console\Command;

class ActualizarPizarra extends Command
{
    protected $signature = 'emcarga:actualizar-pizarra {--broadcast : Emitir evento en tiempo real}';

    protected $description = 'Recalcula la pizarra de vehículos desde los datos actuales';

    public function handle(): int
    {
        $registros = Pizarra::with(['tractivo:id,descripcion,placa', 'conductor:id,name'])
            ->latest()
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'vehiculo' => $p->tractivo?->descripcion ?? '—',
                'placa' => $p->tractivo?->placa ?? '—',
                'conductor' => $p->conductor?->name ?? '—',
                'estado' => $p->estado,
                'ubicacion' => $p->ubicacion,
                'origen' => $p->origen,
                'destino' => $p->destino,
                'salida' => $p->salida?->format('H:i d/m/Y'),
                'llegada_estimada' => $p->llegada_estimada?->format('H:i d/m/Y'),
                'carga' => $p->carga,
                'tonelaje' => $p->tonelaje,
            ]);

        $this->info('Pizarra actualizada: ' . count($registros) . ' vehículos.');

        if ($this->option('broadcast')) {
            broadcast(new PizarraUpdated($registros->toArray()));
            $this->info('Evento PizarraUpdated emitido.');
        }

        return Command::SUCCESS;
    }
}
