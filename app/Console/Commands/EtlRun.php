<?php

namespace App\Console\Commands;

use App\Services\Etl\EtlService;
use Illuminate\Console\Command;

/**
 * Comando ETL legacy → nuevo esquema (Fase 3).
 *
 * Uso:
 *   php artisan emcarga:etl                 ETL completo (usuarios + tablas mapeadas)
 *   php artisan emcarga:etl --solo=users    Solo usuarios (con password_histories)
 *   php artisan emcarga:etl --solo=clientes Solo una tabla del mapeo
 *   php artisan emcarga:etl --validar       Solo conteos old vs new, sin migrar
 */
class EtlRun extends Command
{
    protected $signature = 'emcarga:etl
                            {--solo= : Migrar solo una tabla (users o una del mapeo)}
                            {--validar : Solo muestra conteos legacy vs nueva}
                            {--chunk=1000 : Tamaño del lote de lectura}';

    protected $description = 'Migra datos del sistema legacy (CodeIgniter) al nuevo esquema (Fase 3)';

    public function handle(EtlService $etl): int
    {
        if ($this->option('validar')) {
            return $this->mostrarValidacion($etl);
        }

        $solo = $this->option('solo');
        $chunk = (int) $this->option('chunk');

        if (! $solo || $solo === 'users') {
            $this->info('Migrando usuarios (cod_usuarios → users + password_histories)...');
            $etl->migrarUsuarios(min($chunk, 500));
            $this->mostrarResultado($etl->getReporte());
        }

        foreach (array_keys(config('etl.tablas')) as $tabla) {
            if ($solo && $solo !== $tabla) {
                continue;
            }

            $this->info("Migrando {$tabla}...");
            $etl->migrarTabla($tabla, $chunk);
            $this->mostrarResultado($etl->getReporte(), $tabla);
        }

        $this->newLine();
        $this->info('ETL finalizado. Ejecute php artisan emcarga:etl --validar para verificar conteos.');

        return self::SUCCESS;
    }

    private function mostrarValidacion(EtlService $etl): int
    {
        $filas = [];

        foreach ($etl->validar() as $tabla => $conteos) {
            $ok = $conteos['legacy'] === $conteos['nueva'] ? '<fg=green>OK</>' : '<fg=red>DIFIERE</>';
            $filas[] = [$tabla, $conteos['legacy'], $conteos['nueva'], $ok];
        }

        $this->table(['Tabla', 'Legacy', 'Nueva', 'Estado'], $filas);

        return self::SUCCESS;
    }

    private function mostrarResultado(array $reporte, ?string $tabla = null): void
    {
        foreach ($reporte as $nombre => $datos) {
            if ($tabla && $nombre !== $tabla) {
                continue;
            }

            $this->line("  <fg=cyan>{$nombre}</>: {$datos['nueva']}/{$datos['legacy']} registros");

            foreach (array_slice($datos['avisos'], 0, 10) as $aviso) {
                $this->warn("  ⚠ {$aviso}");
            }

            if (count($datos['avisos']) > 10) {
                $this->warn('  ⚠ ... y '.(count($datos['avisos']) - 10).' avisos más');
            }
        }
    }
}
