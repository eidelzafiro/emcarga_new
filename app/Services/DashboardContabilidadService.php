<?php

namespace App\Services;

use App\Models\AmortizacionTaller;

use App\Models\Factura;
use App\Models\GastoMaterial;

use App\Models\Tarjeta;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardContabilidadService
{
    use EntidadScoping;

    public function datos(): array
    {
        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $fecha = Carbon::parse($fechaOperaciones);
        $inicioMes = $fecha->copy()->startOfMonth()->toDateString();
        $finMes = $fecha->copy()->endOfMonth()->toDateString();
        $idsEntidades = $this->entidadesPermitidas();

        // ═══ COMBUSTIBLE ═══

        $tarjetasPorTipo = Tarjeta::query()
            ->select(
                'idtipocombustibles',
                'estado',
                DB::raw('COUNT(*) as cantidad')
            )
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
            ->groupBy('idtipocombustibles', 'estado')
            ->with('tipoCombustible:id,nombre')
            ->get()
            ->map(fn ($row) => [
                'tipo_combustible' => $row->tipoCombustible?->nombre ?? 'Sin tipo',
                'estado' => $row->estado ?? 'activo',
                'cantidad' => (int) $row->cantidad,
            ])
            ->groupBy('tipo_combustible')
            ->map(fn ($grupo, $tipo) => [
                'tipo_combustible' => $tipo,
                'estados' => $grupo->pluck('estado', 'estado')->keys()->map(fn ($e) => [
                    'estado' => $e,
                    'cantidad' => $grupo->where('estado', $e)->sum('cantidad'),
                ])->values(),
                'total' => $grupo->sum('cantidad'),
            ])
            ->values()
            ->all();

        // Saldo actual (snapshot de la tabla tarjetas)
        $combustibleActual = Tarjeta::query()
            ->select(
                'idtipocombustibles',
                'idmonedas',
                DB::raw('SUM(saldo_actual) as total_mon'),
                DB::raw('SUM(saldoactuallts) as total_lts')
            )
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
            ->groupBy('idtipocombustibles', 'idmonedas')
            ->with('tipoCombustible:id,nombre')
            ->with('moneda:id,codigo')
            ->get()
            ->map(fn ($row) => [
                'tipo_combustible' => $row->tipoCombustible?->nombre ?? 'Sin tipo',
                'moneda' => $row->moneda?->codigo ?? '—',
                'total_mon' => round((float) $row->total_mon, 2),
                'total_lts' => round((float) $row->total_lts, 2),
            ])
            ->all();

        // Saldo inicio: último cierre del mes anterior o saldos iniciales de tarjetas
        $inicioMesAnterior = $fecha->copy()->subMonth()->startOfMonth()->toDateString();
        $finMesAnterior = $fecha->copy()->subMonth()->endOfMonth()->toDateString();
        $cierresMesAnterior = DB::table('cierre_tarjetas as ct')
            ->select(
                't.idtipocombustibles',
                't.idmonedas',
                DB::raw('SUM(ct.saldoactualmon) as total_mon'),
                DB::raw('SUM(ct.saldoactuallts) as total_lts')
            )
            ->join('tarjetas as t', 't.id', '=', 'ct.id_tarjeta')
            ->whereBetween('ct.ftrabajo', [$inicioMesAnterior, $finMesAnterior])
            ->when(! empty($idsEntidades), function ($q) use ($idsEntidades) {
                $q->whereIn('ct.id_entidad', $idsEntidades);
            })
            ->groupBy('t.idtipocombustibles', 't.idmonedas')
            ->get()
            ->all();

        // Si no hay cierre del mes anterior, usar saldos iniciales de las tarjetas
        $saldoInicio = collect($cierresMesAnterior);
        if ($saldoInicio->isEmpty()) {
            $saldoInicio = Tarjeta::query()
                ->select(
                    'idtipocombustibles',
                    'idmonedas',
                    DB::raw('SUM(saldoinicialmon) as total_mon'),
                    DB::raw('SUM(saldoiniciallts) as total_lts')
                )
                ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
                ->groupBy('idtipocombustibles', 'idmonedas')
                ->with('tipoCombustible:id,nombre')
                ->with('moneda:id,codigo')
                ->get()
                ->map(fn ($row) => (object) [
                    'idtipocombustibles' => $row->idtipocombustibles,
                    'idmonedas' => $row->idmonedas,
                    'total_mon' => $row->total_mon,
                    'total_lts' => $row->total_lts,
                    'nombre' => $row->tipoCombustible?->nombre ?? 'Sin tipo',
                    'moneda_codigo' => $row->moneda?->codigo ?? '—',
                ])
                ->all();
        }

        // Cargado en el mes (detalles_carga_combustible → combustible_cargas)
        $combustibleCargado = DB::table('detalles_carga_combustible as dcc')
            ->select(
                DB::raw('COALESCE(tc.nombre, "Sin tipo") as tipo_combustible'),
                DB::raw('COALESCE(m.codigo, "—") as moneda'),
                DB::raw('SUM(dcc.saldo_mon) as total_mon'),
                DB::raw('SUM(dcc.saldo_lts) as total_lts')
            )
            ->join('combustible_cargas as cc', 'cc.id', '=', 'dcc.id_carga')
            ->leftJoin('tipos_combustibles as tc', 'tc.id', '=', 'cc.id_tipo_combustibles')
            ->leftJoin('monedas as m', 'm.id', '=', 'cc.id_monedas')
            ->whereBetween('cc.fcarga', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), function ($q) use ($idsEntidades) {
                $q->whereIn('cc.id_entidad', $idsEntidades);
            })
            ->groupBy(DB::raw('COALESCE(tc.nombre, "Sin tipo")'), DB::raw('COALESCE(m.codigo, "—")'))
            ->get()
            ->all();

        // Descargado en el mes
        $combustibleDescargado = DB::table('combustible_descargas as cd')
            ->select(
                DB::raw('COALESCE(tc.nombre, "Sin tipo") as tipo_combustible'),
                DB::raw('COALESCE(m.codigo, "—") as moneda'),
                DB::raw('SUM(cd.saldo_mon) as total_mon'),
                DB::raw('SUM(cd.saldo_lts) as total_lts')
            )
            ->join('tarjetas as t', 't.id', '=', 'cd.id_tarjeta')
            ->leftJoin('tipos_combustibles as tc', 'tc.id', '=', 't.idtipocombustibles')
            ->leftJoin('monedas as m', 'm.id', '=', 't.idmonedas')
            ->whereBetween('cd.fdescarga', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), function ($q) use ($idsEntidades) {
                $q->whereIn('cd.id_entidad', $idsEntidades);
            })
            ->groupBy(DB::raw('COALESCE(tc.nombre, "Sin tipo")'), DB::raw('COALESCE(m.codigo, "—")'))
            ->get()
            ->all();

        // ═══ FACTURACIÓN ═══

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

        // ═══ COMBUSTIBLE: Saldo por tipo (inicio + cargado - descargado) ═══
        $saldoInicioMap = [];
        foreach ($saldoInicio as $c) {
            $tipoNombre = is_object($c) && isset($c->nombre) ? $c->nombre : ($c->tipoCombustible->nombre ?? 'Sin tipo');
            $monedaCodigo = is_object($c) && isset($c->moneda_codigo) ? $c->moneda_codigo : ($c->moneda->codigo ?? '—');
            $key = $tipoNombre . '|' . $monedaCodigo;
            $saldoInicioMap[$key] = [
                'tipo' => $tipoNombre,
                'moneda' => $monedaCodigo,
                'inicio_mon' => (float) $c->total_mon,
                'inicio_lts' => (float) $c->total_lts,
            ];
        }
        $cargadoMap = [];
        foreach ($combustibleCargado as $c) {
            $key = $c->tipo_combustible . '|' . $c->moneda;
            $cargadoMap[$key] = ['tipo' => $c->tipo_combustible, 'moneda' => $c->moneda, 'cargado_mon' => (float) $c->total_mon, 'cargado_lts' => (float) $c->total_lts];
        }
        $descargadoMap = [];
        foreach ($combustibleDescargado as $c) {
            $key = $c->tipo_combustible . '|' . $c->moneda;
            $descargadoMap[$key] = ['tipo' => $c->tipo_combustible, 'moneda' => $c->moneda, 'descargado_mon' => (float) $c->total_mon, 'descargado_lts' => (float) $c->total_lts];
        }
        $todasClaves = array_unique(array_merge(array_keys($saldoInicioMap), array_keys($cargadoMap), array_keys($descargadoMap)));
        $saldoPorTipo = [];
        foreach ($todasClaves as $key) {
            $ini = $saldoInicioMap[$key] ?? null;
            $car = $cargadoMap[$key] ?? null;
            $des = $descargadoMap[$key] ?? null;
            $tipo = $ini['tipo'] ?? ($car['tipo'] ?? ($des['tipo'] ?? 'Sin tipo'));
            $moneda = $ini['moneda'] ?? ($car['moneda'] ?? ($des['moneda'] ?? '—'));
            $inicioMon = $ini['inicio_mon'] ?? 0;
            $inicioLts = $ini['inicio_lts'] ?? 0;
            $cargadoMon = $car['cargado_mon'] ?? 0;
            $cargadoLts = $car['cargado_lts'] ?? 0;
            $descargadoMon = $des['descargado_mon'] ?? 0;
            $descargadoLts = $des['descargado_lts'] ?? 0;
            $saldoPorTipo[] = [
                'tipo' => $tipo,
                'moneda' => $moneda,
                'inicio_mon' => round($inicioMon, 2),
                'inicio_lts' => round($inicioLts, 2),
                'cargado_mon' => round($cargadoMon, 2),
                'cargado_lts' => round($cargadoLts, 2),
                'descargado_mon' => round($descargadoMon, 2),
                'descargado_lts' => round($descargadoLts, 2),
                'final_mon' => round($inicioMon + $cargadoMon - $descargadoMon, 2),
                'final_lts' => round($inicioLts + $cargadoLts - $descargadoLts, 2),
            ];
        }

        // ═══ COSTOS ═══

        $gastoMaterialPorConcepto = GastoMaterial::query()
            ->select(
                'nombre',
                DB::raw('SUM(valor_mn) as total_mn'),
                DB::raw('SUM(cantidad) as total_cantidad')
            )
            ->whereBetween('fecha', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
            ->groupBy('nombre')
            ->orderByDesc('total_mn')
            ->get()
            ->map(fn ($row) => [
                'concepto' => $row->nombre ?? 'Sin concepto',
                'total_mn' => round((float) $row->total_mn, 2),
                'cantidad' => round((float) $row->total_cantidad, 3),
            ])
            ->all();

        $otrosGastosPorConcepto = DB::table('otros_gastos as og')
            ->select(
                DB::raw('COALESCE(ci.nombre, og.concepto, "Sin concepto") as concepto'),
                DB::raw('SUM(og.monto_mn) as total_mn'),
                DB::raw('SUM(og.monto_mlc) as total_mlc'),
                DB::raw('COUNT(*) as cantidad')
            )
            ->leftJoin('catalogo_items as ci', 'ci.id', '=', 'og.id_tipo_concepto')
            ->whereNull('og.deleted_at')
            ->whereBetween('og.fecha', [$inicioMes, $finMes])
            ->groupBy(DB::raw('COALESCE(ci.nombre, og.concepto, "Sin concepto")'))
            ->orderByDesc('total_mn')
            ->get()
            ->map(fn ($row) => [
                'concepto' => $row->concepto,
                'total_mn' => round((float) $row->total_mn, 2),
                'total_mlc' => round((float) $row->total_mlc, 2),
                'cantidad' => (int) $row->cantidad,
            ])
            ->all();

        $amortizacionTaller = AmortizacionTaller::query()
            ->select(
                'id_tractivo',
                DB::raw('SUM(amortizacion_mn) as total_amortizacion'),
                DB::raw('SUM(chapa) as total_chapa'),
                DB::raw('COUNT(*) as registros')
            )
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades))
            ->groupBy('id_tractivo')
            ->with('tractivo:id,codigo')
            ->orderByDesc('total_amortizacion')
            ->get()
            ->map(fn ($row) => [
                'tractivo' => $row->tractivo?->codigo ?? 'Sin asignar',
                'amortizacion_mn' => round((float) $row->total_amortizacion, 2),
                'chapa' => round((float) $row->total_chapa, 2),
                'registros' => (int) $row->registros,
            ])
            ->all();

        $totales = [
            'combustible_cargado_mon' => collect($combustibleCargado)->sum('total_mon'),
            'combustible_cargado_lts' => collect($combustibleCargado)->sum('total_lts'),
            'combustible_descargado_mon' => collect($combustibleDescargado)->sum('total_mon'),
            'combustible_descargado_lts' => collect($combustibleDescargado)->sum('total_lts'),
            'ingresos_mt' => collect($ingresosPorConcepto)->sum('total_mt'),
            'total_facturas' => collect($facturacionPorCliente)->sum('cantidad_facturas'),
            'gasto_material_mn' => collect($gastoMaterialPorConcepto)->sum('total_mn'),
            'otros_gastos_mn' => collect($otrosGastosPorConcepto)->sum('total_mn'),
            'amortizacion_mn' => collect($amortizacionTaller)->sum('amortizacion_mn'),
        ];

        return [
            'fechaOperaciones' => $fechaOperaciones,
            'tarjetasPorTipo' => $tarjetasPorTipo,
            'combustibleActual' => $combustibleActual,
            'saldoPorTipo' => $saldoPorTipo,
            'combustibleCargado' => $combustibleCargado,
            'combustibleDescargado' => $combustibleDescargado,
            'ingresosPorConcepto' => $ingresosPorConcepto,
            'facturacionPorCliente' => $facturacionPorCliente,
            'gastoMaterialPorConcepto' => $gastoMaterialPorConcepto,
            'otrosGastosPorConcepto' => $otrosGastosPorConcepto,
            'amortizacionTaller' => $amortizacionTaller,
            'totales' => $totales,
        ];
    }
}
