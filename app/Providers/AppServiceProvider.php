<?php

namespace App\Providers;

use App\Database\Grammars\MariaDbGrammarOverride;
use App\Database\Processors\MariaDbProcessorOverride;
use App\Models\Aforo;
use App\Models\Arrastre;
use App\Models\Tractivo;
use App\Services\Push\ExpoPushSender;
use App\Services\Push\FcmPushSender;
use App\Services\Push\NullPushSender;
use App\Services\Push\PushSender;
use App\Policies\IndicadorePolicy;
use App\Policies\RolePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\RateLimiter;
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
        // Envío push móvil: Firebase (FCM) si está habilitado, Expo como
        // alternativa legacy, y un no-op (offline) por defecto. El cliente
        // Ionic genera tokens FCM → `PUSH_PROVIDER=fcm` es el camino real.
        $this->app->singleton(PushSender::class, function () {
            $provider = (string) config('services.push.provider', 'off');

            if ($provider === 'fcm') {
                $fcm = $this->credencialesFcm();

                if (empty($fcm['project_id']) || empty($fcm['client_email']) || empty($fcm['private_key'])) {
                    return new NullPushSender();
                }

                return new FcmPushSender(
                    $fcm['project_id'],
                    $fcm['client_email'],
                    $fcm['private_key'],
                    $fcm['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                    $fcm['send_uri'] ?? null,
                );
            }

            if ($provider === 'expo') {
                $expo = config('services.expo');

                return new ExpoPushSender($expo['endpoint'], $expo['access_token'] ?? null);
            }

            return new NullPushSender();
        });
    }

    /**
     * Credenciales FCM: prioriza el archivo del service account de Firebase
     * (`FCM_CREDENTIALS_JSON`) y como alternativa las variables sueltas del
     * .env (`FCM_PROJECT_ID`, `FCM_CLIENT_EMAIL`, `FCM_PRIVATE_KEY`).
     *
     * @return array{project_id: string|null, client_email: string|null, private_key: string|null, token_uri: string|null, send_uri: string|null}
     */
    private function credencialesFcm(): array
    {
        $fcm = config('services.fcm');

        $ruta = $fcm['credentials_json'] ?? null;
        if ($ruta !== null && $ruta !== '' && is_file($ruta)) {
            $datos = json_decode((string) file_get_contents($ruta), true);

            if (is_array($datos)) {
                return [
                    'project_id' => $datos['project_id'] ?? null,
                    'client_email' => $datos['client_email'] ?? null,
                    'private_key' => $datos['private_key'] ?? null,
                    'token_uri' => $datos['token_uri'] ?? null,
                    'send_uri' => $fcm['send_uri'] ?? null,
                ];
            }
        }

        return [
            'project_id' => $fcm['project_id'] ?? null,
            'client_email' => $fcm['client_email'] ?? null,
            'private_key' => $fcm['private_key'] ?? null,
            'token_uri' => $fcm['token_uri'] ?? null,
            'send_uri' => $fcm['send_uri'] ?? null,
        ];
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

        // Rate limiter del login de la API móvil (5/min por usuario+IP).
        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(5)
                ->by(strtolower((string) $request->input('username')).'|'.$request->ip());
        });

        // Rate limiter general de la API móvil (60/min por usuario autenticado
        // o por IP si aún no hay token). Protege todos los endpoints de negocio.
        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ? 'u:'.$request->user()->id : 'ip:'.$request->ip());
        });

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

        // Documentación OpenAPI (Scramble): RestrictedDocsAccess la limita a
        // entorno local; fuera de local solo SUPERADMIN (usuarios con sesión web).
        Gate::define('viewApiDocs', fn ($user = null) => $user !== null && $user->hasRole('SUPERADMIN'));

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
