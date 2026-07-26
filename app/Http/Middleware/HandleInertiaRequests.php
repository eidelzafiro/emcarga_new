<?php

namespace App\Http\Middleware;

use App\Support\MenuBuilder;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * La plantilla raíz de la aplicación.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determina la versión actual de los assets.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props compartidas por defecto con todas las vistas Inertia.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'title' => config('app.name', 'EMCARGA'),
            'appName' => config('app.name', 'EMCARGA'),
            'auth' => [
                'user' => $request->user(),
                'roles' => fn () => $request->user()?->getRoleNames(),
                'permissions' => fn () => $request->user()?->getAllPermissions()->pluck('name'),
            ],
            // Menú dinámico filtrado por los permisos del usuario (Fase 4.5)
            'menu' => fn () => MenuBuilder::para($request->user()),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
        ];
    }
}
