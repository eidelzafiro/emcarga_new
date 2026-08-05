<?php

namespace App\Http\Controllers;

use App\Services\KpiService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $movimientos = $this->movimientosPorRol($rol);
        $secciones = $this->seccionesPorRol($rol);

        return Inertia::render('Dashboard', [
            'title' => 'Dashboard',
            'user' => $user,
            'rol' => $rol,
            'kpis' => $kpis,
            'actividadReciente' => $actividadReciente,
            'movimientos' => $movimientos,
            'secciones' => $secciones,
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

    private function movimientosPorRol(string $rol): array
    {
        return match ($rol) {
            'TECNICA' => [
                ['id' => 2001, 'tipo' => 'Mantenimiento', 'icono' => 'pi pi-wrench', 'color' => '#6366f1', 'descripcion' => 'Servicio programado tractor #T-042', 'monto' => '—', 'estado' => 'Pendiente', 'claseBadge' => 'status-badge-pendiente', 'fecha' => 'Próximo'],
                ['id' => 2002, 'tipo' => 'Batería', 'icono' => 'pi pi-bolt', 'color' => '#d97706', 'descripcion' => 'Rotación de batería #B-018', 'monto' => '—', 'estado' => 'En proceso', 'claseBadge' => 'status-badge-proceso', 'fecha' => 'Hoy'],
                ['id' => 2003, 'tipo' => 'Arrastre', 'icono' => 'pi pi-link', 'color' => '#059669', 'descripcion' => 'Asignación arrastre #A-105', 'monto' => '—', 'estado' => 'Completado', 'claseBadge' => 'status-badge-completado', 'fecha' => 'Ayer'],
                ['id' => 2004, 'tipo' => 'Inspección', 'icono' => 'pi pi-search', 'color' => '#0ea5e9', 'descripcion' => 'Revisión neumáticos flota', 'monto' => '—', 'estado' => 'Programado', 'claseBadge' => 'status-badge-pendiente', 'fecha' => 'Mañana'],
            ],
            'COMERCIAL' => [
                ['id' => 3001, 'tipo' => 'Cotización', 'icono' => 'pi pi-shopping-cart', 'color' => '#059669', 'descripcion' => 'Aforo #AF-089 para Cliente XYZ', 'monto' => '—', 'estado' => 'Pendiente', 'claseBadge' => 'status-badge-pendiente', 'fecha' => 'Hoy'],
                ['id' => 3002, 'tipo' => 'Factura', 'icono' => 'pi pi-file', 'color' => '#6366f1', 'descripcion' => 'Factura #F-2025-050 emitida', 'monto' => '—', 'estado' => 'Completado', 'claseBadge' => 'status-badge-completado', 'fecha' => '25 jul'],
                ['id' => 3003, 'tipo' => 'Tarifa', 'icono' => 'pi pi-tag', 'color' => '#ec4899', 'descripcion' => 'Actualización tarifas carga general', 'monto' => '—', 'estado' => 'Vigente', 'claseBadge' => 'status-badge-completado', 'fecha' => '01 ago'],
            ],
            'CONTABILIDAD' => [
                ['id' => 4001, 'tipo' => 'Ingreso', 'icono' => 'pi pi-arrow-up', 'color' => '#059669', 'descripcion' => 'Facturación acumulada agosto', 'monto' => '—', 'estado' => 'En curso', 'claseBadge' => 'status-badge-proceso', 'fecha' => 'Agosto'],
                ['id' => 4002, 'tipo' => 'Egreso', 'icono' => 'pi pi-arrow-down', 'color' => '#ef4444', 'descripcion' => 'Pago de nómina mensual', 'monto' => '—', 'estado' => 'Programado', 'claseBadge' => 'status-badge-pendiente', 'fecha' => 'Próximo'],
                ['id' => 4003, 'tipo' => 'Balance', 'icono' => 'pi pi-calculator', 'color' => '#6366f1', 'descripcion' => 'Cierre contable julio', 'monto' => '—', 'estado' => 'Completado', 'claseBadge' => 'status-badge-completado', 'fecha' => '31 jul'],
            ],
            'RECHUM' => [
                ['id' => 5001, 'tipo' => 'Ingreso', 'icono' => 'pi pi-user-plus', 'color' => '#059669', 'descripcion' => 'Nuevo trabajador registrado', 'monto' => '—', 'estado' => 'Completado', 'claseBadge' => 'status-badge-completado', 'fecha' => 'Hoy'],
                ['id' => 5002, 'tipo' => 'Vacaciones', 'icono' => 'pi pi-calendar', 'color' => '#d97706', 'descripcion' => 'Solicitud de vacaciones aprobada', 'monto' => '—', 'estado' => 'Aprobado', 'claseBadge' => 'status-badge-completado', 'fecha' => 'Agosto'],
                ['id' => 5003, 'tipo' => 'Cargo', 'icono' => 'pi pi-briefcase', 'color' => '#6366f1', 'descripcion' => 'Nuevo cargo creado: Especialista', 'monto' => '—', 'estado' => 'Activo', 'claseBadge' => 'status-badge-completado', 'fecha' => 'Ayer'],
            ],
            'OPERATIVOS' => [
                ['id' => 6001, 'tipo' => 'Turno', 'icono' => 'pi pi-clock', 'color' => '#059669', 'descripcion' => 'Turno matutino en curso', 'monto' => '—', 'estado' => 'Activo', 'claseBadge' => 'status-badge-proceso', 'fecha' => 'Hoy'],
                ['id' => 6002, 'tipo' => 'Combustible', 'icono' => 'pi pi-fuel', 'color' => '#d97706', 'descripcion' => 'Carga tractor #T-015', 'monto' => '—', 'estado' => 'Completado', 'claseBadge' => 'status-badge-completado', 'fecha' => 'Hoy'],
                ['id' => 6003, 'tipo' => 'Incidencia', 'icono' => 'pi pi-exclamation-triangle', 'color' => '#ef4444', 'descripcion' => 'Neumático bajo reportado', 'monto' => '—', 'estado' => 'Atendido', 'claseBadge' => 'status-badge-completado', 'fecha' => 'Hoy'],
            ],
            'CONFIGURACIONES' => [
                ['id' => 7001, 'tipo' => 'Usuario', 'icono' => 'pi pi-user-plus', 'color' => '#6366f1', 'descripcion' => 'Nuevo usuario registrado', 'monto' => '—', 'estado' => 'Activo', 'claseBadge' => 'status-badge-completado', 'fecha' => 'Hoy'],
                ['id' => 7002, 'tipo' => 'Entidad', 'icono' => 'pi pi-building', 'color' => '#059669', 'descripcion' => 'Entidad configurada', 'monto' => '—', 'estado' => 'Activa', 'claseBadge' => 'status-badge-completado', 'fecha' => 'Reciente'],
            ],
            default => [
                ['id' => 1001, 'tipo' => 'Sistema', 'icono' => 'pi pi-cog', 'color' => '#6366f1', 'descripcion' => 'Panel de control activo', 'monto' => '—', 'estado' => 'Activo', 'claseBadge' => 'status-badge-completado', 'fecha' => 'Ahora'],
                ['id' => 1002, 'tipo' => 'Vehículo', 'icono' => 'pi pi-truck', 'color' => '#059669', 'descripcion' => 'Vehículos registrados en sistema', 'monto' => '—', 'estado' => '—', 'claseBadge' => 'status-badge-completado', 'fecha' => '—'],
            ],
        };
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
