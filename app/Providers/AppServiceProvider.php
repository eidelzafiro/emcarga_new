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

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // MariaDB 10.1 no tiene generation_expression en information_schema.
        // Gramática y procesador custom lo omiten para evitar SQL 1054.
        if (DB::connection('mysql')->isMaria()) {
            $connection = DB::connection('mysql');
            $connection->setSchemaGrammar(new MariaDbGrammarOverride($connection));
            $connection->setPostProcessor(new MariaDbProcessorOverride);
        }

        // El modelo Role vive en el paquete spatie, así que su policy
        // no se auto-descubre y se registra explícitamente.
        Gate::policy(Role::class, RolePolicy::class);
    }
}
