<?php

namespace App\Services;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\CartaPorte;
use App\Models\CombustibleDescarga;
use App\Models\HojasRuta;
use App\Models\SolicitudesServicio;
use App\Models\Entidad;
use App\Models\Tractivo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard del módulo OPERATIVOS.
 *
 * Combina tres bloques:
 *  - Documentos del día: cartas de porte y hojas de ruta emitidas /
 *    recepcionadas / cerradas en el día de trabajo.
 *  - Solicitudes: nuevas y cumplidas del día.
 *  - Tablero de flota: la misma pizarra de flota que usa la técnica.
 *
 * El "día de trabajo" y el mes se toman de `session('fecha_operaciones')`.
 * El filtrado por entidad usa el trait EntidadScoping (la matriz ve sus
 * filiales; una filial solo lo suyo).
 */
class DashboardOperativosService
{
    use EntidadScoping;

    public function __construct(
        protected DashboardTecnicoService $tecnico,
    ) {}

    public function datos(?Request $request = null): array
    {
        $request ??= request();

        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $fecha = Carbon::parse($fechaOperaciones);
        $dia = $fecha->toDateString();
        $inicioMes = $fecha->copy()->startOfMonth()->toDateString();
        $finMes = $fecha->copy()->endOfMonth()->toDateString();
        $inicioMesAnterior = $fecha->copy()->subMonth()->startOfMonth()->toDateString();
        $finMesAnterior = $fecha->copy()->subMonth()->endOfMonth()->toDateString();
        $idsEntidades = $this->entidadesPermitidas();
        $entidadId = (int) entidadActivaId();
        $entidad = $entidadId ? Entidad::find($entidadId) : null;
        $entidadNombre = $entidad?->nombre ?? '—';

        // ═══ KPIs del mes ═══

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

        $cpQuery = CartaPorte::query()
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->where('cancelada', false)
            ->when(! empty($idsEntidades), fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->whereIn('id_entidad', $idsEntidades)));

        $cpDelMes = (clone $cpQuery)->count();
        $cpEmitidas = (clone $cpQuery)->where('estado', 'emitida')->count();
        $cpRecepcionadas = (clone $cpQuery)->where('estado', 'recepcionada')->count();
        $cpFacturadas = (clone $cpQuery)->where('estado', 'facturada')->count();

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

        $combustibleMes = CombustibleDescarga::query()
            ->whereBetween('fdescarga', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
            ->select(
                DB::raw('SUM(saldo_lts) as total_lts'),
                DB::raw('SUM(saldo_mon) as total_mon'),
                DB::raw('COUNT(*) as cantidad')
            )
            ->first();

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

        $hrPorEstado = [
            ['estado' => 'abiertas', 'etiqueta' => 'Abiertas', 'total' => $hrAbiertas],
            ['estado' => 'cerradas', 'etiqueta' => 'Cerradas', 'total' => $hrCerradas],
            ['estado' => 'canceladas', 'etiqueta' => 'Canceladas', 'total' => $hrCanceladas],
        ];

        $cpPorEstado = [
            ['estado' => 'emitida', 'etiqueta' => 'Emitidas', 'total' => $cpEmitidas],
            ['estado' => 'recepcionada', 'etiqueta' => 'Recepcionadas', 'total' => $cpRecepcionadas],
            ['estado' => 'facturada', 'etiqueta' => 'Facturadas', 'total' => $cpFacturadas],
        ];

        $solicitudesPorEstado = [
            ['estado' => 'pendiente', 'etiqueta' => 'Pendientes', 'total' => $solicitudesPendientes],
            ['estado' => 'en_proceso', 'etiqueta' => 'En proceso', 'total' => $solicitudesEnProceso],
            ['estado' => 'ejecutada', 'etiqueta' => 'Ejecutadas', 'total' => $solicitudesEjecutadas],
            ['estado' => 'cancelada', 'etiqueta' => 'Canceladas', 'total' => $solicitudesCanceladas],
        ];

        $kmsHr = HojasRuta::query()
            ->where('cancelada', false)
            ->whereNotNull('fecha_cierre')
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
            ->sum('kms_totales');

        $serie = $this->serieDiaria($inicioMes, $finMes, $idsEntidades);

        // ═══ Documentos del día ═══
        $documentosDia = [
            'dia' => $dia,
            'cpEmitidasPorCliente' => $this->cpEmitidasDiaPorCliente($dia, $idsEntidades),
            'cpRecepcionadasPorChofer' => $this->cpRecepcionadasDiaPorChofer($dia, $idsEntidades),
            'hrEmitidasPorChofer' => $this->hrDiaPorChofer($dia, 'emision', $idsEntidades),
            'hrCerradasPorChofer' => $this->hrDiaPorChofer($dia, 'cierre', $idsEntidades),
        ];

        // ═══ Solicitudes del día ═══
        $solicitudesDia = [
            'dia' => $dia,
            'nuevas' => SolicitudesServicio::query()
                ->where('fecha_solicitud', $dia)
                ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
                ->count(),
            'cumplidas' => SolicitudesServicio::query()
                ->where('estado', 'ejecutada')
                ->where('fecha_ejecutada', $dia)
                ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
                ->count(),
            'cumplidasPorCliente' => $this->solicitudesCumplidasDiaPorCliente($dia, $idsEntidades),
        ];

        // ═══ Tablero de Flota (misma pizarra que la técnica) ═══
        $pizarra = $this->tecnico->paraPizarraOperativa($request);
        $columnas = $pizarra['columnas'] ?? [];
        $flotaPorTipo = $this->flotaPorTipo($columnas);

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
            'cp_emitidas_dia' => collect($documentosDia['cpEmitidasPorCliente'])->sum('cantidad'),
            'hr_emitidas_dia' => collect($documentosDia['hrEmitidasPorChofer'])->sum('cantidad'),
            'solicitudes_dia' => $solicitudesDia['nuevas'],
            'solicitudes_cumplidas_dia' => $solicitudesDia['cumplidas'],
        ];

        return [
            'fechaOperaciones' => $fechaOperaciones,
            'diaOperaciones' => $dia,
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
            'documentosDia' => $documentosDia,
            'solicitudesDia' => $solicitudesDia,
            'flotaPorTipo' => $flotaPorTipo,
        ];
    }

    /**
     * Cartas de porte emitidas en el día, agrupadas por cliente.
     */
    private function cpEmitidasDiaPorCliente(string $dia, array $idsEntidades): array
    {
        return DB::table('cartas_porte as cp')
            ->join('solicitudes_servicio as s', 's.id', '=', 'cp.id_solicitud')
            ->join('clientes as c', 'c.id', '=', 's.id_cliente')
            ->whereNull('cp.deleted_at')
            ->where('cp.fecha_emision', $dia)
            ->where('cp.cancelada', false)
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('cp.id_hoja_ruta', function ($sub) use ($idsEntidades) {
                $sub->select('id')->from('hojas_ruta')->whereIn('id_entidad', $idsEntidades);
            }))
            ->groupBy('c.id', 'c.nombre')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->selectRaw('c.nombre as etiqueta, COUNT(*) as cantidad')
            ->get()
            ->map(fn ($row) => ['cliente' => $row->etiqueta, 'cantidad' => (int) $row->cantidad])
            ->all();
    }

    /**
     * Cartas de porte recepcionadas en el día, agrupadas por chofer.
     */
    private function cpRecepcionadasDiaPorChofer(string $dia, array $idsEntidades): array
    {
        return DB::table('cartas_porte as cp')
            ->leftJoin('bolsa as b', 'b.id', '=', 'cp.id_chofer')
            ->whereNull('cp.deleted_at')
            ->where('cp.fecha_recepcion', $dia)
            ->where('cp.estado', 'recepcionada')
            ->where('cp.cancelada', false)
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('cp.id_hoja_ruta', function ($sub) use ($idsEntidades) {
                $sub->select('id')->from('hojas_ruta')->whereIn('id_entidad', $idsEntidades);
            }))
            ->groupBy('cp.id_chofer', 'b.nombre', 'b.apellidos')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->selectRaw("COALESCE(NULLIF(TRIM(CONCAT(COALESCE(b.nombre,''),' ',COALESCE(b.apellidos,''))), ''), 'Sin chofer') as etiqueta, COUNT(*) as cantidad")
            ->get()
            ->map(fn ($row) => ['chofer' => $row->etiqueta, 'cantidad' => (int) $row->cantidad])
            ->all();
    }

    /**
     * Hojas de ruta emitidas o cerradas en el día, agrupadas por chofer.
     *
     * @param  string  $tipo  'emision' usa fecha_emision; 'cierre' usa fecha_cierre.
     */
    private function hrDiaPorChofer(string $dia, string $tipo, array $idsEntidades): array
    {
        $columna = $tipo === 'cierre' ? 'h.fecha_cierre' : 'h.fecha_emision';

        return DB::table('hojas_ruta as h')
            ->leftJoin('bolsa as b', 'b.id', '=', 'h.id_chofer')
            ->whereNull('h.deleted_at')
            ->where($columna, $dia)
            ->where('h.cancelada', false)
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('h.id_entidad', $idsEntidades))
            ->groupBy('h.id_chofer', 'b.nombre', 'b.apellidos')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->selectRaw("COALESCE(NULLIF(TRIM(CONCAT(COALESCE(b.nombre,''),' ',COALESCE(b.apellidos,''))), ''), 'Sin chofer') as etiqueta, COUNT(*) as cantidad")
            ->get()
            ->map(fn ($row) => ['chofer' => $row->etiqueta, 'cantidad' => (int) $row->cantidad])
            ->all();
    }

    /**
     * Solicitudes cumplidas en el día (estado ejecutada), agrupadas por cliente.
     */
    private function solicitudesCumplidasDiaPorCliente(string $dia, array $idsEntidades): array
    {
        return DB::table('solicitudes_servicio as s')
            ->join('clientes as c', 'c.id', '=', 's.id_cliente')
            ->where('s.estado', 'ejecutada')
            ->where('s.fecha_ejecutada', $dia)
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('s.id_entidad', $idsEntidades))
            ->groupBy('c.id', 'c.nombre')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->selectRaw('c.nombre as etiqueta, COUNT(*) as cantidad')
            ->get()
            ->map(fn ($row) => ['cliente' => $row->etiqueta, 'cantidad' => (int) $row->cantidad])
            ->all();
    }

    /**
     * Agrupa los vehículos de las columnas de la pizarra por tipo de equipo,
     * con el mismo formato que consume la sección "Tablero de Flota" de
     * PizarraOperativa.vue.
     */
    private function flotaPorTipo(array $columnas): array
    {
        $todos = collect($columnas)
            ->flatMap(fn ($columna) => $columna['vehiculos'] ?? [])
            ->filter(fn ($v) => empty($v['baja']));

        $grupos = [];
        foreach ($todos as $v) {
            $nombre = $v['tipoVehiculo'] ?? ($v['tipo'] ?? 'Sin tipo');
            if (! isset($grupos[$nombre])) {
                $grupos[$nombre] = [
                    'nombre' => $nombre,
                    'esArrastre' => (bool) ($v['esArrastre'] ?? false),
                    'activos' => [],
                    'taller' => [],
                ];
            }
            if (! empty($v['enTaller'])) {
                $grupos[$nombre]['taller'][] = $v;
            } else {
                $grupos[$nombre]['activos'][] = $v;
            }
        }

        $grupos = array_values($grupos);
        usort($grupos, fn ($a, $b) => (count($b['activos']) + count($b['taller'])) <=> (count($a['activos']) + count($a['taller'])));

        return array_map(fn ($g) => array_merge($g, [
            'activosCount' => count($g['activos']),
            'tallerCount' => count($g['taller']),
            'total' => count($g['activos']) + count($g['taller']),
        ]), $grupos);
    }

    /**
     * Serie diaria del mes: HR emitidas, CP emitidas y descargas de combustible.
     * Devuelve un array con un elemento por día del mes.
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
