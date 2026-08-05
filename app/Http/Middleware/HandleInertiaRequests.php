<?php

namespace App\Http\Middleware;

use App\Models\Entidad;
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
            'menu' => fn () => MenuBuilder::para(
                $request->user(),
                $request->session()->get('perfil_activo'),
                (int) $request->session()->get('entidad_activa_id')
            ),
            // Contexto de trabajo: entidad activa + fecha de operaciones
            'contexto' => fn () => $this->contextoTrabajo($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'licencia_alerta' => fn () => $request->session()->get('licencia_alerta'),
                'licencia_tipo' => fn () => $request->session()->get('licencia_tipo'),
            ],
        ];
    }

    /**
     * Contexto de trabajo compartido con todas las páginas:
     * entidad activa (con abreviatura), entidades seleccionables
     * y fecha de operaciones. Null para invitados.
     *
     * @return array<string, mixed>|null
     */
    private function contextoTrabajo(Request $request): ?array
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $activaId = (int) $request->session()->get('entidad_activa_id');
        $activa = $activaId ? Entidad::find($activaId) : null;

        $perfilActivo = $request->session()->get('perfil_activo');

        return [
            'entidadActiva' => $activa ? [
                'id' => $activa->id,
                'nombre' => $activa->nombre,
                'abreviatura' => $activa->abreviatura ?? $activa->nombre,
            ] : null,
            'entidades' => $user->entidadesAcceso()
                ->map(fn (Entidad $e) => [
                    'id' => $e->id,
                    'nombre' => $e->nombre,
                    'abreviatura' => $e->abreviatura ?? $e->nombre,
                ])
                ->values()
                ->all(),
            'fechaOperaciones' => $request->session()->get('fecha_operaciones'),
            'perfilActivo' => $perfilActivo,
            // Solo SUPERADMIN puede cambiar de perfil
            'perfiles' => $user->hasRole('SUPERADMIN')
                ? ['SUPERADMIN', 'TECNICA', 'COMERCIAL', 'RECHUM', 'CONTABILIDAD', 'OPERATIVOS', 'CONFIGURACIONES']
                : null,
        ];
    }
}
