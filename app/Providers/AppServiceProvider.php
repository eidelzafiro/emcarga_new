<?php

namespace App\Providers;

use App\Database\Grammars\MariaDbGrammarOverride;
use App\Database\Processors\MariaDbProcessorOverride;
use App\Policies\IndicadorePolicy;
use App\Policies\RolePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Aforo;
use App\Models\Arrastre;
use App\Models\Tractivo;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Detecta consultas N+1 en desarrollo local (lanza excepción) para
        // evitar regresiones de rendimiento. Se acota a 'local' para no romper
        // el suite de tests (APP_ENV=testing) mientras se corrigen los N+1
        // existentes de forma incremental.
        Model::preventLazyLoading(config('app.env') === 'local');

        Schema::defaultStringLength(191);

        // Fase C: tipo polimórfico de las fichas de vehículo (amortización,
        // planes, documentación). Debe coincidir con el valor insertado en las
        // tablas vehiculos_* ('tractivo' / 'arrastre' tras la Fase D).
        Relation::morphMap([
            'tractivo' => Tractivo::class,
            'arrastre' => Arrastre::class,
        ]);

        // dompdf requiere un directorio de fuentes/caché escribible por el
        // proceso (www-data). Se crea con permisos amplios de forma idempotente
        // para que funcione tras recrear contenedores (bind mount WSL2).
        $fontDir = storage_path('fonts');
        if (! is_dir($fontDir)) {
            @mkdir($fontDir, 0777, true);
        }
        if (is_dir($fontDir)) {
            @chmod($fontDir, 0777);
        }

        try {
            if (config('database.default') === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            if (DB::connection('mysql')->isMaria()) {
                $connection = DB::connection('mysql');
                $connection->setSchemaGrammar(new MariaDbGrammarOverride($connection));
                $connection->setPostProcessor(new MariaDbProcessorOverride);
            }
        } catch (\PDOException $e) {
            // Sin BD disponible (CI, composer install, etc.) — se omite
        }

        Gate::policy(Role::class, RolePolicy::class);

        // El módulo "Indicadores" edita los aforos; su policy no sigue el
        // convenio Modelo→Policy (no existe App\Models\Indicadore), por eso se
        // registra explícitamente para el modelo Aforo.
        Gate::policy(Aforo::class, IndicadorePolicy::class);

        // O-1 (optimización de suite): cuando se corre con `--parallel`, Laravel
        // crea una BD propia por worker (emcarga_new_test_<token>). Como la suite
        // usa DatabaseTransactions sobre un baseline sembrado (no RefreshDatabase),
        // cada worker necesita su propia BD migrada y sembrada. Este callback la
        // prepara una sola vez por worker. No afecta el flujo secuencial normal.
        ParallelTesting::setUpTestDatabase(function (string $database, int $token): void {
            Artisan::call('migrate:fresh', [
                '--database' => $database,
                '--seed' => true,
                '--force' => true,
            ]);
        });
    }
}
