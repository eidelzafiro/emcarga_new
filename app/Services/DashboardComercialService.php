<?php

namespace App\Services;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Aforo;
use App\Models\CartaPorte;
use App\Models\Entidad;
use App\Models\Factura;
use App\Models\HojasRuta;
use App\Models\SolicitudesServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard del módulo COMERCIAL.
 *
 * Estructura por secciones intercambiables (pestañas en el frontend):
 *  1. Documentos del mes  — cartas de porte (emitidas/recepcionadas/aforadas/
 *     facturadas) por cliente y hojas de ruta (emitidas/cerradas) por chofer.
 *  2. Solicitudes del mes — por cliente y estado.
 *  3. Facturación         — idéntica a la del dashboard de Contabilidad.
 *  4. Tráfico             — toneladas, kms, tráfico e ingresos por fecha de parte.
 *  5. Tablero de Flota    — idéntico al de la técnica (tarjetas por tipo de equipo).
 *
 * Todo se acota al MES de `session('fecha_operaciones')` y a las entidades
 * permitidas por el trait EntidadScoping (la matriz ve sus filiales; una
 * filial solo lo suyo).
 */
class DashboardComercialService
{
    use EntidadScoping;

    public function datos(): array
    {
        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $fecha = Carbon::parse($fechaOperaciones);
        $inicioMes = $fecha->copy()->startOfMonth()->toDateString();
        $finMes = $fecha->copy()->endOfMonth()->toDateString();
        $idsEntidades = $this->entidadesPermitidas();
        $entidadId = (int) entidadActivaId();
        $entidad = $entidadId ? Entidad::find($entidadId) : null;
        $entidadNombre = $entidad?->nombre ?? '—';

        // ═══════════════════════════════════════════════════════════
        // SECCIÓN 1 — DOCUMENTOS DEL MES
        // ═══════════════════════════════════════════════════════════

        $cps = CartaPorte::query()
            ->where('cancelada', false)
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->where(function ($w) use ($idsEntidades) {
                $this->whereCartaEnEntidades($w, $idsEntidades);
            }))
            ->with([
                'solicitud:id,id_cliente',
                'solicitud.cliente:id,nombre',
                'aforos:id,id_carta_porte,id_factura',
            ])
            ->get(['id', 'estado', 'id_solicitud']);

        $cpEmitidas = $cps->where('estado', 'emitida')->count();
        $cpRecepcionadas = $cps->where('estado', 'recepcionada')->count();
        $cpAforadas = $cps->filter(fn ($cp) => $cp->aforos->isNotEmpty())->count();
        $cpFacturadas = $cps->filter(fn ($cp) => $cp->aforos->contains(fn ($a) => ! empty($a->id_factura)))->count();

        $documentosPorCliente = $cps
            ->groupBy(fn ($cp) => $cp->solicitud?->cliente?->nombre ?? 'Sin cliente')
            ->map(fn ($grupo, $cliente) => [
                'cliente' => $cliente,
                'emitidas' => $grupo->where('estado', 'emitida')->count(),
                'recepcionadas' => $grupo->where('estado', 'recepcionada')->count(),
                'aforadas' => $grupo->filter(fn ($cp) => $cp->aforos->isNotEmpty())->count(),
                'facturadas' => $grupo->filter(fn ($cp) => $cp->aforos->contains(fn ($a) => ! empty($a->id_factura)))->count(),
                'total' => $grupo->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        // Hojas de ruta del mes agrupadas por chofer.
        $hrs = HojasRuta::query()
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
            ->with('chofer:id,nombre,apellidos')
            ->get(['id', 'fecha_cierre', 'id_chofer', 'cancelada']);

        $hrEmitidas = $hrs->where('cancelada', false)->count();
        $hrCerradas = $hrs->whereNotNull('fecha_cierre')->count();

        $hrPorChofer = $hrs
            ->groupBy(fn ($hr) => trim(($hr->chofer?->nombre ?? '').' '.($hr->chofer?->apellidos ?? '')) ?: 'Sin chofer')
            ->map(fn ($grupo, $chofer) => [
                'chofer' => $chofer,
                'emitidas' => $grupo->where('cancelada', false)->count(),
                'cerradas' => $grupo->whereNotNull('fecha_cierre')->count(),
                'total' => $grupo->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        // ═══════════════════════════════════════════════════════════
        // SECCIÓN 2 — SOLICITUDES DEL MES
        // ═══════════════════════════════════════════════════════════

        $solicitudes = SolicitudesServicio::query()
            ->whereBetween('fecha_solicitud', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
            ->with('cliente:id,nombre')
            ->get(['id', 'id_cliente', 'estado']);

        $solicitudesPorCliente = $solicitudes
            ->groupBy(fn ($s) => $s->cliente?->nombre ?? 'Sin cliente')
            ->map(fn ($grupo, $cliente) => [
                'cliente' => $cliente,
                'pendientes' => $grupo->where('estado', 'pendiente')->count(),
                'en_proceso' => $grupo->where('estado', 'en_proceso')->count(),
                'ejecutadas' => $grupo->where('estado', 'ejecutada')->count(),
                'total' => $grupo->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        $solicitudesTotal = $solicitudes->count();

        // ═══════════════════════════════════════════════════════════
        // SECCIÓN 3 — FACTURACIÓN (idéntica a Contabilidad)
        // ═══════════════════════════════════════════════════════════

        $ingresosPorConcepto = Factura::query()
            ->select(
                'id_tipo_ingreso',
                DB::raw('SUM(ingreso_mt) as total_mt'),
                DB::raw('COUNT(*) as cantidad')
            )
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->where('cancelada', false)
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
            ->groupBy('id_tipo_ingreso')
            ->with('tipoIngreso:id,nombre')
            ->get()
            ->map(fn ($row) => [
                'concepto' => $row->tipoIngreso?->nombre ?? 'FLETE TRANSPORTACION',
                'total_mt' => round((float) $row->total_mt, 2),
                'cantidad' => (int) $row->cantidad,
            ])
            ->all();

        $facturacionPorCliente = Factura::query()
            ->select(
                'id_cliente',
                DB::raw('SUM(ingreso_mt) as total_mt'),
                DB::raw('COUNT(*) as cantidad_facturas')
            )
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->where('cancelada', false)
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
            ->groupBy('id_cliente')
            ->with('cliente:id,nombre')
            ->orderByDesc('total_mt')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'cliente' => $row->cliente?->nombre ?? 'Sin cliente',
                'total_mt' => round((float) $row->total_mt, 2),
                'cantidad_facturas' => (int) $row->cantidad_facturas,
            ])
            ->all();

        // Alias histórico que el test del dashboard espera.
        $facturacionPorConcepto = $ingresosPorConcepto;

        // ═══════════════════════════════════════════════════════════
        // SECCIÓN 4 — TRÁFICO (por fecha de parte)
        // ═══════════════════════════════════════════════════════════

        $traficoPorFecha = Aforo::query()
            ->select(
                'fecha_parte',
                DB::raw('SUM(tn_real_total) as toneladas'),
                DB::raw('SUM(km_carga_total) as km_carga'),
                DB::raw('SUM(km_vacio_total) as km_vacio'),
                DB::raw('SUM(traf_pos_total) as traf_pos'),
                DB::raw('SUM(traf_real_total) as traf_real'),
                DB::raw('SUM(ingreso_mt) as ingreso'),
                DB::raw('COUNT(*) as aforos')
            )
            ->whereBetween('fecha_parte', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereHas('cartaPorte', fn ($c) => $this->whereCartaEnEntidades($c, $idsEntidades)))
            ->groupBy('fecha_parte')
            ->orderBy('fecha_parte')
            ->get()
            ->map(fn ($row) => [
                'fecha' => $row->fecha_parte,
                'etiqueta' => $row->fecha_parte ? Carbon::parse($row->fecha_parte)->format('d/m') : '—',
                'toneladas' => round((float) $row->toneladas, 2),
                'km_carga' => round((float) $row->km_carga, 2),
                'km_vacio' => round((float) $row->km_vacio, 2),
                'traf_pos' => round((float) $row->traf_pos, 2),
                'traf_real' => round((float) $row->traf_real, 2),
                'ingreso' => round((float) $row->ingreso, 2),
                'aforos' => (int) $row->aforos,
            ])
            ->all();

        $traficoTotal = [
            'toneladas' => round(collect($traficoPorFecha)->sum('toneladas'), 2),
            'km_carga' => round(collect($traficoPorFecha)->sum('km_carga'), 2),
            'km_vacio' => round(collect($traficoPorFecha)->sum('km_vacio'), 2),
            'traf_pos' => round(collect($traficoPorFecha)->sum('traf_pos'), 2),
            'traf_real' => round(collect($traficoPorFecha)->sum('traf_real'), 2),
            'ingreso' => round(collect($traficoPorFecha)->sum('ingreso'), 2),
            'aforos' => (int) collect($traficoPorFecha)->sum('aforos'),
        ];

        // ═══════════════════════════════════════════════════════════
        // SECCIÓN 5 — TABLERO DE FLOTA (idéntico a la técnica)
        // ═══════════════════════════════════════════════════════════

        $flotaPorTipo = $this->flotaPorTipo();

        // ═══════════════════════════════════════════════════════════
        // SERIE (ingresos aforados por día) y TOTALES
        // ═══════════════════════════════════════════════════════════

        $serie = $this->serieIngresosDiarios($inicioMes, $finMes, $idsEntidades);

        $ingresosFacturados = collect($ingresosPorConcepto)->sum('total_mt');
        $totalFacturas = collect($facturacionPorCliente)->sum('cantidad_facturas');

        $totales = [
            // Documentos
            'cp_emitidas' => $cpEmitidas,
            'cp_recepcionadas' => $cpRecepcionadas,
            'cp_aforadas' => $cpAforadas,
            'cp_facturadas' => $cpFacturadas,
            'cp_del_mes' => $cps->count(),
            'hr_emitidas' => $hrEmitidas,
            'hr_cerradas' => $hrCerradas,
            'solicitudes_total' => $solicitudesTotal,
            // Facturación (paridad Contabilidad)
            'ingresos_mt' => $ingresosFacturados,
            'total_facturas' => $totalFacturas,
            // Tráfico
            'toneladas' => $traficoTotal['toneladas'],
            'km_carga' => $traficoTotal['km_carga'],
            'km_vacio' => $traficoTotal['km_vacio'],
            'traf_pos' => $traficoTotal['traf_pos'],
            'traf_real' => $traficoTotal['traf_real'],
            'ingresos_trafico' => $traficoTotal['ingreso'],
            // Compatibilidad con claves previas
            'facturas_del_mes' => $totalFacturas,
            'ingresos_facturados' => $ingresosFacturados,
            'aforos_del_mes' => $traficoTotal['aforos'],
            'ingresos_aforados' => $traficoTotal['ingreso'],
            'clientes_activos' => collect($documentosPorCliente)->count(),
        ];

        return [
            'fechaOperaciones' => $fechaOperaciones,
            'entidadNombre' => $entidadNombre,
            'entidad' => $entidad ? [
                'nombre' => $entidad->nombre,
                'abreviatura' => $entidad->abreviatura,
            ] : null,
            'totales' => $totales,
            // Sección 1
            'documentosPorCliente' => $documentosPorCliente,
            'hrPorChofer' => $hrPorChofer,
            // Sección 2
            'solicitudesPorCliente' => $solicitudesPorCliente,
            // Sección 3
            'ingresosPorConcepto' => $ingresosPorConcepto,
            'facturacionPorConcepto' => $facturacionPorConcepto,
            'facturacionPorCliente' => $facturacionPorCliente,
            // Sección 4
            'traficoPorFecha' => $traficoPorFecha,
            'traficoTotal' => $traficoTotal,
            // Sección 5
            'flotaPorTipo' => $flotaPorTipo,
            // Serie
            'serie' => $serie,
        ];
    }

    /**
     * Tablero de flota con la MISMA forma que el computed `flotaPorTipo` de
     * `Tecnico/PizarraOperativa.vue`: agrupa los vehículos (sin baja) por tipo
     * de equipo y separa activos de los que están en taller.
     */
    private function flotaPorTipo(): array
    {
        $pizarra = app(DashboardTecnicoService::class)->paraPizarraOperativa(request());
        $columnas = $pizarra['columnas'] ?? [];
        $vehiculos = collect($columnas['todos']['vehiculos'] ?? [])->where('baja', false);

        return $vehiculos
            ->groupBy(fn ($v) => $v['tipoVehiculo'] ?? 'Sin tipo')
            ->map(function ($grupo, $nombre) {
                $activos = $grupo->where('enTaller', false)->values()->all();
                $taller = $grupo->where('enTaller', true)->values()->all();

                return [
                    'nombre' => $nombre,
                    'esArrastre' => (bool) ($grupo->first()['esArrastre'] ?? false),
                    'activos' => $activos,
                    'taller' => $taller,
                    'activosCount' => count($activos),
                    'tallerCount' => count($taller),
                    'total' => count($activos) + count($taller),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    private function whereCartaEnEntidades($query, array $ids): void
    {
        $query->where(function ($w) use ($ids) {
            $w->whereHas('hojaRuta', fn ($h) => $h->whereIn('id_entidad', $ids))
                ->orWhereHas('hojaRuta.tractivo', fn ($t) => $t->whereIn('id_entidad', $ids))
                ->orWhereHas('solicitud', fn ($s) => $s->whereIn('id_entidad', $ids));
        });
    }

    private function serieIngresosDiarios(string $inicioMes, string $finMes, array $idsEntidades): array
    {
        $porDia = Aforo::query()
            ->select('fecha_parte', DB::raw('SUM(ingreso_mt) as total_mt'), DB::raw('COUNT(*) as cantidad'))
            ->whereBetween('fecha_parte', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereHas('cartaPorte', fn ($c) => $this->whereCartaEnEntidades($c, $idsEntidades)))
            ->groupBy('fecha_parte')
            ->get()
            ->keyBy('fecha_parte');

        $inicio = Carbon::parse($inicioMes);
        $fin = Carbon::parse($finMes);
        $serie = [];

        for ($d = $inicio->copy(); $d->lte($fin); $d->addDay()) {
            $clave = $d->toDateString();
            $serie[] = [
                'fecha' => $clave,
                'etiqueta' => $d->format('d/m'),
                'ingreso_mt' => round((float) ($porDia[$clave]->total_mt ?? 0), 2),
                'aforos' => (int) ($porDia[$clave]->cantidad ?? 0),
            ];
        }

        return $serie;
    }
}
