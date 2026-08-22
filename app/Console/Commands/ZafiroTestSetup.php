<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P3.3 del plan general: optimiza el setup de la BD de test.
 *
 * Por defecto reutiliza la BD de test ya migrada y sembrada (emcarga_new_test):
 * si ya tiene las migraciones aplicadas y datos sembrados, omite el costoso
 * `migrate:fresh --seed` y solo aplica migraciones pendientes con `migrate`.
 * Esto reduce el setup de varios minutos a segundos en corridas repetidas.
 *
 * Usar --fresh para forzar migrate:fresh --seed (p. ej. tras cambiar
 * migraciones existentes).
 *
 * El script composer test:setup debe invocarlo con
 * DB_DATABASE=emcarga_new_test para apuntar a la BD de test.
 */
class ZafiroTestSetup extends Command
{
    protected $signature = 'zafiro:test-setup {--fresh : Fuerza migrate:fresh --seed ignorando el estado actual}';

    protected $description = 'Prepara la BD de test reusando el estado previo cuando es posible (optimización P3.3).';

    public function handle(): int
    {
        $fresh = (bool) $this->option('fresh');

        $migrada = Schema::hasTable('migrations') && DB::table('migrations')->count() > 0;
        $sembrada = Schema::hasTable('users') && DB::table('users')->count() > 0;

        if ($fresh || ! $migrada) {
            $this->info('Reconstruyendo BD de test (migrate:fresh --seed)...');
            Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
            $this->line(Artisan::output());

            return self::SUCCESS;
        }

        if (! $sembrada) {
            $this->info('BD migrada sin sembrar: ejecutando seeders...');
            Artisan::call('db:seed', ['--force' => true]);
            $this->line(Artisan::output());

            return self::SUCCESS;
        }

        $this->info('BD de test ya preparada: aplicando migraciones pendientes (migrate)...');
        Artisan::call('migrate', ['--force' => true]);
        $this->line(Artisan::output());

        return self::SUCCESS;
    }
}
