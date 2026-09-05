<?php

namespace App\Services;

use App\Models\Arrastre;
use App\Models\Bateria;
use App\Models\BateriasMovimiento;
use App\Models\Caja;
use App\Models\Diferenciale;
use App\Models\Entidad;
use App\Models\Motore;
use App\Models\Neumatico;
use App\Models\NeumaticosMovimiento;
use App\Models\OrdenesTaller;
use App\Models\Taller;
use App\Models\TiposMantenimiento;
use App\Models\Aforo;
use App\Models\Bolsa;
use App\Models\CartaPorte;
use App\Models\Cliente;
use App\Models\HojasRuta;
use App\Models\Lugare;
use App\Models\Tractivo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Construye los datos de los tres dashboards del módulo técnico.
 *
 * Filtrado común (principio de fecha de operaciones + entidad activa):
 *  - Mes: request `mes` (YYYY-MM) > sesión `fecha_operaciones` > mes actual.
 *  - Entidad: sesión `entidad_activa_id` + sus subordinadas (Entidad::idsPermitidos).
 *
 * Los indicadores transaccionales (OT, montajes, movimientos) se acotan al
 * mes; la composición de flota (marca/modelo/tipo) es un retrato de la
 * entidad (no varía por mes).
 */
class DashboardTecnicoService
{
    private const ESTADO_ACTIVO = 14;
    private const ESTADO_EN_TALLER = 26;
    private const ESTADO_PARALIZADO = 25;
    private const ESTADO_BAJA = 27;

    /**
     * Resuelve mes de operaciones, ventana y entidades permitidas.
     */
    private function contexto(Request $request): array
    {
        $fecha = $request->input('mes')
            ? Carbon::createFromFormat('Y-m', $request->input('mes'))->startOfMonth()
            : (session('fecha_operaciones') ? Carbon::parse(session('fecha_operaciones')) : now());

        $entidadId = (int) entidadActivaId();
        $ids = $entidadId ? Entidad::idsPermitidos($entidadId) : [];
        $sinEntidad = empty($ids);

        return [
            'fecha' => $fecha,
            'inicio' => $fecha->copy()->startOfMonth()->toDateString(),
            'fin' => $fecha->copy()->endOfMonth()->toDateString(),
            'ids' => $ids,
            'sinEntidad' => $sinEntidad,
            'mesLabel' => ucfirst($fecha->translatedFormat('F Y')),
            'entidadNombre' => $entidadId ? (Entidad::find($entidadId)?->nombre ?? '—') : '—',
        ];
    }

    /**
     * Colección de vehículos (tractivos + arrastres) de la entidad con su
     * tipo de vehículo (y su marca/modelo/tipo de equipo resueltos).
     */
    private function vehiculosEntidad(array $ids): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        $entidades = Entidad::whereIn('id', $ids)->pluck('nombre', 'id');

        $tractivos = Tractivo::whereIn('id_entidad', $ids)
            ->with('tipoVehiculo.tipoEquipo', 'tipoVehiculo.marca', 'tipoVehiculo.modelo')
            ->get();

        $arrastres = Arrastre::whereIn('id_entidad', $ids)
            ->with('tipoVehiculo.tipoEquipo', 'tipoVehiculo.marca', 'tipoVehiculo.modelo')
            ->get();

        return $tractivos->map(fn ($v) => $this->mapaVehiculo($v, 'Tractivo', $entidades))
            ->concat($arrastres->map(fn ($v) => $this->mapaVehiculo($v, 'Arrastre', $entidades)));
    }

    private function mapaVehiculo($v, string $clase, Collection $entidades): array
    {
        $tv = $v->tipoVehiculo;
        $marca = optional($tv?->marca)->nombre;
        $modelo = optional($tv?->modelo)->nombre;

        return [
            'id' => $v->id,
            'clase' => $clase,
            'codigo' => $v->codigo,
            'placa' => $v->placa ?? '—',
            'tipo' => optional($tv?->tipoEquipo)->nombre ?? 'Sin tipo',
            'marca' => $marca ?? 'Sin marca',
            'modelo' => $modelo ?? 'Sin modelo',
            'marcaModelo' => trim(($marca ?: 'Sin marca').' '.($modelo ?: '')),
            'entidad' => $entidades[$v->id_entidad] ?? '—',
            'estado' => $v->id_tipo_estado,
            'enTaller' => in_array((int) $v->id_tipo_estado, [self::ESTADO_EN_TALLER, self::ESTADO_PARALIZADO], true),
            'baja' => ! empty($v->fecha_baja),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // PROPUESTA 1 — CONTROL DE FLOTA TÉCNICA
    // ──────────────────────────────────────────────────────────────

    public function paraFlota(Request $request): array
    {
        $c = $this->contexto($request);
        $vehiculos = $this->vehiculosEntidad($c['ids']);

        // Conteos coherentes con el desglose de composición (baja / taller /
        // activos mutuamente excluyentes). Un vehículo con fecha_baja se
        // cuenta como baja, no como activo ni en taller.
        $tractivosActivos = $vehiculos->where('clase', 'Tractivo')->where('baja', false)->where('enTaller', false)->count();
        $arrastresActivos = $vehiculos->where('clase', 'Arrastre')->where('baja', false)->where('enTaller', false)->count();
        $enTaller = $vehiculos->where('enTaller', true)->where('baja', false)->count();
        $baja = $vehiculos->where('baja', true)->count();

        $otsAbiertasMes = OrdenesTaller::whereIn('id_entidad', $c['ids'])
            ->whereBetween('fecha_ingreso', [$c['inicio'], $c['fin']])
            ->where('estado', 'abierta')->count();

        $neuMontados = NeumaticosMovimiento::whereIn('id_entidad', $c['ids'])
            ->whereNull('fecha_retiro')->count();
        $batRotacion = BateriasMovimiento::whereIn('id_entidad', $c['ids'])
            ->whereNull('fecha_retiro')->count();

        $kpis = [
            ['label' => 'Tractivos activos', 'valor' => $tractivosActivos, 'icono' => 'pi pi-truck', 'color' => 'bg-blue-500', 'subtexto' => "Entidad: {$c['entidadNombre']}"],
            ['label' => 'Arrastres activos', 'valor' => $arrastresActivos, 'icono' => 'pi pi-box', 'color' => 'bg-emerald-500', 'subtexto' => 'Sin taller y sin baja'],
            ['label' => 'Vehículos en taller', 'valor' => $enTaller, 'icono' => 'pi pi-wrench', 'color' => 'bg-amber-500', 'subtexto' => 'Estado taller/paralizado'],
            ['label' => 'Vehículos de baja', 'valor' => $baja, 'icono' => 'pi pi-ban', 'color' => 'bg-red-500', 'subtexto' => 'Con fecha de baja'],
            ['label' => 'OT abiertas del mes', 'valor' => $otsAbiertasMes, 'icono' => 'pi pi-file-edit', 'color' => 'bg-violet-500', 'subtexto' => $c['mesLabel']],
            ['label' => 'Neumáticos montados', 'valor' => $neuMontados, 'icono' => 'pi pi-circle-fill', 'color' => 'bg-fuchsia-500', 'subtexto' => 'En circulación'],
            ['label' => 'Baterías en rotación', 'valor' => $batRotacion, 'icono' => 'pi pi-bolt', 'color' => 'bg-orange-500', 'subtexto' => 'Instaladas'],
        ];

        $porTipo = $this->agrupar($vehiculos, 'tipo');
        $porMarca = $this->agrupar($vehiculos, 'marca', 8);
        $porModelo = $this->agrupar($vehiculos, 'modelo', 8);

        $otMes = $this->otPorEstadoMes($c);
        $serieOt = $this->serieOtDiaria($c);

        return [
            'mesLabel' => $c['mesLabel'],
            'entidadNombre' => $c['entidadNombre'],
            'sinEntidad' => $c['sinEntidad'],
            'kpis' => $kpis,
            'composicion' => [
                'porTipo' => $porTipo,
                'porMarca' => $porMarca,
                'porModelo' => $porModelo,
            ],
            'otResumen' => $otMes,
            'serieOt' => $serieOt,
            'totalVehiculos' => $vehiculos->count(),
        ];
    }

    /**
     * Drill-down: lista de vehículos filtrada por dimensión (tipo/marca/modelo)
     * y valor (nombre). Usado por el clic en las gráficas de composición.
     */
    public function detalleFlota(Request $request): array
    {
        $c = $this->contexto($request);
        $dimension = $request->input('dimension', 'tipo');
        $valor = $request->input('valor', '');

        $vehiculos = $this->vehiculosEntidad($c['ids']);

        if ($dimension !== 'clase') {
            $vehiculos = $vehiculos->where($dimension, $valor);
        }

        return [
            'dimension' => $dimension,
            'valor' => $valor,
            'vehiculos' => $vehiculos->values()->take(200)->all(),
            'total' => $vehiculos->count(),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // PROPUESTA 2 — TALLER EN VIVO
    // ──────────────────────────────────────────────────────────────

    public function paraTaller(Request $request): array
    {
        $c = $this->contexto($request);
        $vehiculos = $this->vehiculosEntidad($c['ids']);

        $enTaller = $vehiculos->where('enTaller', true)->values();
        $hoy = now();

        $vehiculosTaller = $enTaller->map(function ($v) use ($c, $hoy) {
            $ot = OrdenesTaller::whereIn('id_entidad', $c['ids'])
                ->where('id_tractivo', $v['id'])
                ->where('estado', 'abierta')
                ->whereNull('cancelada')
                ->orderByDesc('fecha_ingreso')
                ->first();

            $dias = $ot && $ot->fecha_ingreso
                ? $ot->fecha_ingreso->diffInDays($hoy)
                : null;

            return [
                'codigo' => $v['codigo'],
                'placa' => $v['placa'],
                'clase' => $v['clase'],
                'ot' => $ot ? $ot->numero : '—',
                'motivo' => optional($ot?->motivoEntrada)->nombre ?? '—',
                'dias' => $dias,
                'estado' => (int) $v['estado'] === self::ESTADO_PARALIZADO ? 'Paralizado' : 'En taller',
            ];
        })->sortByDesc('dias')->values()->all();

        $otMes = $this->otPorEstadoMes($c);
        $horasMes = OrdenesTaller::whereIn('id_entidad', $c['ids'])
            ->whereBetween('fecha_ingreso', [$c['inicio'], $c['fin']])
            ->get()
            ->sum(fn ($ot) => $ot->calcularTiempoTotal());

        $vencidas = OrdenesTaller::whereIn('id_entidad', $c['ids'])
            ->where('estado', 'abierta')
            ->whereNotNull('fecha_salida_estimada')
            ->where('fecha_salida_estimada', '<', $hoy->toDateString())
            ->count();

        $porTipoMtto = OrdenesTaller::whereIn('id_entidad', $c['ids'])
            ->whereBetween('fecha_ingreso', [$c['inicio'], $c['fin']])
            ->with('tipoMantenimiento')
            ->get()
            ->groupBy(fn ($ot) => optional($ot->tipoMantenimiento)->nombre ?? 'Sin tipo')
            ->map(fn ($g) => $g->count())
            ->sortDesc()
            ->map(fn ($v, $k) => ['etiqueta' => $k, 'valor' => $v])
            ->values()
            ->all();

        $porTaller = OrdenesTaller::whereIn('id_entidad', $c['ids'])
            ->whereBetween('fecha_ingreso', [$c['inicio'], $c['fin']])
            ->with('taller')
            ->get()
            ->groupBy(fn ($ot) => optional($ot->taller)->nombre ?? 'Sin taller')
            ->map(fn ($g) => $g->count())
            ->sortDesc()
            ->map(fn ($v, $k) => ['etiqueta' => $k, 'valor' => $v])
            ->values()
            ->all();

        $alertas = [];
        if ($vencidas > 0) {
            $alertas[] = ['tipo' => 'warning', 'texto' => "{$vencidas} órdenes de taller vencidas sin cerrar"];
        }
        $porVencer = Neumatico::whereIn('id_entidad', $c['ids'])
            ->whereNotNull('fecha_plan_aviso')
            ->where('fecha_plan_aviso', '<=', $hoy->copy()->addDays(30)->toDateString())
            ->where(function ($q) use ($hoy) {
                $q->whereNull('fecha_retiro')
                    ->orWhere('fecha_retiro', '>', $hoy->toDateString());
            })
            ->count();
        if ($porVencer > 0) {
            $alertas[] = ['tipo' => 'danger', 'texto' => "{$porVencer} neumáticos por retirar en los próximos 30 días"];
        }
        if (empty($alertas)) {
            $alertas[] = ['tipo' => 'ok', 'texto' => 'Sin alertas críticas'];
        }

        return [
            'mesLabel' => $c['mesLabel'],
            'entidadNombre' => $c['entidadNombre'],
            'sinEntidad' => $c['sinEntidad'],
            'kpis' => [
                ['label' => 'En taller ahora', 'valor' => count($vehiculosTaller), 'icono' => 'pi pi-wrench', 'color' => 'bg-amber-500', 'subtexto' => 'Vehículos'],
                ['label' => 'OT abiertas', 'valor' => $otMes['abierta'], 'icono' => 'pi pi-file-edit', 'color' => 'bg-blue-500', 'subtexto' => $c['mesLabel']],
                ['label' => 'OT cerradas', 'valor' => $otMes['cerrada'], 'icono' => 'pi pi-check-circle', 'color' => 'bg-emerald-500', 'subtexto' => $c['mesLabel']],
                ['label' => 'Horas de taller', 'valor' => round($horasMes, 1), 'icono' => 'pi pi-clock', 'color' => 'bg-violet-500', 'subtexto' => 'Mes acumulado'],
            ],
            'vehiculosTaller' => $vehiculosTaller,
            'otResumen' => $otMes,
            'porTipoMtto' => $porTipoMtto,
            'porTaller' => $porTaller,
            'alertas' => $alertas,
            'vencidas' => $vencidas,
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // PROPUESTA 3 — SALUD DE COMPONENTES
    // ──────────────────────────────────────────────────────────────

    public function paraComponentes(Request $request): array
    {
        $c = $this->contexto($request);
        $hoy = now();

        $motores = Motore::whereIn('id_entidad', $c['ids']);
        $cajas = Caja::whereIn('id_entidad', $c['ids']);
        $diferenciales = Diferenciale::whereIn('id_entidad', $c['ids']);

        $kpiComponente = function ($query) {
            $total = (clone $query)->count();
            $kms = (clone $query)->avg('kms_acumulados') ?? 0;

            return ['total' => $total, 'kmsPromedio' => round((float) $kms)];
        };

        $motoresKpi = $kpiComponente($motores);
        $cajasKpi = $kpiComponente($cajas);
        $difKpi = $kpiComponente($diferenciales);

        $neumaticosTotal = Neumatico::whereIn('id_entidad', $c['ids'])->count();
        $neumaticosPorVencer = Neumatico::whereIn('id_entidad', $c['ids'])
            ->whereNotNull('fecha_plan_aviso')
            ->where('fecha_plan_aviso', '<=', $hoy->copy()->addDays(30)->toDateString())
            ->where(function ($q) use ($hoy) {
                $q->whereNull('fecha_retiro')
                    ->orWhere('fecha_retiro', '>', $hoy->toDateString());
            })
            ->count();

        $bateriasTotal = Bateria::whereIn('id_entidad', $c['ids'])->count();
        $bateriasBaja = Bateria::whereIn('id_entidad', $c['ids'])
            ->whereNotNull('id_motivo_baja')->count();

        $kpis = [
            ['label' => 'Motores', 'valor' => $motoresKpi['total'], 'icono' => 'pi pi-cog', 'color' => 'bg-blue-500', 'subtexto' => "Kms méd.: {$motoresKpi['kmsPromedio']}"],
            ['label' => 'Cajas', 'valor' => $cajasKpi['total'], 'icono' => 'pi pi-sitemap', 'color' => 'bg-emerald-500', 'subtexto' => "Kms méd.: {$cajasKpi['kmsPromedio']}"],
            ['label' => 'Diferenciales', 'valor' => $difKpi['total'], 'icono' => 'pi pi-share-alt', 'color' => 'bg-cyan-500', 'subtexto' => "Kms méd.: {$difKpi['kmsPromedio']}"],
            ['label' => 'Neumáticos', 'valor' => $neumaticosTotal, 'icono' => 'pi pi-circle-fill', 'color' => 'bg-fuchsia-500', 'subtexto' => "Por vencer: {$neumaticosPorVencer}"],
            ['label' => 'Baterías', 'valor' => $bateriasTotal, 'icono' => 'pi pi-bolt', 'color' => 'bg-orange-500', 'subtexto' => "Con baja: {$bateriasBaja}"],
        ];

        // Pronóstico de bajas de neumáticos por mes (ventana 6 meses).
        $pronostico = [];
        for ($i = 0; $i < 6; $i++) {
            $mes = $hoy->copy()->addMonths($i)->startOfMonth();
            $fin = $mes->copy()->endOfMonth()->toDateString();
            $ini = $mes->copy()->startOfMonth()->toDateString();
            $cant = Neumatico::whereIn('id_entidad', $c['ids'])
                ->whereNotNull('fecha_plan_aviso')
                ->whereBetween('fecha_plan_aviso', [$ini, $fin])
                ->count();
            $pronostico[] = [
                'mes' => ucfirst($mes->translatedFormat('M y')),
                'cantidad' => $cant,
            ];
        }

        $neumaticosPorVencerLista = Neumatico::whereIn('id_entidad', $c['ids'])
            ->whereNotNull('fecha_plan_aviso')
            ->where('fecha_plan_aviso', '<=', $hoy->copy()->addDays(30)->toDateString())
            ->where(function ($q) use ($hoy) {
                $q->whereNull('fecha_retiro')
                    ->orWhere('fecha_retiro', '>', $hoy->toDateString());
            })
            ->orderBy('fecha_plan_aviso')
            ->limit(15)
            ->get(['folio', 'medida', 'fecha_instalacion', 'fecha_plan_aviso', 'kms_promedio', 'fecha_retiro'])
            ->map(fn ($n) => [
                'folio' => $n->folio,
                'medida' => $n->medida,
                'planAviso' => $n->fecha_plan_aviso?->format('d/m/Y'),
                'kms' => $n->kms_promedio,
                'retirado' => (bool) $n->fecha_retiro,
            ])
            ->all();

        $bateriasLista = Bateria::whereIn('id_entidad', $c['ids'])
            ->orderByDesc('fecha_movimiento')
            ->limit(15)
            ->get(['folio', 'voltaje', 'amperaje', 'fecha_instalacion', 'fecha_movimiento', 'id_motivo_baja'])
            ->map(fn ($b) => [
                'folio' => $b->folio,
                'voltaje' => $b->voltaje,
                'amperaje' => $b->amperaje,
                'instalacion' => $b->fecha_instalacion?->format('d/m/Y'),
                'baja' => (bool) $b->id_motivo_baja,
            ])
            ->all();

        $componenteLista = function ($query) {
            return (clone $query)
                ->orderByDesc('fecha_instalacion')
                ->limit(15)
                ->get(['codigo', 'marca', 'modelo', 'numero_serie', 'kms_acumulados', 'fecha_instalacion', 'fecha_baja'])
                ->map(fn ($x) => [
                    'folio' => $x->codigo,
                    'marca' => $x->marca,
                    'modelo' => $x->modelo,
                    'serie' => $x->numero_serie,
                    'kms' => $x->kms_acumulados,
                    'instalacion' => $x->fecha_instalacion?->format('d/m/Y'),
                    'baja' => (bool) $x->fecha_baja,
                ])
                ->all();
        };

        $motoresLista = $componenteLista($motores);
        $cajasLista = $componenteLista($cajas);
        $diferencialesLista = $componenteLista($diferenciales);

        return [
            'mesLabel' => $c['mesLabel'],
            'entidadNombre' => $c['entidadNombre'],
            'sinEntidad' => $c['sinEntidad'],
            'kpis' => $kpis,
            'pronostico' => $pronostico,
            'neumaticosPorVencer' => $neumaticosPorVencerLista,
            'baterias' => $bateriasLista,
            'motores' => $motoresLista,
            'cajas' => $cajasLista,
            'diferenciales' => $diferencialesLista,
            'totales' => [
                'motores' => $motoresKpi,
                'cajas' => $cajasKpi,
                'diferenciales' => $difKpi,
                'neumaticosPorVencer' => $neumaticosPorVencer,
                'bateriasBaja' => $bateriasBaja,
            ],
        ];
    }


    // ──────────────────────────────────────────────────────────────
    // PROPUESTA PIZARRA 2 — PIZARRA OPERATIVA DIVIDIDA
    // ──────────────────────────────────────────────────────────────

    public function paraPizarraOperativa(Request $request): array
    {
        $c = $this->contexto($request);
        if ($c['sinEntidad']) {
            return $this->pizarraVacia($c, [
                'kpis' => [],
                'columnas' => [],
                'operaciones' => ['kpis' => [], 'donutEstado' => [], 'cpsTransito' => []],
                'taller' => ['vehiculosTaller' => []],
                'conflictos' => [],
            ]);
        }
        $ids = $c['ids'];
        $hoy = now();

        // --- Operaciones (Cartas de Porte del mes) ---
        $cps = CartaPorte::whereBetween('fecha_emision', [$c['inicio'], $c['fin']])
            ->whereNotNull('id_hoja_ruta')
            ->with([
                'hojaRuta:id,id_tractivo,id_arrastre',
                'chofer:id,nombre',
                'solicitud.cliente:id,nombre',
                'solicitud.lugarDestino:id,nombre',
                'aforos:id,id_carta_porte,id_factura',
            ])
            ->get();

        $emitidas = $cps->where('estado', 'emitida')->where('cancelada', false)->count();
        $recepcionadas = $cps->where('estado', 'recepcionada')->count();
        $canceladas = $cps->where('estado', 'cancelada')->count();
        $aforadas = $cps->filter(fn ($cp) => $cp->aforos->isNotEmpty())->count();
        $facturadas = $cps->filter(fn ($cp) => $this->cpFacturada($cp))->count();
        $toneladas = $cps->sum('toneladas');
        $kms = $cps->sum('distancia');
        $ingreso = $cps->sum(fn ($cp) => $cp->aforos->sum(fn ($a) => ($a->flete_mt ?: 0) + ($a->flete_mlc ?: 0)));

        $donutEstado = [
            ['etiqueta' => 'Emitidas', 'valor' => $emitidas],
            ['etiqueta' => 'Recepcionadas', 'valor' => $recepcionadas],
            ['etiqueta' => 'Canceladas', 'valor' => $canceladas],
        ];

        $tractivoCodigos = Tractivo::whereIn('id_entidad', $ids)->pluck('codigo', 'id');

        $cpsTransito = $cps->where('estado', 'emitida')->where('cancelada', false)
            ->map(function ($cp) use ($tractivoCodigos) {
                $hr = $cp->hojaRuta;

                return [
                    'folio' => $cp->numero,
                    'cliente' => optional($cp->solicitud?->cliente)->nombre,
                    'destino' => optional($cp->solicitud?->lugarDestino)->nombre,
                    'tractivo' => $hr && $hr->id_tractivo ? ($tractivoCodigos[$hr->id_tractivo] ?? '—') : '—',
                    'arrastre' => $hr && $hr->id_arrastre ? ('#' . $hr->id_arrastre) : '—',
                    'chofer' => optional($hr?->chofer)->nombrecompleto,
                    'kms' => $cp->distancia,
                    'toneladas' => $cp->toneladas,
                    'aforo' => $this->cpAforoEstado($cp),
                ];
            })->values()->all();

        // --- Tablero de flota (Kanban Disponible / En Taller) ---
        $flota = $this->tableroFlotaColumnas($c, $hoy, $cps);
        $columnas = $flota['columnas'];
        $enTaller = $flota['enTaller'];
        $disponible = $flota['disponible'];
        $idsEnTaller = $flota['idsEnTaller'];

        // --- Taller ---
        $vehiculos = $this->vehiculosEntidad($ids);
        $otMes = $this->otPorEstadoMes($c);
        $horasMes = OrdenesTaller::whereIn('id_entidad', $ids)
            ->whereBetween('fecha_ingreso', [$c['inicio'], $c['fin']])
            ->get()->sum(fn ($ot) => $ot->calcularTiempoTotal());

        $vehiculosTaller = $vehiculos->where('enTaller', true)->map(function ($v) use ($ids, $hoy) {
            $ot = OrdenesTaller::whereIn('id_entidad', $ids)
                ->where('id_tractivo', $v['id'])
                ->where('estado', 'abierta')->whereNull('cancelada')
                ->orderByDesc('fecha_ingreso')->first();
            $dias = $ot && $ot->fecha_ingreso ? $ot->fecha_ingreso->diffInDays($hoy) : null;

            return [
                'codigo' => $v['codigo'],
                'clase' => $v['clase'],
                'ot' => $ot ? $ot->numero : '—',
                'motivo' => optional($ot?->motivoEntrada)->nombre ?? '—',
                'dias' => $dias,
                'estado' => (int) $v['estado'] === self::ESTADO_PARALIZADO ? 'Paralizado' : 'En taller',
            ];
        })->sortByDesc('dias')->values()->all();

        $alertasNeumaticos = Neumatico::whereIn('id_entidad', $ids)
            ->whereNotNull('fecha_plan_aviso')
            ->where('fecha_plan_aviso', '<=', $hoy->copy()->addDays(30)->toDateString())
            ->where(function ($q) use ($hoy) {
                $q->whereNull('fecha_retiro')->orWhere('fecha_retiro', '>', $hoy->toDateString());
            })->count();

        // --- Conflictos: ya no aplica (panel centrado en Taller) ---
        $conflictos = [];

        // --- KPIs del panel Flota Técnico (cabecera) ---
        $flota = $this->paraFlota($request);
        $kpis = $flota['kpis'];

        // --- Desglose de flota por Tipo de Equipo / Marca-Modelo ---
        $desglose = [
            'tipo' => $this->desglose($vehiculos, 'tipo'),
            'marcaModelo' => $this->desglose($vehiculos, 'marcaModelo'),
        ];

        // --- Órdenes de Taller del mes ---
        $otsMes = OrdenesTaller::whereIn('id_entidad', $ids)
            ->whereBetween('fecha_ingreso', [$c['inicio'], $c['fin']])
            ->with(['taller:id,nombre', 'motivoEntrada:id,nombre'])
            ->orderByDesc('fecha_ingreso')
            ->get()
            ->map(function ($ot) use ($tractivoCodigos) {
                return [
                    'numero' => $ot->numero,
                    'vehiculo' => $tractivoCodigos[$ot->id_tractivo] ?? '—',
                    'taller' => optional($ot->taller)->nombre ?? '—',
                    'motivo' => optional($ot->motivoEntrada)->nombre ?? '—',
                    'estado' => $ot->estado,
                    'fecha' => optional($ot->fecha_ingreso)->format('d/m/Y'),
                ];
            })
            ->all();

        $componentes = $this->paraComponentes($request);

        return [
            'mesLabel' => $c['mesLabel'],
            'entidadNombre' => $c['entidadNombre'],
            'sinEntidad' => false,
            'kpis' => $kpis,
            'desglose' => $desglose,
            'columnas' => $columnas,
            'otsMes' => $otsMes,
            'taller' => [
                'vehiculosTaller' => $vehiculosTaller,
            ],
            'componentes' => $componentes,
            'estados' => \App\Models\EstadoComponente::orderBy('nombre')->get(['id', 'nombre'])->all(),
            'conflictos' => [],
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Tablero de flota (Kanban: Disponible / En Taller)
    // ──────────────────────────────────────────────────────────────

    private function tableroFlotaColumnas(array $c, Carbon $hoy, Collection $cps): array
    {
        $ids = $c['ids'];
        $tractivos = Tractivo::whereIn('id_entidad', $ids)
            ->with(['tipoVehiculo.tipoEquipo', 'tipoVehiculo.marca'])
            ->get();
        $arrastres = Arrastre::whereIn('id_entidad', $ids)
            ->with(['tipoVehiculo.tipoEquipo', 'tipoVehiculo.marca'])
            ->get();
        $arrastreCodigos = $arrastres->pluck('codigo', 'id');
        $neuMap = Neumatico::whereIn('id_tractivo', $tractivos->pluck('id')->all())
            ->whereNotNull('fecha_plan_aviso')
            ->where('fecha_plan_aviso', '>=', $hoy->toDateString())
            ->get()->groupBy('id_tractivo');

        $cpsActivas = $cps->where('estado', '<>', 'cancelada')->where('cancelada', false);
        $cpPorVehiculo = [];
        foreach ($cpsActivas as $cp) {
            $hr = $cp->hojaRuta;
            if (!$hr) {
                continue;
            }
            foreach (array_filter([$hr->id_tractivo, $hr->id_arrastre]) as $vid) {
                $k = (string) $vid;
                if (!isset($cpPorVehiculo[$k]) || $cp->estado === 'emitida') {
                    $cpPorVehiculo[$k] = $cp;
                }
            }
        }

        $ots = OrdenesTaller::whereIn('id_tractivo', $tractivos->pluck('id')->all())
            ->where('estado', 'abierta')->whereNull('cancelada')
            ->with(['taller:id,nombre', 'motivoEntrada:id,nombre'])
            ->get();
        $otPorTractivo = [];
        foreach ($ots as $ot) {
            $otPorTractivo[(string) $ot->id_tractivo] = $ot;
        }

        $vehiculos = [];
        $idsEnTaller = [];

        foreach ($tractivos as $t) {
            $k = (string) $t->id;
            $cp = $cpPorVehiculo[$k] ?? null;
            $ot = $otPorTractivo[$k] ?? null;
            // Regla de negocio: el vehículo está "en taller" si y solo si tiene
            // una Orden de Taller abierta. Sin OT abierta => se considera activo.
            $enTaller = (bool) $ot;
            $baja = (int) $t->id_tipo_estado === 27 || !empty($t->fecha_baja);
            if ($enTaller) {
                $idsEnTaller[] = $k;
            }
            $arrastreCodigo = null;
            if ($cp && $cp->hojaRuta && $cp->hojaRuta->id_arrastre && isset($arrastreCodigos[$cp->hojaRuta->id_arrastre])) {
                $arrastreCodigo = $arrastreCodigos[$cp->hojaRuta->id_arrastre];
            }
            $proximaBaja = null;
            $neu = $neuMap->get($t->id, collect());
            if ($neu->isNotEmpty()) {
                $min = $neu->min('fecha_plan_aviso');
                if ($min) {
                    $diasBaja = (int) round($hoy->diffInDays(Carbon::parse($min)));
                    $proximaBaja = ['tipo' => 'Neumático', 'fecha' => Carbon::parse($min)->format('d/m/Y'), 'dias' => $diasBaja];
                }
            }
            $imagen = null;
            $te = optional($t->tipoVehiculo?->tipoEquipo)->imagen;
            if ($te) {
                $imagen = asset('storage/' . ltrim($te, '/'));
            }
            $vehiculos[] = [
                'id' => $t->id,
                'codigo' => $t->codigo,
                'esArrastre' => false,
                'tipoVehiculo' => optional($t->tipoVehiculo?->tipoEquipo)->nombre ?? '—',
                'marca' => optional($t->tipoVehiculo?->marca)->nombre ?? '—',
                'arrastre' => $arrastreCodigo,
                'estado' => $t->id_tipo_estado,
                'baja' => $baja,
                'enTaller' => $enTaller,
                'imagen' => $imagen,
                'cp' => $cp ? ['folio' => $cp->numero, 'cliente' => optional($cp->solicitud?->cliente)->nombre, 'destino' => optional($cp->solicitud?->lugarDestino)->nombre, 'estado' => $cp->estado] : null,
                'ot' => $ot ? [
                    'numero' => $ot->numero,
                    'taller' => optional($ot->taller)->nombre,
                    'motivo' => optional($ot->motivoEntrada)->nombre,
                    'dias' => $ot->fecha_ingreso ? (int) round($ot->fecha_ingreso->diffInDays($hoy)) : null,
                ] : null,
                'proximaBaja' => $proximaBaja,
                'conflicto' => false,
            ];
        }

        // Arrastres (tabla propia) — se agrupan aparte en el frontend (esArrastre = true).
        foreach ($arrastres as $a) {
            $k = (string) $a->id;
            $cp = $cpPorVehiculo[$k] ?? null;
            $enTaller = in_array((int) $a->id_tipo_estado, [self::ESTADO_EN_TALLER, self::ESTADO_PARALIZADO], true);
            $baja = (int) $a->id_tipo_estado === 27 || !empty($a->fecha_baja);
            if ($enTaller) {
                $idsEnTaller[] = $k;
            }
            $imagen = null;
            $te = optional($a->tipoVehiculo?->tipoEquipo)->imagen;
            if ($te) {
                $imagen = asset('storage/' . ltrim($te, '/'));
            }
            $vehiculos[] = [
                'id' => $a->id,
                'codigo' => $a->codigo,
                'esArrastre' => true,
                'tipoVehiculo' => optional($a->tipoVehiculo?->tipoEquipo)->nombre ?? '—',
                'marca' => optional($a->tipoVehiculo?->marca)->nombre ?? '—',
                'arrastre' => null,
                'estado' => $a->id_tipo_estado,
                'baja' => $baja,
                'enTaller' => $enTaller,
                'imagen' => $imagen,
                'cp' => $cp ? ['folio' => $cp->numero, 'cliente' => optional($cp->solicitud?->cliente)->nombre, 'destino' => optional($cp->solicitud?->lugarDestino)->nombre, 'estado' => $cp->estado] : null,
                'ot' => null,
                'proximaBaja' => null,
                'conflicto' => false,
            ];
        }

        $columnas = [
            'todos' => ['clave' => 'todos', 'titulo' => 'Flota', 'color' => 'border-gray-200', 'vehiculos' => $vehiculos],
        ];

        return ['columnas' => $columnas, 'enTaller' => count($idsEnTaller), 'disponible' => count($vehiculos), 'idsEnTaller' => $idsEnTaller];
    }

    /**
     * Desglose de flota por un campo, con conteo de activos / taller / baja / total.
     */
    private function desglose(Collection $vehiculos, string $campo): array
    {
        return $vehiculos->groupBy($campo)
            ->map(function ($g, $k) {
                // Baja = con fecha de baja; Taller = en taller (sin baja);
                // Activos = el resto. Mutuamente excluyentes → la suma cierra.
                $baja = $g->where('baja', true)->count();
                $taller = $g->where('enTaller', true)->where('baja', false)->count();
                $activos = $g->count() - $baja - $taller;

                return [
                    'etiqueta' => $k ?: 'Sin definir',
                    'activos' => $activos,
                    'taller' => $taller,
                    'baja' => $baja,
                    'total' => $g->count(),
                ];
            })
            ->sortBy(fn ($r) => mb_strtolower($r['etiqueta']))
            ->values()
            ->all();
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers pizarras
    // ──────────────────────────────────────────────────────────────

    private function pizarraVacia(array $c, array $extra = []): array
    {
        return array_merge([
            'mesLabel' => $c['mesLabel'],
            'entidadNombre' => $c['entidadNombre'],
            'sinEntidad' => true,
        ], $extra);
    }

    private function cpFacturada($cp): bool
    {
        foreach ($cp->aforos as $aforo) {
            if (!empty($aforo->id_factura)) {
                return true;
            }
        }
        return false;
    }

    private function cpAforoEstado($cp): string
    {
        if ($cp->aforos->isEmpty()) {
            return 'Sin aforo';
        }
        return $this->cpFacturada($cp) ? 'Facturado' : 'Pendiente';
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers compartidos
    // ──────────────────────────────────────────────────────────────

    private function agrupar(Collection $vehiculos, string $campo, ?int $top = null): array
    {
        $grupos = $vehiculos->groupBy($campo)
            ->map(fn ($g, $k) => ['etiqueta' => $k ?: 'Sin definir', 'valor' => $g->count()])
            ->sortByDesc('valor')
            ->values();

        if ($top) {
            $recortado = $grupos->take($top);
            $resto = $grupos->skip($top)->sum('valor');
            if ($resto > 0) {
                $recortado->push(['etiqueta' => 'Otros', 'valor' => $resto]);
            }

            return $recortado->all();
        }

        return $grupos->all();
    }

    private function otPorEstadoMes(array $c): array
    {
        $ots = OrdenesTaller::whereIn('id_entidad', $c['ids'])
            ->whereBetween('fecha_ingreso', [$c['inicio'], $c['fin']])
            ->get();

        return [
            'abierta' => $ots->where('estado', 'abierta')->count(),
            'cerrada' => $ots->where('estado', 'cerrada')->count(),
            'cancelada' => $ots->where('cancelada', true)->count(),
            'total' => $ots->count(),
        ];
    }

    private function serieOtDiaria(array $c): array
    {
        $ots = OrdenesTaller::whereIn('id_entidad', $c['ids'])
            ->whereBetween('fecha_ingreso', [$c['inicio'], $c['fin']])
            ->selectRaw('fecha_ingreso, count(*) as total')
            ->groupBy('fecha_ingreso')
            ->pluck('total', 'fecha_ingreso');

        $serie = [];
        for ($d = Carbon::parse($c['inicio']); $d->lte(Carbon::parse($c['fin'])); $d->addDay()) {
            $fecha = $d->toDateString();
            $serie[] = ['fecha' => $fecha, 'ot' => (int) ($ots[$fecha] ?? 0)];
        }

        return $serie;
    }
}
