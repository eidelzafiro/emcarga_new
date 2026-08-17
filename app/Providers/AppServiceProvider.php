<?php

namespace App\Providers;

use App\Database\Grammars\MariaDbGrammarOverride;
use App\Database\Processors\MariaDbProcessorOverride;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
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
        Schema::defaultStringLength(191);

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
    }
}
