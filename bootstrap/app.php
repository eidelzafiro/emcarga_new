<?php

use App\Http\Middleware\Api\ResolverEntidadApi;
use App\Http\Middleware\Api\ResolverFechaOperacionesApi;
use App\Http\Middleware\AuditoriaEscritura;
use App\Http\Middleware\EnsureModulePermission;
use App\Http\Middleware\EstablecerContextoTrabajo;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LicenciaMiddleware;
use App\Http\Middleware\RedirectIfPasswordTemporal;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            EstablecerContextoTrabajo::class,
            LicenciaMiddleware::class,
            SecurityHeaders::class,
            AuditoriaEscritura::class,
        ]);

        // La API móvil también recibe cabeceras de seguridad (nosniff, HSTS si
        // la petición llega por HTTPS, etc.).
        $middleware->api(append: [
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'password.temporal' => RedirectIfPasswordTemporal::class,
            'permiso.modulo' => EnsureModulePermission::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'api.entidad' => ResolverEntidadApi::class,
            'api.fecha' => ResolverFechaOperacionesApi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
