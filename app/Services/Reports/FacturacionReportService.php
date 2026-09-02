<?php

namespace App\Services\Reports;

use App\Models\CartaPorte;
use App\Models\Cliente;
use App\Models\Conciliacione;
use App\Models\Entidad;
use App\Models\Factura;
use App\Models\Prefactura;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

/**
 * Reportes del módulo FACTURACION (grupo migrado en R-1).
 *
 * Listados y resúmenes mensuales de facturación, cartas de porte pendientes
 * de facturar, comprobantes (prefacturas), firmas de clientes y conciliaciones.
 *
 * Filtros aceptados (array $filtros procedente del catálogo `reportes`):
 *  - 'fecha' => 'YYYY-MM-DD' (día) o 'YYYY-MM' (mes)
 *  - 'mes'   => 'YYYY-MM'
 *  - 'mes1'  => 'YYYY-MM'
 */
class FacturacionReportService
{
    private function entidadIds(): array
    {
        $activa = (int) entidadActivaId();
        if (! $activa) {
            return [23];
        }

        return Entidad::subEntidadesIds($activa);
    }

    private function rangoFiltros(array $filtros): array
    {
        $valor = $filtros['fecha'] ?? $filtros['mes'] ?? $filtros['mes1'] ?? null;
        if (! $valor) {
            $valor = now()->format('Y-m');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return [Carbon::parse($valor)->startOfDay(), Carbon::parse($valor)->endOfDay()];
        }

        return [Carbon::parse($valor.'-01')->startOfMonth(), Carbon::parse($valor.'-01')->endOfMonth()];
    }

    private function nombreMes(int $m): string
    {
        return match ($m) {
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
            default => '',
        };
    }

    private function periodoTexto(array $filtros): string
    {
        if (! empty($filtros['mes'])) {
            try {
                $m = Carbon::parse($filtros['mes']);

                return 'Período: '.$this->nombreMes($m->month).' '.$m->year;
            } catch (\Exception) {}
        }
        [$d, $h] = $this->rangoFiltros($filtros);

        return $d && $h ? "Período: $d al $h" : '';
    }

    private function reporteTablaPdf(string $titulo, array $columnas, array $filas, array $opts = []): \Illuminate\Http\Response
    {
        $periodo = $opts['periodo'] ?? '';
        $pdf = Pdf::loadHTML(view('reports.reporte_tabla', compact('titulo', 'columnas', 'filas', 'periodo'))->render());

        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf']);
    }

    // 13 — IMPRESION DE FACTURAS
    public function facturasImpresion(array $filtros): \Illuminate\Http\Response
    {
        [$desde, $hasta] = $this->rangoFiltros($filtros);
        $ids = $this->entidadIds();

        $facturas = Factura::with('cliente:id,nombre')
            ->whereIn('id_entidad', $ids)
            ->whereBetween('fecha_emision', [$desde, $hasta])
            ->orderBy('fecha_emision')->orderBy('numero')
            ->get();

        $columnas = [
            ['key' => 'numero', 'label' => 'No.', 'num' => false],
            ['key' => 'fecha_emision', 'label' => 'Fecha', 'num' => false],
            ['key' => 'cliente', 'label' => 'Cliente', 'num' => false],
            ['key' => 'ingreso_mt', 'label' => 'Ingreso MT', 'num' => true],
            ['key' => 'flete_mlc', 'label' => 'Flete MLC', 'num' => true],
            ['key' => 'estado', 'label' => 'Estado', 'num' => false],
        ];

        $filas = $facturas->map(fn ($f) => [
            'numero' => $f->numero,
            'fecha_emision' => optional($f->fecha_emision)?->format('d/m/Y'),
            'cliente' => optional($f->cliente)->nombre,
            'ingreso_mt' => number_format((float) ($f->ingreso_mt ?? 0), 2),
            'flete_mlc' => number_format((float) ($f->flete_mlc ?? 0), 2),
            'estado' => $f->estado ?? ($f->cancelada ? 'CANCELADA' : 'VIGENTE'),
        ])->all();

        return $this->reporteTablaPdf('Impresión de Facturas', $columnas, $filas, ['periodo' => $this->periodoTexto($filtros)]);
    }

    // 14 — LISTADO CARTAS PORTES A FACTURAR
    public function cartasPorteAFacturar(array $filtros): \Illuminate\Http\Response
    {
        [$desde, $hasta] = $this->rangoFiltros($filtros);
        $ids = $this->entidadIds();

        $cartas = CartaPorte::with([
            'tractivo' => fn ($q) => $q->select('id', 'placa', 'codigo', 'id_entidad'),
            'cliente' => fn ($q) => $q->select('id', 'nombre'),
            'hojaRuta' => fn ($q) => $q->select('id', 'origen', 'destino'),
        ])
            ->whereDoesntHave('aforos', fn ($q) => $q->whereNotNull('id_factura'))
            ->whereBetween('fecha_emision', [$desde, $hasta])
            ->whereHas('tractivo', fn ($q) => $q->whereIn('tractivos.id_entidad', $ids))
            ->orderBy('fecha_emision')->orderBy('numero')
            ->get();

        $columnas = [
            ['key' => 'numero', 'label' => 'No. CP', 'num' => false],
            ['key' => 'fecha_emision', 'label' => 'Fecha', 'num' => false],
            ['key' => 'cliente', 'label' => 'Cliente', 'num' => false],
            ['key' => 'tractivo', 'label' => 'Tractivo', 'num' => false],
            ['key' => 'origen', 'label' => 'Origen', 'num' => false],
            ['key' => 'destino', 'label' => 'Destino', 'num' => false],
        ];

        $filas = $cartas->map(fn ($c) => [
            'numero' => $c->numero,
            'fecha_emision' => optional($c->fecha_emision)?->format('d/m/Y'),
            'cliente' => optional($c->cliente)->nombre,
            'tractivo' => optional($c->tractivo)->placa,
            'origen' => optional($c->hojaRuta)->origen,
            'destino' => optional($c->hojaRuta)->destino,
        ])->all();

        return $this->reporteTablaPdf('Cartas de Porte a Facturar', $columnas, $filas, ['periodo' => $this->periodoTexto($filtros)]);
    }

    // 16 — REGISTRO FACTURACION MES
    public function registroFacturacionMes(array $filtros): \Illuminate\Http\Response
    {
        [$desde, $hasta] = $this->rangoFiltros($filtros);
        $ids = $this->entidadIds();

        $facturas = Factura::with('cliente:id,nombre')
            ->whereIn('id_entidad', $ids)
            ->whereBetween('fecha_emision', [$desde, $hasta])
            ->orderBy('fecha_emision')->orderBy('numero')
            ->get();

        $columnas = [
            ['key' => 'numero', 'label' => 'No.', 'num' => false],
            ['key' => 'fecha_emision', 'label' => 'Fecha', 'num' => false],
            ['key' => 'cliente', 'label' => 'Cliente', 'num' => false],
            ['key' => 'flete_mt', 'label' => 'Flete MT', 'num' => true],
            ['key' => 'otros_mt', 'label' => 'Otros MT', 'num' => true],
            ['key' => 'ingreso_mt', 'label' => 'Ingreso MT', 'num' => true],
            ['key' => 'flete_mlc', 'label' => 'Flete MLC', 'num' => true],
        ];

        $filas = $facturas->map(fn ($f) => [
            'numero' => $f->numero,
            'fecha_emision' => optional($f->fecha_emision)?->format('d/m/Y'),
            'cliente' => optional($f->cliente)->nombre,
            'flete_mt' => number_format((float) ($f->flete_mt ?? 0), 2),
            'otros_mt' => number_format((float) ($f->otros_mt ?? 0), 2),
            'ingreso_mt' => number_format((float) ($f->ingreso_mt ?? 0), 2),
            'flete_mlc' => number_format((float) ($f->flete_mlc ?? 0), 2),
        ])->all();

        return $this->reporteTablaPdf('Registro de Facturación del Mes', $columnas, $filas, ['periodo' => $this->periodoTexto($filtros)]);
    }

    // 17 — RESUMEN MENSUAL FACTURACION CLIENTES
    public function resumenMensualClientes(array $filtros): \Illuminate\Http\Response
    {
        return $this->resumenPorCliente($filtros, false, 'Resumen Mensual Facturación por Clientes');
    }

    // 4069 — RESUMEN MENSUAL FACTURACION CLIENTES OTRAS VENTAS
    public function resumenMensualClientesOtrasVentas(array $filtros): \Illuminate\Http\Response
    {
        return $this->resumenPorCliente($filtros, true, 'Resumen Mensual Facturación por Clientes (Otras Ventas)');
    }

    // 18 — RESUMEN MENSUAL FACTURACION ORGANISMOS
    public function resumenMensualOrganismos(array $filtros): \Illuminate\Http\Response
    {
        return $this->resumenPorCliente($filtros, false, 'Resumen Mensual Facturación por Organismos');
    }

    private function resumenPorCliente(array $filtros, bool $otrasVentas, string $titulo): \Illuminate\Http\Response
    {
        [$desde, $hasta] = $this->rangoFiltros($filtros);
        $ids = $this->entidadIds();

        $query = Factura::whereIn('id_entidad', $ids)
            ->whereBetween('fecha_emision', [$desde, $hasta])
            ->when($otrasVentas, fn ($q) => $q->where('oventas', 1))
            ->when(! $otrasVentas, fn ($q) => $q->where(fn ($q2) => $q2->where('oventas', 0)->orWhereNull('oventas')));

        $datos = $query->selectRaw('id_cliente, COUNT(*) as facturas, SUM(ingreso_mt) as total_mt, SUM(flete_mlc) as total_mlc')
            ->groupBy('id_cliente')
            ->orderByDesc('total_mt')
            ->get();

        $clientes = Cliente::whereIn('id', $datos->pluck('id_cliente'))->pluck('nombre', 'id');

        $columnas = [
            ['key' => 'cliente', 'label' => 'Cliente / Organismo', 'num' => false],
            ['key' => 'facturas', 'label' => 'Facturas', 'num' => true],
            ['key' => 'total_mt', 'label' => 'Total MT', 'num' => true],
            ['key' => 'total_mlc', 'label' => 'Total MLC', 'num' => true],
        ];

        $filas = $datos->map(fn ($d) => [
            'cliente' => $clientes[$d->id_cliente] ?? '—',
            'facturas' => $d->facturas,
            'total_mt' => number_format((float) ($d->total_mt ?? 0), 2),
            'total_mlc' => number_format((float) ($d->total_mlc ?? 0), 2),
        ])->all();

        return $this->reporteTablaPdf($titulo, $columnas, $filas, ['periodo' => $this->periodoTexto($filtros)]);
    }

    // 59 — IMPRESION DE COMPROBANTES (prefacturas)
    public function impresionComprobantes(array $filtros): \Illuminate\Http\Response
    {
        [$desde, $hasta] = $this->rangoFiltros($filtros);
        $ids = $this->entidadIds();

        $prefacturas = Prefactura::with('cliente:id,nombre', 'entidad:id,nombre')
            ->whereHas('entidad', fn ($q) => $q->whereIn('id', $ids))
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha')->orderBy('id')
            ->get();

        $columnas = [
            ['key' => 'id', 'label' => 'No.', 'num' => false],
            ['key' => 'fecha', 'label' => 'Fecha', 'num' => false],
            ['key' => 'cliente', 'label' => 'Cliente', 'num' => false],
            ['key' => 'entidad', 'label' => 'Entidad', 'num' => false],
        ];

        $filas = $prefacturas->map(fn ($p) => [
            'id' => $p->id,
            'fecha' => optional($p->fecha)?->format('d/m/Y'),
            'cliente' => optional($p->cliente)->nombre,
            'entidad' => optional($p->entidad)->nombre,
        ])->all();

        return $this->reporteTablaPdf('Impresión de Comprobantes (Prefacturas)', $columnas, $filas, ['periodo' => $this->periodoTexto($filtros)]);
    }

    // 1003 — FACTURAS FIRMADAS POR CLIENTES
    public function facturasFirmadasClientes(array $filtros): \Illuminate\Http\Response
    {
        [$desde, $hasta] = $this->rangoFiltros($filtros);
        $ids = $this->entidadIds();

        $facturas = Factura::with('cliente:id,nombre')
            ->whereIn('id_entidad', $ids)
            ->whereNotNull('fecha_firma')
            ->whereBetween('fecha_emision', [$desde, $hasta])
            ->orderBy('fecha_firma')->orderBy('numero')
            ->get();

        $columnas = [
            ['key' => 'numero', 'label' => 'No.', 'num' => false],
            ['key' => 'fecha_emision', 'label' => 'Emisión', 'num' => false],
            ['key' => 'fecha_firma', 'label' => 'Firma', 'num' => false],
            ['key' => 'cliente', 'label' => 'Cliente', 'num' => false],
            ['key' => 'ingreso_mt', 'label' => 'Ingreso MT', 'num' => true],
        ];

        $filas = $facturas->map(fn ($f) => [
            'numero' => $f->numero,
            'fecha_emision' => optional($f->fecha_emision)?->format('d/m/Y'),
            'fecha_firma' => optional($f->fecha_firma)?->format('d/m/Y'),
            'cliente' => optional($f->cliente)->nombre,
            'ingreso_mt' => number_format((float) ($f->ingreso_mt ?? 0), 2),
        ])->all();

        return $this->reporteTablaPdf('Facturas Firmadas por Clientes', $columnas, $filas, ['periodo' => $this->periodoTexto($filtros)]);
    }

    // 1005 — FACTURAS PENDIENTES DE FIRMA POR CLIENTES
    public function facturasPendientesFirma(array $filtros): \Illuminate\Http\Response
    {
        [$desde, $hasta] = $this->rangoFiltros($filtros);
        $ids = $this->entidadIds();

        $facturas = Factura::with('cliente:id,nombre')
            ->whereIn('id_entidad', $ids)
            ->whereNull('fecha_firma')
            ->whereBetween('fecha_emision', [$desde, $hasta])
            ->orderBy('fecha_emision')->orderBy('numero')
            ->get();

        $columnas = [
            ['key' => 'numero', 'label' => 'No.', 'num' => false],
            ['key' => 'fecha_emision', 'label' => 'Emisión', 'num' => false],
            ['key' => 'cliente', 'label' => 'Cliente', 'num' => false],
            ['key' => 'ingreso_mt', 'label' => 'Ingreso MT', 'num' => true],
        ];

        $filas = $facturas->map(fn ($f) => [
            'numero' => $f->numero,
            'fecha_emision' => optional($f->fecha_emision)?->format('d/m/Y'),
            'cliente' => optional($f->cliente)->nombre,
            'ingreso_mt' => number_format((float) ($f->ingreso_mt ?? 0), 2),
        ])->all();

        return $this->reporteTablaPdf('Facturas Pendientes de Firma por Clientes', $columnas, $filas, ['periodo' => $this->periodoTexto($filtros)]);
    }

    // 1007 — LISTADO DE CONCILIACIONES
    public function listadoConciliaciones(array $filtros): \Illuminate\Http\Response
    {
        [$desde, $hasta] = $this->rangoFiltros($filtros);
        $ids = $this->entidadIds();

        $conciliaciones = Conciliacione::with('entidad:id,nombre')
            ->whereIn('id_entidad', $ids)
            ->whereBetween('fecha_conciliacion', [$desde, $hasta])
            ->orderBy('fecha_conciliacion')->orderBy('numero')
            ->get();

        $columnas = [
            ['key' => 'numero', 'label' => 'No.', 'num' => false],
            ['key' => 'fecha_conciliacion', 'label' => 'Fecha', 'num' => false],
            ['key' => 'entidad', 'label' => 'Entidad', 'num' => false],
            ['key' => 'monto', 'label' => 'Monto', 'num' => true],
            ['key' => 'tipo', 'label' => 'Tipo', 'num' => false],
            ['key' => 'estado', 'label' => 'Estado', 'num' => false],
        ];

        $filas = $conciliaciones->map(fn ($c) => [
            'numero' => $c->numero,
            'fecha_conciliacion' => optional($c->fecha_conciliacion)?->format('d/m/Y'),
            'entidad' => optional($c->entidad)->nombre,
            'monto' => number_format((float) ($c->monto ?? 0), 2),
            'tipo' => $c->tipo,
            'estado' => $c->estado,
        ])->all();

        return $this->reporteTablaPdf('Listado de Conciliaciones', $columnas, $filas, ['periodo' => $this->periodoTexto($filtros)]);
    }
}
