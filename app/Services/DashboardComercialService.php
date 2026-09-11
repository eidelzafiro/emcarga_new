<?php

namespace App\Services;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Aforo;
use App\Models\CartaPorte;
use App\Models\Entidad;
use App\Models\Factura;
use App\Models\SolicitudesServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

        // ═══ KPIs ═══

        $facturasQuery = Factura::query()
            ->where('cancelada', false)
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades));

        $facturasDelMes = (clone $facturasQuery)->count();
        $ingresosFacturados = round((float) (clone $facturasQuery)->sum('ingreso_mt'), 2);

        // Aforos del mes (scoping vía carta de porte -> entidades permitidas)
        $aforosQuery = Aforo::query()
            ->whereBetween('fecha_parte', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereHas('cartaPorte', fn ($c) => $this->whereCartaEnEntidades($c, $idsEntidades)));

        $aforosDelMes = (clone $aforosQuery)->count();
        $ingresosAforados = round((float) (clone $aforosQuery)->sum('ingreso_mt'), 2);

        // CP del mes
        $cpDelMes = CartaPorte::query()
            ->where('cancelada', false)
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->where(function ($w) use ($idsEntidades) {
                $w->whereHas('hojaRuta', fn ($h) => $h->whereIn('id_entidad', $idsEntidades))
                    ->orWhereHas('hojaRuta.tractivo', fn ($t) => $t->whereIn('id_entidad', $idsEntidades))
                    ->orWhereHas('solicitud', fn ($s) => $s->whereIn('id_entidad', $idsEntidades));
            }))
            ->count();

        // Clientes activos del mes (con CP no canceladas)
        $clientesActivos = CartaPorte::query()
            ->where('cancelada', false)
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->whereHas('solicitud')
            ->when(! empty($idsEntidades), fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->whereIn('id_entidad', $idsEntidades)))
            ->with('solicitud:id,id_cliente')
            ->get()
            ->map(fn ($c) => $c->solicitud?->id_cliente)
            ->filter()
            ->unique()
            ->count();

        // ═══ SECCIÓN: Facturación por concepto ═══
        $facturacionPorConcepto = (clone $facturasQuery)
            ->select('id_tipo_ingreso', DB::raw('SUM(ingreso_mt) as total_mt'), DB::raw('COUNT(*) as cantidad'))
            ->whereNotNull('id_tipo_ingreso')
            ->groupBy('id_tipo_ingreso')
            ->with('tipoIngreso:id,nombre')
            ->get()
            ->map(fn ($fila) => [
                'concepto' => $fila->tipoIngreso?->nombre ?? 'Sin concepto',
                'total_mt' => round((float) $fila->total_mt, 2),
                'cantidad' => (int) $fila->cantidad,
            ])
            ->sortByDesc('total_mt')
            ->values()
            ->all();

        // ═══ SECCIÓN: Facturación por cliente (top 10) ═══
        $facturacionPorCliente = (clone $facturasQuery)
            ->select('id_cliente', DB::raw('SUM(ingreso_mt) as total_mt'), DB::raw('COUNT(*) as cantidad'))
            ->whereNotNull('id_cliente')
            ->groupBy('id_cliente')
            ->with('cliente:id,nombre')
            ->get()
            ->map(fn ($fila) => [
                'cliente' => $fila->cliente?->nombre ?? 'Sin cliente',
                'total_mt' => round((float) $fila->total_mt, 2),
                'cantidad' => (int) $fila->cantidad,
                'porcentaje' => $ingresosFacturados > 0 ? round(((float) $fila->total_mt / $ingresosFacturados) * 100, 1) : 0,
            ])
            ->sortByDesc('total_mt')
            ->slice(0, 10)
            ->values()
            ->all();

        // ═══ SECCIÓN: Aforos por estado ═══
        $aforosBase = Aforo::query()
            ->whereBetween('fecha_parte', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereHas('cartaPorte', fn ($c) => $this->whereCartaEnEntidades($c, $idsEntidades)));

        $aforosPendientes = (clone $aforosBase)->whereNull('id_prefactura')->whereNull('id_factura')->count();
        $aforosPrefacturados = (clone $aforosBase)->whereNotNull('id_prefactura')->whereNull('id_factura')->count();
        $aforosFacturados = (clone $aforosBase)->whereNotNull('id_factura')->count();

        $aforosPorEstado = [
            ['estado' => 'pendiente', 'etiqueta' => 'Pendientes', 'total' => $aforosPendientes, 'color' => 'text-amber-500'],
            ['estado' => 'prefacturado', 'etiqueta' => 'Prefacturados', 'total' => $aforosPrefacturados, 'color' => 'text-cyan-500'],
            ['estado' => 'facturado', 'etiqueta' => 'Facturados', 'total' => $aforosFacturados, 'color' => 'text-emerald-500'],
        ];

        // ═══ SECCIÓN: Solicitudes del mes por estado ═══
        $solicitudesQuery = SolicitudesServicio::query()
            ->whereBetween('fecha_solicitud', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->whereIn('id_entidad', $idsEntidades));

        $solicitudesPorEstado = (clone $solicitudesQuery)
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->get()
            ->map(fn ($fila) => [
                'estado' => $fila->estado,
                'etiqueta' => ucfirst(str_replace('_', ' ', $fila->estado)),
                'total' => (int) $fila->total,
            ])
            ->values()
            ->all();

        // ═══ SECCIÓN: CP del mes por estado ═══
        $cpBase = CartaPorte::query()
            ->where('cancelada', false)
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->when(! empty($idsEntidades), fn ($q) => $q->where(function ($w) use ($idsEntidades) {
                $w->whereHas('hojaRuta', fn ($h) => $h->whereIn('id_entidad', $idsEntidades))
                    ->orWhereHas('hojaRuta.tractivo', fn ($t) => $t->whereIn('id_entidad', $idsEntidades))
                    ->orWhereHas('solicitud', fn ($s) => $s->whereIn('id_entidad', $idsEntidades));
            }));

        $cpPorEstado = (clone $cpBase)
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->get()
            ->map(fn ($fila) => [
                'estado' => $fila->estado,
                'etiqueta' => ucfirst(str_replace('_', ' ', $fila->estado)),
                'total' => (int) $fila->total,
            ])
            ->values()
            ->all();

        // ═══ SERIE: Ingresos (aforos ingreso_mt) por día del mes ═══
        $serie = $this->serieIngresosDiarios($inicioMes, $finMes, $idsEntidades);

        $totales = [
            'facturas_del_mes' => $facturasDelMes,
            'ingresos_facturados' => $ingresosFacturados,
            'aforos_del_mes' => $aforosDelMes,
            'ingresos_aforados' => $ingresosAforados,
            'cp_del_mes' => $cpDelMes,
            'clientes_activos' => $clientesActivos,
        ];

        return [
            'fechaOperaciones' => $fechaOperaciones,
            'entidadNombre' => $entidadNombre,
            'entidad' => $entidad ? [
                'nombre' => $entidad->nombre,
                'abreviatura' => $entidad->abreviatura,
            ] : null,
            'totales' => $totales,
            'facturacionPorConcepto' => $facturacionPorConcepto,
            'facturacionPorCliente' => $facturacionPorCliente,
            'aforosPorEstado' => $aforosPorEstado,
            'solicitudesPorEstado' => $solicitudesPorEstado,
            'cpPorEstado' => $cpPorEstado,
            'serie' => $serie,
        ];
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