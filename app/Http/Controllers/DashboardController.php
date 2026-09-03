<?php

namespace App\Http\Controllers;

use App\Models\BateriasMovimiento;
use App\Models\CartaPorte;
use App\Models\ControlLubricante;
use App\Models\Entidad;
use App\Models\HojasRuta;
use App\Models\NeumaticosMovimiento;
use App\Models\OrdenesTaller;
use App\Models\SolicitudesServicio;
use App\Services\DashboardContabilidadService;
use App\Services\DashboardRecursosHumanosService;
use App\Services\DashboardTecnicoService;
use App\Services\KpiService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Carbon\Carbon;
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
        $fechaRef = $this->fechaOperaciones($request);
        $rol = $this->detectarRol($request, $user);

        // El módulo Técnica usa la Pizarra Operativa como su dashboard,
        // mostrándola directamente en /dashboard (sin redirigir a la URL larga).
        if ($rol === 'TECNICA') {
            $service = app(DashboardTecnicoService::class);

            return Inertia::render('Tecnico/PizarraOperativa', array_merge(
                ['title' => 'Dashboard · Técnico'],
                $service->paraPizarraOperativa($request)
            ));
        }

        // El módulo Contabilidad usa su dashboard específico con datos
        // contables reales (combustible, facturación, costos, amortización).
        if ($rol === 'CONTABILIDAD') {
            $service = app(DashboardContabilidadService::class);

            return Inertia::render('Contabilidad/Dashboard', array_merge(
                ['title' => 'Dashboard · Contabilidad'],
                $service->datos()
            ));
        }

        // El módulo Recursos Humanos usa su dashboard específico con datos
        // de plantilla, documentación, incidencias y salarios.
        if ($rol === 'RECHUM') {
            $service = app(DashboardRecursosHumanosService::class);

            return Inertia::render('RecursosHumanos/Dashboard', array_merge(
                ['title' => 'Dashboard · Recursos Humanos'],
                $service->datos()
            ));
        }

        $kpis = $this->kpiService->paraRol($rol, $entidadId, $fechaRef);
        $actividadReciente = $this->actividadPorRol($rol);
        $movimientos = $this->movimientosPorRol($rol, $entidadId, $fechaRef);
        $secciones = $this->seccionesPorRol($rol);
        $serieActividad = $this->actividadDiaria($entidadId, $fechaRef);

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
        $fechaRef = $this->fechaOperaciones($request);

        return response()->json([
            'kpis' => $this->kpiService->paraRol($rol, $entidadId, $fechaRef),
        ]);
    }

    /**
     * Fecha de operaciones seleccionada en sesión. Los KPIs y la serie de
     * actividad se calculan sobre este mes y no sobre la fecha real del
     * sistema (principio de filtrado por fecha de operaciones).
     */
    private function fechaOperaciones(Request $request): ?Carbon
    {
        $valor = $request->session()->get('fecha_operaciones');

        return $valor ? Carbon::parse($valor) : null;
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

    private function actividadTecnica(): array
    {
        $entidadId = (int) session('entidad_activa_id') ?: null;
        $ids = $entidadId ? Entidad::idsPermitidos($entidadId) : null;

        $otsAbiertas = OrdenesTaller::where('cancelada', false)
            ->where('estado', 'abierta')
            ->when($ids, fn ($q) => $q->whereIn('id_entidad', $ids))
            ->count();

        $neuMontados = NeumaticosMovimiento::whereNull('fecha_retiro')
            ->when($ids, fn ($q) => $q->whereIn('id_entidad', $ids))
            ->count();

        $batRotacion = BateriasMovimiento::whereNull('fecha_retiro')
            ->when($ids, fn ($q) => $q->whereIn('id_entidad', $ids))
            ->count();

        $lubRecientes = ControlLubricante::when($ids, fn ($q) => $q->whereIn('id_entidad', $ids))
            ->where('fecha_cambio', '>=', now()->subDays(30))
            ->count();

        return [
            [
                'titulo' => 'Órdenes de taller abiertas',
                'descripcion' => $otsAbiertas > 0 ? "{$otsAbiertas} en curso" : 'Sin órdenes activas',
                'icono' => 'pi pi-wrench',
                'color' => 'bg-amber-500',
                'hace' => $otsAbiertas > 0 ? 'Requiere atención' : 'Al día',
            ],
            [
                'titulo' => 'Neumáticos montados',
                'descripcion' => "{$neuMontados} en circulación",
                'icono' => 'pi pi-circle-fill',
                'color' => 'bg-violet-500',
                'hace' => 'Inventario activo',
            ],
            [
                'titulo' => 'Baterías en rotación',
                'descripcion' => "{$batRotacion} instaladas",
                'icono' => 'pi pi-bolt',
                'color' => 'bg-orange-500',
                'hace' => 'Monitoreo continuo',
            ],
            [
                'titulo' => 'Lubricaciones recientes',
                'descripcion' => "{$lubRecientes} en los últimos 30 días",
                'icono' => 'pi pi-drop',
                'color' => 'bg-cyan-500',
                'hace' => 'Último mes',
            ],
        ];
    }

    private function actividadPorRol(string $rol): array
    {
        return match ($rol) {
            'TECNICA' => $this->actividadTecnica(),
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
     * cartas de porte y solicitudes de servicio del MES de operaciones de la
     * entidad activa (principio de filtrado por entidad + fecha de operaciones).
     */
    private function movimientosPorRol(string $rol, ?int $entidadId = null, ?Carbon $fechaRef = null): array
    {
        if ($rol === 'TECNICA') {
            return $this->movimientosTecnica($entidadId);
        }

        $movimientos = [];
        // Ventana: mes de operaciones (o el mes actual si no hay fecha en sesión).
        $inicioMes = ($fechaRef ?? now())->copy()->startOfMonth()->toDateString();
        $finMes = ($fechaRef ?? now())->copy()->endOfMonth()->toDateString();

        $hojas = HojasRuta::with('tractivo:id,codigo')
            ->select('id', 'numero', 'fecha_emision', 'fecha_cierre', 'cancelada', 'id_tractivo')
            ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
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

        $cartas = CartaPorte::with('cliente')
            ->select('id', 'numero', 'fecha_emision', 'estado', 'cancelada')
            ->where('cancelada', false)
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
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
            ->whereBetween('fecha_solicitud', [$inicioMes, $finMes])
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
     * Movimientos reales del módulo técnico para el dashboard TECNICA:
     * órdenes de taller abiertas/recientes, montajes de neumáticos,
     * movimientos de baterías y cambios de lubricante. Se mezclan y ordenan
     * por fecha descendente (principio de filtrado por entidad activa).
     */
    private function movimientosTecnica(?int $entidadId = null): array
    {
        $ids = $entidadId ? Entidad::idsPermitidos($entidadId) : null;
        $movimientos = [];

        // ── Órdenes de taller ──
        $ots = OrdenesTaller::with('tractivo:id,codigo')
            ->select('id', 'numero', 'estado', 'cancelada', 'fecha_ingreso', 'id_tractivo')
            ->when($ids, fn ($q) => $q->whereIn('id_entidad', $ids))
            ->where('cancelada', false)
            ->orderByDesc('fecha_ingreso')
            ->limit(6)
            ->get();

        foreach ($ots as $ot) {
            $movimientos[] = [
                'id' => $ot->id,
                'tipo' => 'Orden de taller',
                'icono' => 'pi pi-wrench',
                'color' => '#d97706',
                'descripcion' => "OT {$ot->numero}".($ot->tractivo ? " · {$ot->tractivo->codigo}" : ''),
                'monto' => '—',
                'estado' => match ($ot->estado) {
                    'cerrada' => 'Cerrada',
                    'abierta' => 'Abierta',
                    default => ucfirst($ot->estado ?? 'Abierta'),
                },
                'claseBadge' => match ($ot->estado) {
                    'cerrada' => 'status-badge-completado',
                    default => 'status-badge-proceso',
                },
                'fecha' => $ot->fecha_ingreso?->translatedFormat('d M y'),
                '_ts' => $ot->fecha_ingreso ? $ot->fecha_ingreso->timestamp : 0,
            ];
        }

        // ── Montajes de neumáticos ──
        $neu = NeumaticosMovimiento::with(['neumatico:id,folio', 'tractivo:id,codigo'])
            ->select('id', 'id_neumatico', 'id_tractivo', 'fecha_montaje')
            ->when($ids, fn ($q) => $q->whereIn('id_entidad', $ids))
            ->whereNull('fecha_retiro')
            ->orderByDesc('fecha_montaje')
            ->limit(6)
            ->get();

        foreach ($neu as $n) {
            $movimientos[] = [
                'id' => $n->id,
                'tipo' => 'Neumático',
                'icono' => 'pi pi-circle-fill',
                'color' => '#7c3aed',
                'descripcion' => "Montaje ".($n->neumatico?->folio ?? "Nº{$n->id_neumatico}").($n->tractivo ? " · {$n->tractivo->codigo}" : ''),
                'monto' => '—',
                'estado' => 'Montaje',
                'claseBadge' => 'status-badge-completado',
                'fecha' => $n->fecha_montaje?->translatedFormat('d M y'),
                '_ts' => $n->fecha_montaje ? $n->fecha_montaje->timestamp : 0,
            ];
        }

        // ── Movimientos de baterías ──
        $bat = BateriasMovimiento::with(['bateria:id,folio', 'tractivo:id,codigo'])
            ->select('id', 'id_bateria', 'id_tractivo', 'fecha_movimiento')
            ->when($ids, fn ($q) => $q->whereIn('id_entidad', $ids))
            ->whereNull('fecha_retiro')
            ->orderByDesc('fecha_movimiento')
            ->limit(6)
            ->get();

        foreach ($bat as $b) {
            $movimientos[] = [
                'id' => $b->id,
                'tipo' => 'Batería',
                'icono' => 'pi pi-bolt',
                'color' => '#ea580c',
                'descripcion' => "Movimiento ".($b->bateria?->folio ?? "Nº{$b->id_bateria}").($b->tractivo ? " · {$b->tractivo->codigo}" : ''),
                'monto' => '—',
                'estado' => 'Movimiento',
                'claseBadge' => 'status-badge-proceso',
                'fecha' => $b->fecha_movimiento?->translatedFormat('d M y'),
                '_ts' => $b->fecha_movimiento ? $b->fecha_movimiento->timestamp : 0,
            ];
        }

        // ── Control de lubricantes ──
        $lub = ControlLubricante::with('tractivo:id,codigo')
            ->select('id', 'id_tractivo', 'fecha_cambio')
            ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->orderByDesc('fecha_cambio')
            ->limit(6)
            ->get();

        foreach ($lub as $l) {
            $movimientos[] = [
                'id' => $l->id,
                'tipo' => 'Lubricante',
                'icono' => 'pi pi-drop',
                'color' => '#0891b2',
                'descripcion' => "Cambio de lubricante".($l->tractivo ? " · {$l->tractivo->codigo}" : ''),
                'monto' => '—',
                'estado' => 'Cambio',
                'claseBadge' => 'status-badge-completado',
                'fecha' => $l->fecha_cambio?->translatedFormat('d M y'),
                '_ts' => $l->fecha_cambio ? $l->fecha_cambio->timestamp : 0,
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
     * para alimentar el gráfico "Resumen de actividad". Ventana: MES completo
     * de la fecha de operaciones (no ventana móvil), filtrado por entidad.
     */
    private function actividadDiaria(?int $entidadId = null, ?Carbon $fechaReferencia = null): array
    {
        // Mes de operaciones completo: día 1 → fin de mes.
        $base = $fechaReferencia ?? now();
        $desde = $base->copy()->startOfMonth()->toDateString();
        $hasta = $base->copy()->endOfMonth()->toDateString();

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
        for ($d = $base->copy()->startOfMonth(); $d->lte($base->copy()->endOfMonth()); $d->addDay()) {
            $fecha = $d->toDateString();
            $serie[] = [
                'fecha' => $fecha,
                'hojas' => (int) ($hojas[$fecha] ?? 0),
                'cartas' => (int) ($cartas[$fecha] ?? 0),
                'solicitudes' => (int) ($solicitudes[$fecha] ?? 0),
            ];
        }

        return $serie;
    }

    private function seccionesPorRol(string $rol): array
    {
        return match ($rol) {
            'TECNICA' => [
                ['titulo' => 'Flota Técnica', 'descripcion' => 'Composición y KPIs de flota', 'ruta' => 'tecnico.flota', 'icono' => 'pi pi-truck', 'color' => 'bg-blue-500'],
                ['titulo' => 'Taller en Vivo', 'descripcion' => 'Vehículos en taller y órdenes', 'ruta' => 'tecnico.taller', 'icono' => 'pi pi-wrench', 'color' => 'bg-amber-500'],
                ['titulo' => 'Salud de Componentes', 'descripcion' => 'Motores, neumáticos, baterías', 'ruta' => 'tecnico.componentes', 'icono' => 'pi pi-cog', 'color' => 'bg-violet-500'],
                ['titulo' => 'Pizarra Operativa', 'descripcion' => 'Tablero de flota, operaciones y taller en una vista', 'ruta' => 'tecnico.dashboard', 'icono' => 'pi pi-sliders-h', 'color' => 'bg-cyan-500'],
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
