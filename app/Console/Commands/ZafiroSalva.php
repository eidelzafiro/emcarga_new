<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class ZafiroSalva extends Command
{
    protected $signature = 'zafiro:salva
                            {--restaurar : Restaura la BD desde la última salva (o --archivo)}
                            {--listar : Lista las salvaciones disponibles}
                            {--archivo= : Ruta específica para restaurar}
                            {--sufijo= : Sufijo opcional para nombrar la salva}';

    protected $description = 'Salva (dump) o restaura la base de datos emcarga_new';

    public function handle(DatabaseBackupService $backup): int
    {
        if ($this->option('listar')) {
            $this->line('Salvaciones disponibles:');
            foreach ($backup->listar() as $i => $archivo) {
                $linea = ($i === 0 ? '  <fg=cyan>→ (última)</>' : '  ').basename($archivo);
                $this->line($linea);
            }

            return self::SUCCESS;
        }

        if ($this->option('restaurar')) {
            try {
                $archivo = $backup->restaurar($this->option('archivo'));
            } catch (\RuntimeException $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            $this->info('BD restaurada desde: '.basename($archivo));

            return self::SUCCESS;
        }

        try {
            $archivo = $backup->salvar($this->option('sufijo'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Salva creada: '.basename($archivo).' ('.number_format(filesize($archivo) / 1048576, 2).' MB)');

        return self::SUCCESS;
    }
}
