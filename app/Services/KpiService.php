<?php

namespace App\Services;

use App\Models\Aforo;
use App\Models\Bateria;
use App\Models\Bolsa;
use App\Models\Cargo;
use App\Models\CartaPorte;
use App\Models\Entidad;
use App\Models\HojasRuta;
use App\Models\SolicitudesServicio;
use App\Models\Tractivo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KpiService
{
    /**
     * Fecha de referencia para los KPIs. Por defecto es el mes actual, pero
     * cuando el usuario tiene seleccionada una "fecha de operaciones" en
     * sesión, los KPIs deben calcularse sobre ese mes (no sobre la fecha real).
     */
    private ?Carbon $fechaReferencia = null;

    private function ref(): Carbon
    {
        return $this->fechaReferencia ?? now();
    }

    private function periodoMes(): array
    {
        $r = $this->ref();

        return [$r->copy()->startOfMonth()->toDateString(), $r->copy()->endOfMonth()->toDateString()];
    }

    private function cartasDelMes(?int $entidadId = null)
    {
        return CartaPorte::where('cancelada', false)
            ->whereBetween('fecha_emision', $this->periodoMes())
            ->when($entidadId, fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->where('id_entidad', $entidadId)));
    }

    /**
     * Ingresos del mes: se suman desde los aforos (los fletes viven en `aforos`,
     * Fase 4d), con scoping de entidad vía carta → HR → tractivo.
     */
    private function ingresosDelMes(?int $entidadId = null): float
    {
        $r = $this->ref();

        return Aforo::whereYear('fecha_parte', $r->year)
            ->whereMonth('fecha_parte', $r->month)
            ->when($entidadId, fn ($q) => $q->whereHas('cartaPorte.hojaRuta.tractivo', fn ($t) => $t->where('id_entidad', $entidadId)))
            ->sum('ingreso_mt');
    }

    private function fmtMoneda(mixed $valor): string
    {
        return '$'.number_format((float) $valor, 2, '.', ',');
    }

    public function calcular(?int $entidadId = null, ?Carbon $fechaReferencia = null): array
    {
        return $this->paraRol('default', $entidadId, $fechaReferencia);
    }

    public function paraRol(string $rol, ?int $entidadId = null, ?Carbon $fechaReferencia = null): array
    {
        $this->fechaReferencia = $fechaReferencia;

        return match ($rol) {
            'SUPERADMIN' => $this->kpisSuperadmin($entidadId),
            'TECNICA' => $this->kpisTecnica($entidadId),
            'COMERCIAL' => $this->kpisComercial($entidadId),
            'CONTABILIDAD' => $this->kpisContabilidad($entidadId),
            'RECHUM' => $this->kpisRechum($entidadId),
            'OPERATIVOS' => $this->kpisOperativos($entidadId),
            'CONFIGURACIONES' => $this->kpisConfiguraciones($entidadId),
            default => $this->kpisDefault($entidadId),
        };
    }

    private function scopeEntidad($query, ?int $entidadId): void
    {
        if ($entidadId && $entidadId > 0) {
            $query->where('id_entidad', $entidadId);
        }
    }

    private function kpisSuperadmin(?int $entidadId): array
    {
        $usuariosQuery = User::query();
        $this->scopeEntidad($usuariosQuery, $entidadId);
        $usuarios = $usuariosQuery->count();

        return [
            [
                'label' => 'Usuarios',
                'valor' => (string) $usuarios,
                'subtexto' => $entidadId ? 'En la entidad activa' : 'Registrados en el sistema',
                'icono' => 'pi pi-users',
                'color' => 'bg-blue-500',
            ],
            [
                'label' => 'Entidades',
                'valor' => $entidadId ? '1' : (string) Entidad::count(),
                'subtexto' => $entidadId ? 'Entidad activa' : 'Organizaciones activas',
                'icono' => 'pi pi-building',
                'color' => 'bg-indigo-500',
            ],
            [
                'label' => 'Vehículos',
                'valor' => (string) Tractivo::when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))->count(),
                'subtexto' => 'Total de tractivos registrados',
                'icono' => 'pi pi-truck',
                'color' => 'bg-emerald-500',
            ],
            [
                'label' => 'Personal',
                'valor' => (string) Bolsa::when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))->count(),
                'subtexto' => 'Trabajadores en plantilla',
                'icono' => 'pi pi-id-card',
                'color' => 'bg-violet-500',
            ],
        ];
    }

    private function kpisTecnica(?int $entidadId): array
    {
        $ids = $entidadId ? Entidad::idsPermitidos($entidadId) : null;
        $inSql = $ids ? 'AND t.id_entidad IN ('.implode(',', $ids).')' : '';
        $inSqlA = $ids ? 'AND a.id_entidad IN ('.implode(',', $ids).')' : '';

        // ── KPI Vehículos: agrupados por TIPO DE EQUIPO, activos/en taller ──
        $enTallerSql = "(t.estado = 'taller' OR EXISTS (
            SELECT 1 FROM ordenes_taller ot
            WHERE ot.id_tractivo = t.id AND ot.deleted_at IS NULL
              AND ot.cancelada = false AND ot.estado = 'abierta'))";

        $vehiculos = DB::select("
            SELECT COALESCE(NULLIF(TRIM(te.nombre), ''), 'Sin tipo') AS tipo,
                   COUNT(*) AS total,
                   SUM(CASE WHEN $enTallerSql THEN 1 ELSE 0 END) AS en_taller
            FROM tractivos t
            LEFT JOIN tipo_vehiculos tv ON tv.id = t.id_tipo_vehiculo
            LEFT JOIN tipos_equipos te ON te.id = tv.id_tipo_equipo
            WHERE t.deleted_at IS NULL ".$inSql."
            GROUP BY 1
            ORDER BY 1
        ");

        $totalVehiculos = array_sum(array_column($vehiculos, 'total'));
        $totalTaller = (int) array_sum(array_column($vehiculos, 'en_taller'));
        $detalleVehiculos = collect($vehiculos)
            ->map(fn ($v) => [
                'etiqueta' => $v->tipo,
                'valor' => ((int) $v->total - (int) $v->en_taller).' act / '.$v->en_taller.' tall',
            ])
            ->values()
            ->all();

        // ── KPI Agregados: motores/cajas/diferenciales trabajando / en taller ──
        // "En taller" = asignado a un tractivo en taller (o con OT abierta);
        // "trabajando" = asignado a tractivo operativo; resto: disponible/baja.
        $agregados = [];
        foreach (['Motores' => 'motores', 'Cajas' => 'cajas', 'Diferenciales' => 'diferenciales'] as $etiqueta => $tabla) {
            $fila = DB::selectOne("
                SELECT COUNT(*) AS trabajando,
                       SUM(CASE WHEN $enTallerSql THEN 1 ELSE 0 END) AS en_taller
                FROM {$tabla} a
                INNER JOIN tractivos t ON t.id = a.id_tractivo AND t.deleted_at IS NULL
                WHERE a.deleted_at IS NULL AND a.estado NOT IN ('baja')
                  ".$inSqlA."
            ");
            $agregados[] = [
                'etiqueta' => $etiqueta,
                'valor' => (int) ($fila->trabajando ?? 0).' trab / '.(int) ($fila->en_taller ?? 0).' tall',
            ];
        }

        // ── KPI Baterías: cantidades por meses desde la fecha de instalación ──
        $batFilas = Bateria::query()
            ->whereNull('deleted_at')
            ->whereNull('fecha_retiro')
            ->whereNotNull('fecha_instalacion')
            ->when($ids, fn ($q) => $q->whereIn('id_entidad', $ids))
            ->selectRaw("
                SUM(TIMESTAMPDIFF(MONTH, fecha_instalacion, NOW()) < 6) AS r1,
                SUM(TIMESTAMPDIFF(MONTH, fecha_instalacion, NOW()) BETWEEN 6 AND 11) AS r2,
                SUM(TIMESTAMPDIFF(MONTH, fecha_instalacion, NOW()) BETWEEN 12 AND 23) AS r3,
                SUM(TIMESTAMPDIFF(MONTH, fecha_instalacion, NOW()) >= 24) AS r4
            ")
            ->first();
        $detalleBaterias = [
            ['etiqueta' => '< 6 meses', 'valor' => (int) ($batFilas->r1 ?? 0)],
            ['etiqueta' => '6-11 meses', 'valor' => (int) ($batFilas->r2 ?? 0)],
            ['etiqueta' => '12-23 meses', 'valor' => (int) ($batFilas->r3 ?? 0)],
            ['etiqueta' => '≥ 24 meses', 'valor' => (int) ($batFilas->r4 ?? 0)],
        ];

        // ── KPI Neumáticos: cantidades por kms recorridos en rangos amplios ──
        $neuFilas = \App\Models\Neumatico::query()
            ->whereNull('deleted_at')
            ->whereNull('fecha_retiro')
            ->when($ids, fn ($q) => $q->whereIn('id_entidad', $ids))
            ->selectRaw("
                SUM(kilometraje < 20000) AS r1,
                SUM(kilometraje BETWEEN 20000 AND 49999) AS r2,
                SUM(kilometraje BETWEEN 50000 AND 79999) AS r3,
                SUM(kilometraje >= 80000) AS r4
            ")
            ->first();
        $detalleNeumaticos = [
            ['etiqueta' => '< 20 mil kms', 'valor' => (int) ($neuFilas->r1 ?? 0)],
            ['etiqueta' => '20-50 mil kms', 'valor' => (int) ($neuFilas->r2 ?? 0)],
            ['etiqueta' => '50-80 mil kms', 'valor' => (int) ($neuFilas->r3 ?? 0)],
            ['etiqueta' => '≥ 80 mil kms', 'valor' => (int) ($neuFilas->r4 ?? 0)],
        ];

        return [
            [
                'label' => 'Vehículos',
                'valor' => $totalVehiculos > 0 ? ($totalVehiculos - $totalTaller)." / {$totalVehiculos}" : '—',
                'subtexto' => "Activos · {$totalTaller} en taller",
                'icono' => 'pi pi-truck',
                'color' => 'bg-emerald-500',
                'detalle' => $detalleVehiculos,
            ],
            [
                'label' => 'Agregados',
                'valor' => (string) array_sum(array_map(
                    fn ($a) => (int) explode(' ', explode('/', $a['valor'])[0])[0], $agregados)),
                'subtexto' => 'Trabajando / en taller',
                'icono' => 'pi pi-cog',
                'color' => 'bg-blue-500',
                'detalle' => $agregados,
            ],
            [
                'label' => 'Baterías',
                'valor' => (string) array_sum(array_column($detalleBaterias, 'valor')),
                'subtexto' => 'Montadas, por antigüedad',
                'icono' => 'pi pi-bolt',
                'color' => 'bg-orange-500',
                'detalle' => $detalleBaterias,
            ],
            [
                'label' => 'Neumáticos',
                'valor' => (string) array_sum(array_column($detalleNeumaticos, 'valor')),
                'subtexto' => 'Montados, por kms recorridos',
                'icono' => 'pi pi-circle-fill',
                'color' => 'bg-violet-500',
                'detalle' => $detalleNeumaticos,
            ],
        ];
    }

    private function kpisComercial(?int $entidadId): array
    {
        $cartasMes = $this->cartasDelMes($entidadId);
        $cartas = (clone $cartasMes)->count();
        $ingresos = $this->ingresosDelMes($entidadId);
        $porRecepcionar = CartaPorte::where('cancelada', false)
            ->where('estado', 'emitida')
            ->when($entidadId, fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->where('id_entidad', $entidadId)))
            ->count();
        $clientesActivos = CartaPorte::where('cancelada', false)
            ->whereBetween('fecha_emision', $this->periodoMes())
            ->whereHas('solicitud')
            ->when($entidadId, fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->where('id_entidad', $entidadId)))
            ->with('solicitud:id,id_cliente')
            ->get()
            ->map(fn ($c) => $c->solicitud?->id_cliente)
            ->filter()
            ->unique()
            ->count();

        return [
            [
                'label' => 'Cartas de porte del mes',
                'valor' => (string) $cartas,
                'subtexto' => $entidadId ? 'Entidad activa' : 'Emitidas este período',
                'icono' => 'pi pi-file',
                'color' => 'bg-emerald-500',
            ],
            [
                'label' => 'Ingresos del mes',
                'valor' => $ingresos > 0 ? $this->fmtMoneda($ingresos) : '—',
                'subtexto' => $entidadId ? 'Entidad activa' : 'Ingresos acumulados (MN)',
                'icono' => 'pi pi-dollar',
                'color' => 'bg-cyan-500',
            ],
            [
                'label' => 'CP por recepcionar',
                'valor' => (string) $porRecepcionar,
                'subtexto' => 'Cartas emitidas en curso',
                'icono' => 'pi pi-clock',
                'color' => 'bg-amber-500',
            ],
            [
                'label' => 'Clientes activos',
                'valor' => (string) $clientesActivos,
                'subtexto' => 'Con cartas de porte en el mes',
                'icono' => 'pi pi-building',
                'color' => 'bg-violet-500',
            ],
        ];
    }

    private function kpisContabilidad(?int $entidadId): array
    {
        $ingresos = $this->ingresosDelMes($entidadId);
        $emitidasMes = (clone $this->cartasDelMes($entidadId))->count();

        return [
            [
                'label' => 'Ingresos del mes',
                'valor' => $ingresos > 0 ? $this->fmtMoneda($ingresos) : '—',
                'subtexto' => $entidadId ? 'Entidad activa' : 'Total facturado (MN)',
                'icono' => 'pi pi-arrow-up',
                'color' => 'bg-emerald-500',
            ],
            [
                'label' => 'Egresos del mes',
                'valor' => '—',
                'subtexto' => $entidadId ? 'Entidad activa' : 'Gastos acumulados',
                'icono' => 'pi pi-arrow-down',
                'color' => 'bg-red-500',
            ],
            [
                'label' => 'Cartas emitidas',
                'valor' => (string) $emitidasMes,
                'subtexto' => 'Giros en el período',
                'icono' => 'pi pi-chart-bar',
                'color' => 'bg-blue-500',
            ],
            [
                'label' => 'Balance',
                'valor' => '—',
                'subtexto' => 'Ingresos - Egresos',
                'icono' => 'pi pi-calculator',
                'color' => 'bg-violet-500',
            ],
        ];
    }

    private function kpisRechum(?int $entidadId): array
    {
        $bolsaQuery = Bolsa::with('cargo')->where('activo', true);
        $this->scopeEntidad($bolsaQuery, $entidadId);

        $trabajadores = $bolsaQuery->get();

        $servicio = app(NotificarDocumentosChofer::class);
        $choferes = $trabajadores->filter(fn ($b) => $servicio->esChofer($b))->values();
        $habilitados = $choferes->filter(fn ($b) => $servicio->habilitado($b))->count();

        $cargosQuery = Cargo::where('activo', true);
        $this->scopeEntidad($cargosQuery, $entidadId);

        return [
            [
                'label' => 'Plantilla',
                'valor' => (string) $trabajadores->count(),
                'subtexto' => 'Trabajadores activos',
                'icono' => 'pi pi-id-card',
                'color' => 'bg-blue-500',
            ],
            [
                'label' => 'Choferes',
                'valor' => (string) $choferes->count(),
                'subtexto' => 'Del total de trabajadores',
                'icono' => 'pi pi-truck',
                'color' => 'bg-emerald-500',
            ],
            [
                'label' => 'Habilitados',
                'valor' => "{$habilitados}/{$choferes->count()}",
                'subtexto' => 'Choferes con documentos vigentes',
                'icono' => 'pi pi-check-circle',
                'color' => 'bg-amber-500',
            ],
            [
                'label' => 'Cargos',
                'valor' => (string) $cargosQuery->count(),
                'subtexto' => 'Definiciones de plaza',
                'icono' => 'pi pi-briefcase',
                'color' => 'bg-violet-500',
            ],
        ];
    }

    private function kpisOperativos(?int $entidadId): array
    {
        $hrAbiertas = HojasRuta::whereNull('fecha_cierre')
            ->where('cancelada', false)
            ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId));
        $abiertas = (clone $hrAbiertas)->count();
        $vehiculosEnRuta = (clone $hrAbiertas)->whereNotNull('id_tractivo')->distinct('id_tractivo')->count('id_tractivo');
        $cartasMes = $this->cartasDelMes($entidadId);
        $cartas = (clone $cartasMes)->count();
        $toneladas = (clone $cartasMes)->sum('toneladas');

        return [
            [
                'label' => 'Hojas de ruta abiertas',
                'valor' => (string) $abiertas,
                'subtexto' => $entidadId ? 'Entidad activa' : 'Sin cerrar en el sistema',
                'icono' => 'pi pi-compass',
                'color' => 'bg-blue-500',
            ],
            [
                'label' => 'Vehículos en ruta',
                'valor' => (string) $vehiculosEnRuta,
                'subtexto' => 'Tractivos en hojas abiertas',
                'icono' => 'pi pi-truck',
                'color' => 'bg-emerald-500',
            ],
            [
                'label' => 'Toneladas del mes',
                'valor' => $toneladas > 0 ? number_format((float) $toneladas, 0, '.', '.').' t' : '—',
                'subtexto' => 'Ingresos de carga del período',
                'icono' => 'pi pi-weight',
                'color' => 'bg-amber-500',
            ],
            [
                'label' => 'Cartas del mes',
                'valor' => (string) $cartas,
                'subtexto' => 'Giros emitidos en el período',
                'icono' => 'pi pi-file',
                'color' => 'bg-violet-500',
            ],
        ];
    }

    private function kpisConfiguraciones(?int $entidadId): array
    {
        $usuariosQuery = User::query();
        $this->scopeEntidad($usuariosQuery, $entidadId);

        return [
            [
                'label' => 'Usuarios',
                'valor' => (string) $usuariosQuery->count(),
                'subtexto' => $entidadId ? 'En la entidad activa' : 'Registrados en el sistema',
                'icono' => 'pi pi-users',
                'color' => 'bg-blue-500',
            ],
            [
                'label' => 'Entidades',
                'valor' => $entidadId ? '1' : (string) Entidad::count(),
                'subtexto' => $entidadId ? 'Entidad activa' : 'Organizaciones configuradas',
                'icono' => 'pi pi-building',
                'color' => 'bg-indigo-500',
            ],
            [
                'label' => 'Vehículos',
                'valor' => (string) Tractivo::when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))->count(),
                'subtexto' => 'Registrados en sistema',
                'icono' => 'pi pi-truck',
                'color' => 'bg-emerald-500',
            ],
            [
                'label' => 'Sistema',
                'valor' => 'Activo',
                'subtexto' => 'Todo en orden',
                'icono' => 'pi pi-check-circle',
                'color' => 'bg-green-500',
            ],
        ];
    }

    private function kpisDefault(?int $entidadId): array
    {
        $vehiculosActivos = Tractivo::when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->where('estado', 'activo')->count();
        $vehiculosTotales = Tractivo::when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))->count();

        $bolsaQuery = Bolsa::query();
        $this->scopeEntidad($bolsaQuery, $entidadId);

        $ingresosMes = $this->ingresosDelMes($entidadId);
        $pendientes = SolicitudesServicio::whereIn('estado', ['pendiente', 'en_proceso'])
            ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->count();
        $porRecepcionar = CartaPorte::where('cancelada', false)
            ->where('estado', 'emitida')
            ->when($entidadId, fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->where('id_entidad', $entidadId)))
            ->count();

        return [
            [
                'label' => 'Vehículos activos',
                'valor' => $vehiculosTotales > 0 ? "{$vehiculosActivos}/{$vehiculosTotales}" : '—',
                'subtexto' => 'En operación actualmente',
                'icono' => 'pi pi-truck',
                'color' => 'bg-emerald-500',
            ],
            [
                'label' => 'Personal',
                'valor' => (string) $bolsaQuery->count(),
                'subtexto' => $entidadId ? 'Entidad activa' : 'Trabajadores registrados',
                'icono' => 'pi pi-users',
                'color' => 'bg-blue-500',
            ],
            [
                'label' => 'Ingresos del mes',
                'valor' => $ingresosMes > 0 ? $this->fmtMoneda($ingresosMes) : '—',
                'subtexto' => $entidadId ? 'Entidad activa' : 'Cartas de porte del período (MN)',
                'icono' => 'pi pi-dollar',
                'color' => 'bg-violet-500',
            ],
            [
                'label' => 'Solicitudes pendientes',
                'valor' => $pendientes > 0 ? (string) $pendientes : ($porRecepcionar > 0 ? (string) $porRecepcionar : '—'),
                'subtexto' => $pendientes > 0 ? 'Por atender' : 'CP por recepcionar',
                'icono' => 'pi pi-clipboard',
                'color' => 'bg-amber-500',
            ],
        ];
    }
}
