<?php

namespace App\Services;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\CartaPorte;
use App\Models\CombustibleDescarga;
use App\Models\HojasRuta;
use App\Models\SolicitudesServicio;
use App\Models\Entidad;
use App\Models\Tractivo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardOperativosService
{
    use EntidadScoping;

    public function datos(): array
    {
        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $fecha = Carbon::parse($fechaOperaciones);
        $inicioMes = $fecha->copy()->startOfMonth()->toDateString();
        $finMes = $fecha->copy()->endOfMonth()->toDateString();
        $inicioMesAnterior = $fecha->copy()->subMonth()->startOfMonth()->toDateString();
        $finMesAnterior = $fecha->copy()->subMonth()->endOfMonth()->toDateString();
        $idsEntidades = $this->entidadesPermitidas();
        $entidadId = (int) entidadActivaId();
        $entidad = $entidadId ? Entidad::find($entidadId) : null;
        $entidadNombre = $entidad?->nombre ?? '—';

        // ═══ KPIs ═══

        // Hojas de ruta del mes (no canceladas)
        $hrQuery = HojasRuta::query()
            ->where('cancelada', false)
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades));

        $hrDelMes = (clone $hrQuery)->count();
        $hrAbiertas = (clone $hrQuery)->whereNull('fecha_cierre')->count();
        $hrCerradas = (clone $hrQuery)->whereNotNull('fecha_cierre')->count();
        $hrCanceladas = HojasRuta::query()
            ->where('cancelada', true)
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
            ->count();

        // Cartas de porte del mes (scoping vía hoja de ruta)
        $cpQuery = CartaPorte::query()
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->where('cancelada', false)
            ->when(! empty($idsEntidades), fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->whereIn('id_entidad', $idsEntidades)));

        $cpDelMes = (clone $cpQuery)->count();
        $cpEmitidas = (clone $cpQuery)->where('estado', 'emitida')->count();
        $cpRecepcionadas = (clone $cpQuery)->where('estado', 'recepcionada')->count();
        $cpFacturadas = (clone $cpQuery)->where('estado', 'facturada')->count();

        // Solicitudes del mes (por fecha_solicitud)
        $solicitudesQuery = SolicitudesServicio::query()
            ->whereBetween('fecha_solicitud', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades));

        $solicitudesDelMes = (clone $solicitudesQuery)->count();
        $solicitudesPendientes = (clone $solicitudesQuery)->where('estado', 'pendiente')->count();
        $solicitudesEnProceso = (clone $solicitudesQuery)->where('estado', 'en_proceso')->count();
        $solicitudesEjecutadas = (clone $solicitudesQuery)->where('estado', 'ejecutada')->count();
        $solicitudesCanceladas = SolicitudesServicio::query()
            ->whereBetween('fecha_solicitud', [$inicioMes, $finMes])
            ->where('estado', 'cancelada')
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
            ->count();

        // Combustible descargado del mes
        $combustibleMes = CombustibleDescarga::query()
            ->whereBetween('fdescarga', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
            ->select(
                DB::raw('SUM(saldo_lts) as total_lts'),
                DB::raw('SUM(saldo_mon) as total_mon'),
                DB::raw('COUNT(*) as cantidad')
            )
            ->first();

        // Combustible descargado mes anterior (variación %)
        $combustibleMesAnterior = CombustibleDescarga::query()
            ->whereBetween('fdescarga', [$inicioMesAnterior, $finMesAnterior])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
            ->select(
                DB::raw('SUM(saldo_lts) as total_lts'),
                DB::raw('SUM(saldo_mon) as total_mon')
            )
            ->first();

        // Flota: tractivos por tipo de estado
        $flotaPorEstado = Tractivo::query()
            ->select('id_tipo_estado', DB::raw('COUNT(*) as total'))
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
            ->groupBy('id_tipo_estado')
            ->with('tipoEstado:id,nombre')
            ->get()
            ->map(fn ($row) => [
                'estado' => $row->tipoEstado?->nombre ?? 'Sin estado',
                'total' => (int) $row->total,
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        $flotaEnTaller = collect($flotaPorEstado)->firstWhere('estado', 'EN TALLER');
        $flotaActiva = collect($flotaPorEstado)->firstWhere('estado', 'ACTIVO');

        // ═══ SECCIÓN: HR por estado ═══
        $hrPorEstado = [
            ['estado' => 'abiertas', 'etiqueta' => 'Abiertas', 'total' => $hrAbiertas],
            ['estado' => 'cerradas', 'etiqueta' => 'Cerradas', 'total' => $hrCerradas],
            ['estado' => 'canceladas', 'etiqueta' => 'Canceladas', 'total' => $hrCanceladas],
        ];

        // ═══ SECCIÓN: CP por estado ═══
        $cpPorEstado = [
            ['estado' => 'emitida', 'etiqueta' => 'Emitidas', 'total' => $cpEmitidas],
            ['estado' => 'recepcionada', 'etiqueta' => 'Recepcionadas', 'total' => $cpRecepcionadas],
            ['estado' => 'facturada', 'etiqueta' => 'Facturadas', 'total' => $cpFacturadas],
        ];

        // ═══ SECCIÓN: Solicitudes por estado ═══
        $solicitudesPorEstado = [
            ['estado' => 'pendiente', 'etiqueta' => 'Pendientes', 'total' => $solicitudesPendientes],
            ['estado' => 'en_proceso', 'etiqueta' => 'En proceso', 'total' => $solicitudesEnProceso],
            ['estado' => 'ejecutada', 'etiqueta' => 'Ejecutadas', 'total' => $solicitudesEjecutadas],
            ['estado' => 'cancelada', 'etiqueta' => 'Canceladas', 'total' => $solicitudesCanceladas],
        ];

        // ═══ SECCIÓN: KM recorridos en HR del mes ═══
        $kmsHr = HojasRuta::query()
            ->where('cancelada', false)
            ->whereNotNull('fecha_cierre')
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
            ->sum('kms_totales');

        // ═══ SERIE: Actividad diaria del mes ═══
        $serie = $this->serieDiaria($inicioMes, $finMes, $idsEntidades);

        $totales = [
            'hr_del_mes' => $hrDelMes,
            'cp_del_mes' => $cpDelMes,
            'solicitudes_del_mes' => $solicitudesDelMes,
            'combustible_lts' => round((float) ($combustibleMes->total_lts ?? 0), 2),
            'combustible_mon' => round((float) ($combustibleMes->total_mon ?? 0), 2),
            'combustible_descargas' => (int) ($combustibleMes->cantidad ?? 0),
            'combustible_lts_anterior' => round((float) ($combustibleMesAnterior->total_lts ?? 0), 2),
            'kms_hr' => round((float) $kmsHr, 2),
            'flota' => collect($flotaPorEstado)->sum('total'),
            'flota_taller' => $flotaEnTaller['total'] ?? 0,
            'flota_activa' => $flotaActiva['total'] ?? 0,
        ];

        return [
            'fechaOperaciones' => $fechaOperaciones,
            'entidadNombre' => $entidadNombre,
            'entidad' => $entidad ? [
                'nombre' => $entidad->nombre,
                'abreviatura' => $entidad->abreviatura,
            ] : null,
            'totales' => $totales,
            'hrPorEstado' => $hrPorEstado,
            'cpPorEstado' => $cpPorEstado,
            'solicitudesPorEstado' => $solicitudesPorEstado,
            'flotaPorEstado' => $flotaPorEstado,
            'serie' => $serie,
        ];
    }

    /**
     * Serie diaria del mes: HR emitidas, CP emitidas y descargas de combustible.
     * Devuelve un array con un elemento por día activo del mes.
     */
    private function serieDiaria(string $inicioMes, string $finMes, array $idsEntidades): array
    {
        $hrPorDia = HojasRuta::query()
            ->select('fecha_emision', DB::raw('COUNT(*) as total'))
            ->where('cancelada', false)
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
            ->groupBy('fecha_emision')
            ->get()
            ->keyBy('fecha_emision');

        $cpPorDia = CartaPorte::query()
            ->select('fecha_emision', DB::raw('COUNT(*) as total'))
            ->where('cancelada', false)
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->whereIn('id_entidad', $idsEntidades)))
            ->groupBy('fecha_emision')
            ->get()
            ->keyBy('fecha_emision');

        $combPorDia = CombustibleDescarga::query()
            ->select('fdescarga', DB::raw('SUM(saldo_lts) as total_lts'))
            ->whereBetween('fdescarga', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
            ->groupBy('fdescarga')
            ->get()
            ->keyBy('fdescarga');

        $inicio = Carbon::parse($inicioMes);
        $fin = Carbon::parse($finMes);
        $serie = [];

        for ($d = $inicio->copy(); $d->lte($fin); $d->addDay()) {
            $clave = $d->toDateString();
            $serie[] = [
                'fecha' => $clave,
                'etiqueta' => $d->format('d/m'),
                'hr' => (int) ($hrPorDia[$clave]->total ?? 0),
                'cp' => (int) ($cpPorDia[$clave]->total ?? 0),
                'combustible_lts' => round((float) ($combPorDia[$clave]->total_lts ?? 0), 2),
            ];
        }

        return $serie;
    }
}