<?php

namespace App\Console\Commands;

use App\Services\NotificarDocumentosChofer;
use Illuminate\Console\Command;

class NotificarChoferesDocumentos extends Command
{
    protected $signature = 'emcarga:notificar-choferes {--chofer= : ID de un chofer específico}';

    protected $description = 'Notifica a COMERCIAL/DIRECTIVOS/RECHUM de la entidad cuando los documentos de un chofer están próximos a vencer o vencidos';

    public function handle(NotificarDocumentosChofer $servicio): int
    {
        $choferId = $this->option('chofer') ? (int) $this->option('chofer') : null;

        $total = $servicio->ejecutar($choferId);

        $this->info("Notificaciones enviadas: {$total}.");

        return self::SUCCESS;
    }
}
