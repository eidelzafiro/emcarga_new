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
                'concepto' => $row->tipoIngreso?->nombre ?? 'Sin concepto',
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
