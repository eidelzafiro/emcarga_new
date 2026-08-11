<?php

namespace App\Http\Controllers;

use App\Models\CartaPorte;
use App\Models\HojasRuta;
use App\Models\SolicitudesServicio;
use App\Services\KpiService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        protected KpiService $kpiService,
    ) {}

    private const ROLES_DISPONIBLES = [
        'SUPERADMIN', 'TECNICA', 'COMERCIAL', 'CONTABILIDAD',
        'RECHUM', 'OPERATIVOS', 'CONFIGURACIONES',
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        $entidadId = (int) $request->session()->get('entidad_activa_id') ?: null;
        $rol = $this->detectarRol($request, $user);
        $kpis = $this->kpiService->paraRol($rol, $entidadId);
        $actividadReciente = $this->actividadPorRol($rol);
        $movimientos = $this->movimientosPorRol($rol, $entidadId);
        $secciones = $this->seccionesPorRol($rol);
        $serieActividad = $this->actividadDiaria($entidadId);

        return Inertia::render('Dashboard', [
            'title' => 'Dashboard',
            'user' => $user,
            'rol' => $rol,
            'kpis' => $kpis,
            'actividadReciente' => $actividadReciente,
            'movimientos' => $movimientos,
            'secciones' => $secciones,
            'serieActividad' => $serieActividad,
        ]);
    }

    public function kpis(Request $request)
    {
        $entidadId = (int) $request->session()->get('entidad_activa_id') ?: null;
        $rol = $this->detectarRol($request, $request->user());

        return response()->json([
            'kpis' => $this->kpiService->paraRol($rol, $entidadId),
        ]);
    }

    private function detectarRol(Request $request, $user): string
    {
        $perfilActivo = $request->session()->get('perfil_activo');

        if ($perfilActivo && $user->hasRole('SUPERADMIN') && in_array($perfilActivo, self::ROLES_DISPONIBLES)) {
            return $perfilActivo;
        }

        $roles = $user->getRoleNames();

        foreach (self::ROLES_DISPONIBLES as $rol) {
            if ($roles->contains($rol)) {
                return $rol;
            }
        }

        return 'default';
    }

    private function actividadPorRol(string $rol): array
    {
        return match ($rol) {
            'TECNICA' => [
                ['titulo' => 'Revisión de flota', 'descripcion' => 'Estado de tractivos y arrastres', 'icono' => 'pi pi-truck', 'color' => 'bg-blue-500', 'hace' => 'Panel técnico'],
                ['titulo' => 'Mantenimiento programado', 'descripcion' => 'Próximos servicios de taller', 'icono' => 'pi pi-wrench', 'color' => 'bg-amber-500', 'hace' => 'Esta semana'],
                ['titulo' => 'Baterías en rotación', 'descripcion' => 'Control de carga y descarga', 'icono' => 'pi pi-bolt', 'color' => 'bg-orange-500', 'hace' => 'Monitoreo continuo'],
                ['titulo' => 'Asociaciones activas', 'descripcion' => 'Tractores con arrastres asignados', 'icono' => 'pi pi-link', 'color' => 'bg-emerald-500', 'hace' => 'Panel técnico'],
            ],
            'COMERCIAL' => [
                ['titulo' => 'Cotizaciones pendientes', 'descripcion' => 'Aforos por facturar', 'icono' => 'pi pi-shopping-cart', 'color' => 'bg-blue-500', 'hace' => 'Por atender'],
                ['titulo' => 'Facturación reciente', 'descripcion' => 'Últimas facturas emitidas', 'icono' => 'pi pi-file', 'color' => 'bg-emerald-500', 'hace' => 'Actualizado'],
                ['titulo' => 'Tarifas configuradas', 'descripcion' => 'Precios para servicios', 'icono' => 'pi pi-tag', 'color' => 'bg-violet-500', 'hace' => 'Vigentes'],
                ['titulo' => 'Clientes frecuentes', 'descripcion' => 'Principales clientes del mes', 'icono' => 'pi pi-star', 'color' => 'bg-amber-500', 'hace' => 'Resumen mensual'],
            ],
            'CONTABILIDAD' => [
                ['titulo' => 'Balance del mes', 'descripcion' => 'Ingresos vs Egresos', 'icono' => 'pi pi-chart-line', 'color' => 'bg-emerald-500', 'hace' => 'Cierre mensual'],
                ['titulo' => 'Centros de costo', 'descripcion' => 'Distribución por área', 'icono' => 'pi pi-chart-bar', 'color' => 'bg-blue-500', 'hace' => 'Actualizado'],
                ['titulo' => 'Pagos procesados', 'descripcion' => 'Nómina y proveedores', 'icono' => 'pi pi-money-bill', 'color' => 'bg-amber-500', 'hace' => 'Este período'],
                ['titulo' => 'Reportes contables', 'descripcion' => 'Disponibles para descarga', 'icono' => 'pi pi-download', 'color' => 'bg-violet-500', 'hace' => 'Generados'],
            ],
            'RECHUM' => [
                ['titulo' => 'Plantilla actual', 'descripcion' => 'Distribución por cargo y entidad', 'icono' => 'pi pi-id-card', 'color' => 'bg-blue-500', 'hace' => 'Actualizado'],
                ['titulo' => 'Nuevos ingresos', 'descripcion' => 'Trabajadores incorporados', 'icono' => 'pi pi-user-plus', 'color' => 'bg-emerald-500', 'hace' => 'Este mes'],
                ['titulo' => 'Vacaciones pendientes', 'descripcion' => 'Por programar este período', 'icono' => 'pi pi-calendar', 'color' => 'bg-amber-500', 'hace' => 'Próximas'],
                ['titulo' => 'Cargos definidos', 'descripcion' => 'Plazas y categorías activas', 'icono' => 'pi pi-briefcase', 'color' => 'bg-violet-500', 'hace' => 'Panel RRHH'],
            ],
            'OPERATIVOS' => [
                ['titulo' => 'Turno actual', 'descripcion' => 'Operaciones en curso', 'icono' => 'pi pi-clock', 'color' => 'bg-blue-500', 'hace' => 'En vivo'],
                ['titulo' => 'Despacho de combustible', 'descripcion' => 'Litros asignados hoy', 'icono' => 'pi pi-fuel', 'color' => 'bg-amber-500', 'hace' => 'Jornada actual'],
                ['titulo' => 'Vehículos disponibles', 'descripcion' => 'Listos para operar', 'icono' => 'pi pi-check-circle', 'color' => 'bg-emerald-500', 'hace' => 'Estado actual'],
                ['titulo' => 'Novedades del día', 'descripcion' => 'Incidencias reportadas', 'icono' => 'pi pi-exclamation-triangle', 'color' => 'bg-red-500', 'hace' => 'Últimas 24h'],
            ],
            'CONFIGURACIONES' => [
                ['titulo' => 'Sistema operativo', 'descripcion' => 'Estado de los servicios', 'icono' => 'pi pi-cog', 'color' => 'bg-emerald-500', 'hace' => 'Todo en orden'],
                ['titulo' => 'Últimos accesos', 'descripcion' => 'Usuarios conectados recientemente', 'icono' => 'pi pi-sign-in', 'color' => 'bg-blue-500', 'hace' => 'Registro de actividad'],
                ['titulo' => 'Entidades activas', 'descripcion' => 'Organizaciones en el sistema', 'icono' => 'pi pi-building', 'color' => 'bg-indigo-500', 'hace' => 'Configuración global'],
                ['titulo' => 'Backup disponible', 'descripcion' => 'Copia de seguridad al día', 'icono' => 'pi pi-shield', 'color' => 'bg-violet-500', 'hace' => 'Automático'],
            ],
            default => [
                ['titulo' => 'Bienvenido a Zafiro', 'descripcion' => 'Sistema de gestión integral EMCARGA', 'icono' => 'pi pi-star', 'color' => 'bg-blue-500', 'hace' => 'Dashboard'],
                ['titulo' => 'Panel de control', 'descripcion' => 'Resumen de operaciones', 'icono' => 'pi pi-chart-bar', 'color' => 'bg-emerald-500', 'hace' => 'General'],
                ['titulo' => 'Explorar módulos', 'descripcion' => 'Acceda a las secciones desde el menú', 'icono' => 'pi pi-compass', 'color' => 'bg-violet-500', 'hace' => 'Navegación'],
                ['titulo' => 'Soporte disponible', 'descripcion' => 'Consulte la documentación', 'icono' => 'pi pi-question-circle', 'color' => 'bg-amber-500', 'hace' => 'Ayuda'],
            ],
        };
    }

    /**
     * Últimos movimientos con datos reales de la operación: hojas de ruta,
     * cartas de porte y solicitudes de servicio recientes.
     */
    private function movimientosPorRol(string $rol, ?int $entidadId = null): array
    {
        $movimientos = [];

        $hojas = HojasRuta::with('tractivo:id,codigo')
            ->select('id', 'numero', 'fecha_emision', 'fecha_cierre', 'cancelada', 'id_tractivo')
            ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->whereNotNull('fecha_emision')
            ->orderByDesc('fecha_emision')
            ->limit(6)
            ->get();

        foreach ($hojas as $hr) {
            $movimientos[] = [
                'id' => $hr->id,
                'tipo' => 'Hoja de ruta',
                'icono' => 'pi pi-truck',
                'color' => '#059669',
                'descripcion' => "HR {$hr->numero}".($hr->tractivo ? " · {$hr->tractivo->codigo}" : ''),
                'monto' => '—',
                'estado' => $hr->cancelada ? 'Cancelada' : ($hr->fecha_cierre ? 'Cerrada' : 'Abierta'),
                'claseBadge' => $hr->cancelada ? 'status-badge-cancelado' : ($hr->fecha_cierre ? 'status-badge-completado' : 'status-badge-proceso'),
                'fecha' => $hr->fecha_emision?->translatedFormat('d M y'),
                '_ts' => $hr->fecha_emision ? $hr->fecha_emision->timestamp : 0,
            ];
        }

        $cartas = CartaPorte::with('cliente:id,nombre')
            ->select('id', 'numero', 'fecha_emision', 'estado', 'cancelada', 'id_cliente')
            ->where('cancelada', false)
            ->whereNotNull('fecha_emision')
            ->when($entidadId, fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->where('id_entidad', $entidadId)))
            ->orderByDesc('fecha_emision')
            ->limit(6)
            ->get();

        foreach ($cartas as $cp) {
            $movimientos[] = [
                'id' => $cp->id,
                'tipo' => 'Carta de porte',
                'icono' => 'pi pi-file',
                'color' => '#2563eb',
                'descripcion' => "CP {$cp->numero}".($cp->cliente ? " · {$cp->cliente->nombre}" : ''),
                'monto' => '—',
                'estado' => ucfirst($cp->estado),
                'claseBadge' => $cp->estado === 'emitida' ? 'status-badge-proceso' : 'status-badge-completado',
                'fecha' => $cp->fecha_emision?->translatedFormat('d M y'),
                '_ts' => $cp->fecha_emision ? $cp->fecha_emision->timestamp : 0,
            ];
        }

        $solicitudes = SolicitudesServicio::with('cliente:id,nombre')
            ->select('id', 'numero', 'fecha_solicitud', 'estado', 'id_cliente')
            ->whereNotNull('fecha_solicitud')
            ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->orderByDesc('fecha_solicitud')
            ->limit(4)
            ->get();

        foreach ($solicitudes as $sol) {
            $movimientos[] = [
                'id' => $sol->id,
                'tipo' => 'Solicitud',
                'icono' => 'pi pi-shopping-cart',
                'color' => '#d97706',
                'descripcion' => "SOL {$sol->numero}".($sol->cliente ? " · {$sol->cliente->nombre}" : ''),
                'monto' => '—',
                'estado' => match ($sol->estado) {
                    'ejecutada' => 'Ejecutada',
                    'en_proceso' => 'En proceso',
                    default => 'Pendiente',
                },
                'claseBadge' => match ($sol->estado) {
                    'ejecutada' => 'status-badge-completado',
                    'en_proceso' => 'status-badge-proceso',
                    default => 'status-badge-pendiente',
                },
                'fecha' => $sol->fecha_solicitud?->translatedFormat('d M y'),
                '_ts' => $sol->fecha_solicitud ? $sol->fecha_solicitud->timestamp : 0,
            ];
        }

        usort($movimientos, fn ($a, $b) => $b['_ts'] <=> $a['_ts']);

        return collect(array_slice($movimientos, 0, 12))
            ->map(fn ($m) => Arr::except($m, ['_ts']))
            ->values()
            ->all();
    }

    /**
     * Serie diaria de emisión de hojas de ruta, cartas de porte y solicitudes
     * para alimentar el gráfico de actividad con datos reales.
     */
    private function actividadDiaria(?int $entidadId = null, int $dias = 90): array
    {
        $desde = now()->subDays($dias - 1)->toDateString();
        $hasta = now()->toDateString();

        $hojas = HojasRuta::whereBetween('fecha_emision', [$desde, $hasta])
            ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->selectRaw('fecha_emision, count(*) as total')
            ->groupBy('fecha_emision')
            ->pluck('total', 'fecha_emision');

        $cartas = CartaPorte::whereBetween('fecha_emision', [$desde, $hasta])
            ->where('cancelada', false)
            ->when($entidadId, fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->where('id_entidad', $entidadId)))
            ->selectRaw('fecha_emision, count(*) as total')
            ->groupBy('fecha_emision')
            ->pluck('total', 'fecha_emision');

        $solicitudes = SolicitudesServicio::whereBetween('fecha_solicitud', [$desde, $hasta])
            ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->selectRaw('fecha_solicitud, count(*) as total')
            ->groupBy('fecha_solicitud')
            ->pluck('total', 'fecha_solicitud');

        $serie = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $serie[] = [
                'fecha' => $d,
                'hojas' => (int) ($hojas[$d] ?? 0),
                'cartas' => (int) ($cartas[$d] ?? 0),
                'solicitudes' => (int) ($solicitudes[$d] ?? 0),
            ];
        }

        return $serie;
    }

    private function seccionesPorRol(string $rol): array
    {
        return match ($rol) {
            'TECNICA' => [
                ['titulo' => 'Resumen de Flota', 'descripcion' => 'Estado actual de tractivos, arrastres y baterías', 'ruta' => null, 'icono' => 'pi pi-truck', 'color' => 'bg-blue-500'],
                ['titulo' => 'Mantenimiento', 'descripcion' => 'Órdenes de taller y servicios programados', 'ruta' => null, 'icono' => 'pi pi-wrench', 'color' => 'bg-amber-500'],
                ['titulo' => 'Inventario Técnico', 'descripcion' => 'Neumáticos, baterías, piezas y lubricantes', 'ruta' => null, 'icono' => 'pi pi-box', 'color' => 'bg-emerald-500'],
            ],
            'COMERCIAL' => [
                ['titulo' => 'Operaciones', 'descripcion' => 'Aforos, facturación y prefacturas', 'ruta' => null, 'icono' => 'pi pi-shopping-cart', 'color' => 'bg-blue-500'],
                ['titulo' => 'Tarifas', 'descripcion' => 'Configuración de precios y recargos', 'ruta' => null, 'icono' => 'pi pi-tag', 'color' => 'bg-amber-500'],
                ['titulo' => 'Clientes y Contratos', 'descripcion' => 'Gestión de clientes y acuerdos comerciales', 'ruta' => null, 'icono' => 'pi pi-building', 'color' => 'bg-violet-500'],
            ],
            'CONTABILIDAD' => [
                ['titulo' => 'Finanzas', 'descripcion' => 'Ingresos, egresos y balances', 'ruta' => null, 'icono' => 'pi pi-chart-line', 'color' => 'bg-blue-500'],
                ['titulo' => 'Nómina', 'descripcion' => 'Salarios y compensaciones', 'ruta' => null, 'icono' => 'pi pi-money-bill', 'color' => 'bg-amber-500'],
                ['titulo' => 'Reportes', 'descripcion' => 'Informes financieros y exportaciones', 'ruta' => null, 'icono' => 'pi pi-file-pdf', 'color' => 'bg-emerald-500'],
            ],
            'RECHUM' => [
                ['titulo' => 'Personal', 'descripcion' => 'Plantilla, cargos y bolsa de trabajo', 'ruta' => null, 'icono' => 'pi pi-id-card', 'color' => 'bg-blue-500'],
                ['titulo' => 'Nómina y Salarios', 'descripcion' => 'Cálculo y pago de nómina', 'ruta' => null, 'icono' => 'pi pi-money-bill', 'color' => 'bg-amber-500'],
                ['titulo' => 'Vacaciones', 'descripcion' => 'Programación y control de ausencias', 'ruta' => null, 'icono' => 'pi pi-calendar', 'color' => 'bg-violet-500'],
            ],
            'OPERATIVOS' => [
                ['titulo' => 'Turnos', 'descripcion' => 'Control de jornadas y asignaciones', 'ruta' => null, 'icono' => 'pi pi-clock', 'color' => 'bg-blue-500'],
                ['titulo' => 'Combustible', 'descripcion' => 'Control de despachos y consumo', 'ruta' => null, 'icono' => 'pi pi-fuel', 'color' => 'bg-amber-500'],
                ['titulo' => 'Novedades', 'descripcion' => 'Registro de incidencias diarias', 'ruta' => null, 'icono' => 'pi pi-exclamation-triangle', 'color' => 'bg-red-500'],
            ],
            'CONFIGURACIONES' => [
                ['titulo' => 'Sistema', 'descripcion' => 'Configuración general y parámetros', 'ruta' => null, 'icono' => 'pi pi-cog', 'color' => 'bg-blue-500'],
                ['titulo' => 'Usuarios', 'descripcion' => 'Gestión de cuentas y permisos', 'ruta' => null, 'icono' => 'pi pi-users', 'color' => 'bg-indigo-500'],
                ['titulo' => 'Catálogos', 'descripcion' => 'Tablas maestras del sistema', 'ruta' => null, 'icono' => 'pi pi-database', 'color' => 'bg-emerald-500'],
            ],
            default => [
                ['titulo' => 'General', 'descripcion' => 'Panel de control general', 'ruta' => null, 'icono' => 'pi pi-chart-bar', 'color' => 'bg-blue-500'],
                ['titulo' => 'Explorar', 'descripcion' => 'Navegue por los módulos disponibles', 'ruta' => null, 'icono' => 'pi pi-compass', 'color' => 'bg-violet-500'],
            ],
        };
    }
}
