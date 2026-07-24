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
