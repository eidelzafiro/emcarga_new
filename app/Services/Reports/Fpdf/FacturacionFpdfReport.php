<?php

namespace App\Services\Reports\Fpdf;

use Illuminate\Support\Facades\DB;

/**
 * Reportes del módulo FACTURACIÓN replicando el layout exacto del legacy
 * `Reportes2.php` (facturas, cartas a facturar, registros, resúmenes,
 * comprobantes, firmas y conciliaciones) con FPDF.
 */
class FacturacionFpdfReport extends DocumentosFpdfBase
{
    /** @var int[] */
    protected array $entidadIds;

    public function __construct(
        string $orientation,
        string $paper,
        ?object $entidad,
        bool $multiEntidad,
        string $fechaOperaciones,
        array $entidadIds = [],
    ) {
        parent::__construct($orientation, $paper, $entidad, $multiEntidad, $fechaOperaciones);
        $this->entidadIds = $entidadIds ?: [23];
    }

    // ==================================================================
    // Helpers
    // ==================================================================

    /** Devuelve [anio, mes] desde 'MM', 'YYYY-MM' o vacío (sesión). */
    protected function anioMes(?string $mes): array
    {
        $fechaOps = $this->fechaOperaciones ?: now()->toDateString();
        $mes = trim((string) $mes);

        if ($mes === '' || strtoupper($mes) === 'TODOS' || $mes === '00') {
            return [(int) substr($fechaOps, 0, 4), (int) substr($fechaOps, 5, 2)];
        }
        if (strlen($mes) === 2) {
            return [(int) substr($fechaOps, 0, 4), (int) $mes];
        }
        if (strlen($mes) >= 7) {
            return [(int) substr($mes, 0, 4), (int) substr($mes, 5, 2)];
        }

        return [(int) substr($fechaOps, 0, 4), (int) substr($fechaOps, 5, 2)];
    }

    /** Normaliza una fecha de BD a 'YYYY-MM-DD'. */
    protected function d($valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        return substr((string) $valor, 0, 10);
    }

    /** Página de "sin datos" con el encabezado legacy. */
    protected function noDatos(string $titulo, string $fecha = '', float $posX = 50, float $posY = 5): void
    {
        $this->inicio($titulo, $fecha, $posX, $posY);
        $this->SetFont('Arial', 'B', 25);
        $this->SetFillColor(999, 999, 999);
        $this->SetXY(10, 65);
        $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');
    }

    // ==================================================================
    // 14 — LISTADO DE CARTAS DE PORTE A FACTURAR
    // ==================================================================

    public function pdfCartasPorteAFacturar(?string $fecha): \Illuminate\Http\Response
    {
        [$anio, $mes] = $this->anioMes(null);

        $campos1 = [
            ['titulo' => 'NRO CP', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'EQUIPO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'PRODUCTO', 'ancho' => 65, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'INGRESOS', 'ancho' => 90, 'direccion' => 'C'],
        ];
        $campos = [
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 65, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'FLETE', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'DEMORA', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'OTROS', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'IMPORTE', 'ancho' => 25, 'direccion' => 'C'],
        ];

        $q = DB::table('aforos as a')
            ->join('cartas_porte as cp', 'cp.id', '=', 'a.id_carta_porte')
            ->join('hojas_ruta as hr', 'hr.id', '=', 'cp.id_hoja_ruta')
            ->join('solicitudes_servicio as s', 's.id', '=', 'cp.id_solicitud')
            ->join('clientes as c', 'c.id', '=', 's.id_cliente')
            ->join('tractivos as t', 't.id', '=', 'hr.id_tractivo')
            ->leftJoin('tipos_cargas as tc', 'tc.id', '=', 's.id_tipo_carga')
            ->leftJoin('productos as p', 'p.id', '=', 's.id_producto')
            ->whereIn('t.id_entidad', $this->entidadIds)
            ->whereNull('a.id_factura')
            ->where('a.ingreso_mt', '>', 0)
            ->whereYear('a.fecha_parte', $anio)
            ->whereMonth('a.fecha_parte', $mes)
            ->whereMonth('hr.fecha_cierre', $mes);

        if ($fecha) {
            $q->where('a.fecha_parte', '<=', $fecha);
        }

        $data = $q->orderBy('c.nombre')->orderBy('cp.numero')
            ->select(
                'c.codigo as codcliente',
                'c.nombre as nombcliente',
                't.codigo as codtractivo',
                'cp.numero as nrocp',
                'tc.nombre as tipocargas',
                'p.nombre as nombproducto',
                'a.flete_mt as fletemtt',
                'a.flete_demora as fletedemt',
                'a.otros_mt as otrosmtt',
                'a.ingreso_mt as ingresomt',
            )->get();

        $titulo = 'LISTADO PARA FACTURACION DE TERCEROS';
        $this->SetAutoPageBreak(false);

        if ($data->isEmpty()) {
            $this->noDatos($titulo);

            return $this->salida($titulo.'.pdf');
        }

        $this->inicio($titulo);
        $this->titulos(6, 10, 35, $campos, $campos1);

        $posY = 47;
        $nroCliente = 1;

        // Encabezado del primer cliente
        $clienteActual = $data[0]->codcliente;
        $this->SetFont('Arial', 'B', 12);
        $this->SetXY(10, $posY);
        $this->SetFillColor(999, 999, 999);
        $this->Cell(25, 8, $this->txt((string) $data[0]->codcliente), 'LTB', 0, 'C', 1);
        $this->Cell(170, 8, $this->txt((string) $data[0]->nombcliente), 'TBR', 0, 'L', 1);
        $posY += 8;

        $sumaCliente = ['flete' => 0.0, 'demora' => 0.0, 'otros' => 0.0, 'ingreso' => 0.0];
        $total = ['flete' => 0.0, 'demora' => 0.0, 'otros' => 0.0, 'ingreso' => 0.0];
        $cp = 0;
        $cpCliente = 0;

        $imprimirFila = function ($arr) use (&$posY, &$sumaCliente, &$cp, &$cpCliente, &$total) {
            $this->SetFont('Arial', '', 11);
            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->Cell(20, 6, $this->txt((string) $arr->nrocp), 1, 0, 'C', 1);
            $this->Cell(20, 6, $this->txt((string) $arr->codtractivo), 1, 0, 'C', 1);
            $this->SetFont('Arial', '', 9);
            $this->Cell(65, 6, $this->txt((string) $arr->nombproducto), 1, 0, 'L', 1);
            $this->SetFont('Arial', '', 12);
            $this->Cell(25, 6, $this->cambiarVariable($arr->fletemtt, 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->cambiarVariable($arr->fletedemt, 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->cambiarVariable($arr->otrosmtt, 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->cambiarVariable($arr->ingresomt, 2), 1, 0, 'R', 1);

            $sumaCliente['flete'] += (float) $arr->fletemtt;
            $sumaCliente['demora'] += (float) $arr->fletedemt;
            $sumaCliente['otros'] += (float) $arr->otrosmtt;
            $sumaCliente['ingreso'] += (float) $arr->ingresomt;

            $total['flete'] += (float) $arr->fletemtt;
            $total['demora'] += (float) $arr->fletedemt;
            $total['otros'] += (float) $arr->otrosmtt;
            $total['ingreso'] += (float) $arr->ingresomt;

            $posY += 6;
            $cp++;
            $cpCliente++;
        };

        $imprimirTotalCliente = function () use (&$posY, &$sumaCliente, &$cpCliente) {
            $this->SetFont('Arial', 'B', 11);
            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->Cell(105, 6, 'TOTAL DEL CLIENTE ('.$cpCliente.')', 1, 0, 'L', 1);
            $this->Cell(25, 6, $this->cambiarVariable($sumaCliente['flete'], 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->cambiarVariable($sumaCliente['demora'], 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->cambiarVariable($sumaCliente['otros'], 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->cambiarVariable($sumaCliente['ingreso'], 2), 1, 0, 'R', 1);
            $posY += 6;
        };

        $nuevoCliente = function ($arr) use (&$posY, &$clienteActual, &$sumaCliente, &$cpCliente, $imprimirTotalCliente) {
            $imprimirTotalCliente();
            if ($posY >= 240) {
                $this->inicio('LISTADO PARA FACTURACION DE TERCEROS');
                $this->titulos(6, 10, 35, [
                    ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
                    ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
                    ['titulo' => '', 'ancho' => 65, 'direccion' => 'C', 'bordes' => 'LRB'],
                    ['titulo' => 'FLETE', 'ancho' => 25, 'direccion' => 'C'],
                    ['titulo' => 'DEMORA', 'ancho' => 20, 'direccion' => 'C'],
                    ['titulo' => 'OTROS', 'ancho' => 20, 'direccion' => 'C'],
                    ['titulo' => 'IMPORTE', 'ancho' => 25, 'direccion' => 'C'],
                ], [
                    ['titulo' => 'NRO CP', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
                    ['titulo' => 'EQUIPO', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
                    ['titulo' => 'PRODUCTO', 'ancho' => 65, 'direccion' => 'C', 'bordes' => 'LTR'],
                    ['titulo' => 'INGRESOS', 'ancho' => 90, 'direccion' => 'C'],
                ]);
                $posY = 47;
            }
            $this->SetFont('Arial', 'B', 12);
            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->Cell(25, 8, $this->txt((string) $arr->codcliente), 'LTB', 0, 'C', 1);
            $this->Cell(170, 8, $this->txt((string) $arr->nombcliente), 'TBR', 0, 'L', 1);
            $posY += 8;
            $clienteActual = $arr->codcliente;
            $sumaCliente = ['flete' => 0.0, 'demora' => 0.0, 'otros' => 0.0, 'ingreso' => 0.0];
            $cpCliente = 0;
        };

        foreach ($data as $arr) {
            if ($clienteActual !== $arr->codcliente) {
                $nuevoCliente($arr);
            }
            $imprimirFila($arr);
        }

        $imprimirTotalCliente();

        $this->SetFont('Arial', 'B', 11);
        $this->SetXY(10, $posY);
        $this->SetFillColor(999, 999, 999);
        $this->Cell(105, 10, 'TOTAL GENERAL ('.$cp.')', 1, 0, 'L', 1);
        $this->Cell(25, 10, $this->cambiarVariable($total['flete'], 2), 1, 0, 'R', 1);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(20, 10, $this->cambiarVariable($total['demora'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->cambiarVariable($total['otros'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->cambiarVariable($total['ingreso'], 2), 1, 0, 'R', 1);

        return $this->salida($titulo.'.pdf');
    }

    // ==================================================================
    // 16 — REGISTRO DE FACTURACIÓN DEL MES
    // ==================================================================

    public function pdfRegistroFacturacionMes(?string $mes): \Illuminate\Http\Response
    {
        [$anio, $mesNum] = $this->anioMes($mes);

        $campos = [
            ['titulo' => 'FACTURA', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'COD', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'NOMBRE DEL CLIENTE', 'ancho' => 100, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'IMPORTES', 'ancho' => 140, 'direccion' => 'C'],
        ];
        $campos1 = [
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 15, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 100, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'FLETE', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'DEMORA', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'OTROS', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'ALMACENAJE', 'ancho' => 20, 'direccion' => 'C', 'letra' => '7'],
            ['titulo' => 'FLETE CL', 'ancho' => 20, 'direccion' => 'C', 'letra' => '11'],
            ['titulo' => 'TOTAL', 'ancho' => 25, 'direccion' => 'C'],
        ];

        $q = DB::table('facturas as f')
            ->join('clientes as c', 'c.id', '=', 'f.id_cliente')
            ->whereIn('f.id_entidad', $this->entidadIds)
            ->whereYear('f.fecha_emision', $anio)
            ->whereMonth('f.fecha_emision', $mesNum);

        $data = $q->orderBy('f.id')
            ->select('f.*', 'c.codigo as codcliente', 'c.nombre as nombcliente')
            ->get();

        $titulo = 'REGISTRO DE FACTURAS MES DE OPERACIONES';
        $this->SetAutoPageBreak(false);

        if ($data->isEmpty()) {
            $this->noDatos($titulo, str_pad((string) $mesNum, 2, '0', STR_PAD_LEFT), 50, 5);

            return $this->salida($titulo.'.pdf');
        }

        // Almacenaje por factura (para restarlo del flete).
        $ids = $data->pluck('id')->all();
        $almacenajes = DB::table('aforos')
            ->whereIn('id_factura', $ids)
            ->where('almacenaje_flete', '>', 0)
            ->selectRaw('id_factura, SUM(almacenaje_flete) almflete')
            ->groupBy('id_factura')
            ->pluck('almflete', 'id_factura')
            ->all();

        $mesTitulo = str_pad((string) $mesNum, 2, '0', STR_PAD_LEFT);
        $this->inicio($titulo, $mesTitulo, 50, 5);
        $this->titulos(10, 10, 35, $campos1, $campos);

        $posY = 55;
        $max = 18;
        $i = 1;
        $tot = ['flete' => 0.0, 'demora' => 0.0, 'otros' => 0.0, 'alm' => 0.0, 'mlc' => 0.0, 'ingreso' => 0.0];

        foreach ($data as $arr) {
            $alm = (float) ($almacenajes[$arr->id] ?? 0);
            $flete = round((float) $arr->flete_mt - $alm, 2);

            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);

            if ($arr->cancelada == 1 || $arr->refacturada == 1) {
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(20, 8, $this->txt((string) $arr->numero), 1, 0, 'C', 1);
                $this->Cell(15, 8, $this->txt((string) $arr->codcliente), 1, 0, 'C', 1);
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(100, 8, $this->txt(trim((string) $arr->nombcliente)), 1, 0, 'L', 1);
                $this->Cell(140, 8, $arr->cancelada == 1 ? 'FACTURA CANCELADA' : 'FACTURA REFACTURADA', 1, 0, 'C', 1);
                $posY += 8;
                $i++;
            } else {
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(20, 8, $this->txt((string) $arr->numero), 1, 0, 'C', 1);
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(15, 8, $this->txt((string) $arr->codcliente), 1, 0, 'C', 1);
                $this->SetFont('Arial', 'B', 11);
                $this->Cell(100, 8, $this->txt(trim((string) $arr->nombcliente)), 1, 0, 'L', 1);
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(25, 8, $this->cambiarVariable($flete, 2), 1, 0, 'R', 1);
                $this->Cell(25, 8, $this->cambiarVariable($arr->flete_demora, 2), 1, 0, 'R', 1);
                $this->Cell(25, 8, $this->cambiarVariable($arr->otros_mt, 2), 1, 0, 'R', 1);
                $this->SetFont('Arial', 'B', 10);
                $this->Cell(20, 8, $this->cambiarVariable($alm, 2), 1, 0, 'R', 1);
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(20, 8, $this->cambiarVariable($arr->flete_mlc, 2), 1, 0, 'R', 1);
                $this->Cell(25, 8, $this->cambiarVariable($arr->ingreso_mt, 2), 1, 0, 'R', 1);

                $tot['flete'] += $flete;
                $tot['demora'] += (float) $arr->flete_demora;
                $tot['otros'] += (float) $arr->otros_mt;
                $tot['alm'] += $alm;
                $tot['mlc'] += (float) $arr->flete_mlc;
                $tot['ingreso'] += (float) $arr->ingreso_mt;
                $posY += 8;
                $i++;
            }

            if ($i == $max) {
                $posY = 55;
                $i = 1;
                $this->inicio($titulo, $mesTitulo, 50, 5);
                $this->titulos(10, 10, 35, $campos1, $campos);
            }
        }

        $this->SetFont('Arial', 'B', 11);
        $this->SetXY(10, $posY);
        $this->SetFillColor(999, 999, 999);
        $this->Cell(135, 10, 'TOTALES FACTURADO', 1, 0, 'L', 1);
        $this->Cell(25, 10, $this->cambiarVariable($tot['flete'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->cambiarVariable($tot['demora'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->cambiarVariable($tot['otros'], 2), 1, 0, 'R', 1);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(20, 10, $this->cambiarVariable($tot['alm'], 2), 1, 0, 'R', 1);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(20, 10, $this->cambiarVariable($tot['mlc'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->cambiarVariable($tot['ingreso'], 2), 1, 0, 'R', 1);

        return $this->salida($titulo.'.pdf');
    }

    // ==================================================================
    // 17 / 18 / 4069 — RESUMEN MENSUAL
    // ==================================================================

    /**
     * @param string $campo 'clientes' | 'abreviatura' | 'clientesov'
     */
    public function pdfResumenMensual(?string $mes, string $campo): \Illuminate\Http\Response
    {
        [$anio, $mesNum] = $this->anioMes($mes);

        $porOrganismo = $campo === 'abreviatura';
        $otrasVentas = $campo === 'clientesov';

        if ($porOrganismo) {
            $vtitulo = 'MENSUAL POR ORGANISMOS ';
            $vcampo = 'ORGANISMO';
        } else {
            $vtitulo = 'MENSUAL POR CLIENTES ';
            $vcampo = 'NOMBRE DEL CLIENTE';
        }
        $acampo = 90;

        $q = DB::table('facturas as f')
            ->join('clientes as c', 'c.id', '=', 'f.id_cliente')
            ->whereIn('f.id_entidad', $this->entidadIds)
            ->where('f.refacturada', 0)
            ->where('f.cancelada', 0)
            ->whereYear('f.fecha_emision', $anio)
            ->whereMonth('f.fecha_emision', $mesNum)
            ->where('f.oventas', $otrasVentas ? 1 : 0);

        if ($porOrganismo) {
            $q->leftJoin('catalogo_items as o', function ($j) {
                $j->on('o.id', '=', 'c.idorganismos')->where('o.tipo', '=', 'organismos');
            })
                ->groupBy('o.id')
                ->orderBy('o.nombre')
                ->selectRaw("COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(o.extra,'$.abreviatura')),''), o.nombre, 'SIN ORGANISMO') as nombre, MIN(c.id) as idcliente,
                    COUNT(f.numero) as factura, SUM(f.flete_mt) as fletemtt, SUM(f.flete_mlc) as fletemlc,
                    SUM(f.flete_demora) as fletedemt, SUM(f.otros_mt) as otrosmtt, SUM(f.ingreso_mt) as ingresomt");
        } else {
            $q->groupBy('c.id')
                ->orderBy('c.nombre')
                ->selectRaw('c.nombre as nombre, c.id as idcliente,
                    COUNT(f.numero) as factura, SUM(f.flete_mt) as fletemtt, SUM(f.flete_mlc) as fletemlc,
                    SUM(f.flete_demora) as fletedemt, SUM(f.otros_mt) as otrosmtt, SUM(f.ingreso_mt) as ingresomt');
        }

        $data = $q->get();

        $titulo = 'RESUMEN DE FACTURACION '.$vtitulo;
        $this->SetAutoPageBreak(false);

        $campos1 = [
            ['titulo' => $vcampo, 'ancho' => $acampo, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'CANT', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'IMPORTES', 'ancho' => 155, 'direccion' => 'C'],
        ];
        $campos2 = [
            ['titulo' => '', 'ancho' => $acampo, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'FLETE', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'DEMORA', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'OTROS', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'ALMACENAJE', 'ancho' => 25, 'direccion' => 'C', 'letra' => '8'],
            ['titulo' => 'FLETE CL', 'ancho' => 20, 'direccion' => 'C', 'letra' => '11'],
            ['titulo' => 'IMPORTE', 'ancho' => 25, 'direccion' => 'C'],
        ];

        if ($data->isEmpty()) {
            $this->noDatos($titulo, str_pad((string) $mesNum, 2, '0', STR_PAD_LEFT), 50, 5);

            return $this->salida($titulo.'.pdf');
        }

        // Almacenaje por cliente en el mes.
        $almacenajes = DB::table('aforos as a')
            ->join('cartas_porte as cp', 'cp.id', '=', 'a.id_carta_porte')
            ->join('solicitudes_servicio as s', 's.id', '=', 'cp.id_solicitud')
            ->where('a.almacenaje_flete', '>', 0)
            ->whereYear('a.fecha_parte', $anio)
            ->whereMonth('a.fecha_parte', $mesNum)
            ->selectRaw('s.id_cliente, SUM(a.almacenaje_flete) almflete')
            ->groupBy('s.id_cliente')
            ->pluck('almflete', 'id_cliente')
            ->all();

        $mesTitulo = str_pad((string) $mesNum, 2, '0', STR_PAD_LEFT);
        $this->inicio($titulo, $mesTitulo, 50, 5);
        $this->titulos(6, 10, 30, $campos2, $campos1);

        $posY = 42;
        $max = 25;
        $i = 1;
        $tot = ['factura' => 0, 'flete' => 0.0, 'demora' => 0.0, 'otros' => 0.0, 'alm' => 0.0, 'mlc' => 0.0, 'ingreso' => 0.0];

        foreach ($data as $arr) {
            $alm = (float) ($almacenajes[$arr->idcliente] ?? 0);
            $flete = round((float) $arr->fletemtt - $alm, 2);

            $this->SetFont('Arial', 'B', 12);
            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->Cell($acampo, 6, $this->txt(trim((string) $arr->nombre)), 1, 0, 'L', 1);
            $this->Cell(20, 6, $this->cambiarVariable($arr->factura, 0), 1, 0, 'C', 1);
            $this->Cell(30, 6, $this->cambiarVariable($flete, 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->cambiarVariable($arr->fletedemt, 2), 1, 0, 'R', 1);
            $this->Cell(30, 6, $this->cambiarVariable($arr->otrosmtt, 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->cambiarVariable($alm, 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->cambiarVariable($arr->fletemlc, 2), 1, 0, 'R', 1);
            $this->Cell(25, 6, $this->cambiarVariable($arr->ingresomt, 2), 1, 0, 'R', 1);

            $tot['factura'] += (int) $arr->factura;
            $tot['flete'] += $flete;
            $tot['demora'] += (float) $arr->fletedemt;
            $tot['otros'] += (float) $arr->otrosmtt;
            $tot['alm'] += $alm;
            $tot['mlc'] += (float) $arr->fletemlc;
            $tot['ingreso'] += (float) $arr->ingresomt;

            $posY += 6;
            $i++;
            if ($i == $max) {
                $this->inicio($titulo, $mesTitulo, 50, 5);
                $this->titulos(6, 10, 30, $campos2, $campos1);
                $posY = 55;
                $i = 1;
            }
        }

        $this->SetFont('Arial', 'B', 12);
        $this->SetXY(10, $posY);
        $this->SetFillColor(999, 999, 999);
        $this->Cell($acampo, 10, 'TOTALES', 1, 0, 'C', 1);
        $this->Cell(20, 10, $this->cambiarVariable($tot['factura'], 0), 1, 0, 'C', 1);
        $this->Cell(30, 10, $this->cambiarVariable($tot['flete'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->cambiarVariable($tot['demora'], 2), 1, 0, 'R', 1);
        $this->Cell(30, 10, $this->cambiarVariable($tot['otros'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->cambiarVariable($tot['alm'], 2), 1, 0, 'R', 1);
        $this->Cell(20, 10, $this->cambiarVariable($tot['mlc'], 2), 1, 0, 'R', 1);
        $this->Cell(25, 10, $this->cambiarVariable($tot['ingreso'], 2), 1, 0, 'R', 1);

        return $this->salida($titulo.'.pdf');
    }

    // ==================================================================
    // 1003 / 1005 — FACTURAS FIRMADAS / PENDIENTES POR CLIENTES
    // ==================================================================

    /**
     * @param int $tipo 1 = firmadas, 3 = pendientes de firma
     */
    public function pdfFacturasFirmadas(?string $mes, int $tipo): \Illuminate\Http\Response
    {
        [$anio, $mesNum] = $this->anioMes($mes);

        $titulo = $tipo === 1
            ? 'LISTADO FACTURAS FIRMADAS POR CLIENTES'
            : 'LISTADO FACTURAS PENDIENTES DE FIRMAS POR CLIENTES';

        $q = DB::table('facturas as f')
            ->join('clientes as c', 'c.id', '=', 'f.id_cliente')
            ->whereIn('f.id_entidad', $this->entidadIds)
            ->where('f.cancelada', 0)
            ->where('f.numero', '!=', ' ')
            ->whereYear('f.fecha_emision', $anio)
            ->whereMonth('f.fecha_emision', $mesNum);

        if ($tipo === 1) {
            $q->whereNotNull('f.fecha_firma')
                ->whereYear('f.fecha_firma', $anio)
                ->whereMonth('f.fecha_firma', $mesNum);
        } else {
            $q->whereNull('f.fecha_firma');
        }

        $data = $q->orderBy('c.nombre')->orderBy('f.numero')
            ->select('f.*', 'c.nombre as nombcliente')
            ->get();

        $this->SetAutoPageBreak(false);

        $campos = [
            ['titulo' => 'NOMBRE DEL CLIENTE', 'ancho' => 80, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'FACTURA', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'IMPORTE ', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'EMITIDA', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR', 'letra' => '8'],
            ['titulo' => 'FIRMADA', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'CONCILIADA', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LTR'],
            ['titulo' => 'IMPORTES', 'ancho' => 75, 'direccion' => 'C', 'letra' => '12'],
        ];
        $campos1 = [
            ['titulo' => '', 'ancho' => 80, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 25, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'TOTAL', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => '', 'ancho' => 20, 'direccion' => 'C', 'bordes' => 'LRB'],
            ['titulo' => 'COBRADA', 'ancho' => 20, 'direccion' => 'C', 'letra' => '9'],
            ['titulo' => 'D-PAGO', 'ancho' => 25, 'direccion' => 'C'],
            ['titulo' => 'DIAS', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'MORA', 'ancho' => 15, 'direccion' => 'C'],
        ];

        if ($data->isEmpty()) {
            $this->noDatos($titulo, str_pad((string) $mesNum, 2, '0', STR_PAD_LEFT), 50, 5);

            return $this->salida($titulo.'.pdf');
        }

        $mesTitulo = str_pad((string) $mesNum, 2, '0', STR_PAD_LEFT);
        $this->inicio($titulo, $mesTitulo, 50, 5);
        $this->titulos(6, 10, 30, $campos1, $campos);

        $posY = 42;
        $max = 21;
        $i = 1;
        $total = 0.0;

        foreach ($data as $arr) {
            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->SetFont('Arial', 'B', 9);
            $this->Cell(80, 8, $this->txt(substr((string) $arr->nombcliente, 0, 50)), 1, 0, 'L');
            $this->SetFont('Arial', 'BU', 11);
            $this->Cell(25, 8, $this->txt((string) $arr->numero), 1, 0, 'C', 1);
            $this->SetFont('Arial', 'B', 9);
            $this->Cell(20, 8, $this->cambiarVariable($arr->ingreso_mt, 2), 1, 0, 'C', 1);
            $this->Cell(20, 8, $this->d($arr->fecha_emision), 1, 0, 'C', 1);
            $this->Cell(20, 8, $this->d($arr->fecha_firma) ?: ' PENDIENTE ', 1, 0, 'C', 1);
            $this->Cell(20, 8, $this->d($arr->fecha_conciliacion), 1, 0, 'C', 1);
            $this->Cell(20, 8, $this->d($arr->fecha_cobro_mn), 1, 0, 'C', 1);
            $this->Cell(25, 8, $this->d($arr->doc_pago_mn), 1, 0, 'C', 1);
            $this->Cell(15, 8, '', 1, 0, 'C', 1);
            $this->Cell(15, 8, '', 1, 0, 'C', 1);

            $total += (float) $arr->ingreso_mt;
            $posY += 8;
            $i++;
            if ($i == $max) {
                $this->inicio($titulo, $mesTitulo, 50, 5);
                $this->titulos(6, 10, 30, $campos1, $campos);
                $posY = 42;
                $i = 1;
            }
        }

        $this->SetXY(10, $posY);
        $this->SetFillColor(999, 999, 999);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(105, 8, 'TOTAL', 1, 0, 'L');
        $this->Cell(20, 8, $this->cambiarVariable($total, 2), 1, 0, 'C', 1);
        $this->Cell(120, 8, '', 1, 0, 'C', 1);
        $this->Cell(15, 8, '', 1, 0, 'C', 1);

        return $this->salida($titulo.'.pdf');
    }

    // ==================================================================
    // 1007 — LISTADO DE CONCILIACIONES DEL MES
    // ==================================================================

    public function pdfListadoConciliaciones(?string $mes): \Illuminate\Http\Response
    {
        [$anio, $mesNum] = $this->anioMes($mes);

        $campos = [
            ['titulo' => 'NOMBRE DEL CLIENTE', 'ancho' => 100, 'direccion' => 'C'],
            ['titulo' => 'EMITIDA', 'ancho' => 30, 'direccion' => 'C'],
            ['titulo' => 'FIRMADA', 'ancho' => 30, 'direccion' => 'C'],
        ];

        $data = DB::table('conciliaciones as co')
            ->leftJoin('facturas as f', 'f.id', '=', 'co.id_factura')
            ->leftJoin('clientes as c', 'c.id', '=', 'f.id_cliente')
            ->whereIn('co.id_entidad', $this->entidadIds)
            ->whereYear('co.fecha_conciliacion', $anio)
            ->whereMonth('co.fecha_conciliacion', $mesNum)
            ->orderBy('co.fecha_conciliacion')
            ->orderBy('c.nombre')
            ->select('co.fecha_conciliacion', 'f.fecha_firma', 'c.nombre as nombcliente')
            ->get();

        $titulo = 'LISTADO DE CONCILIACIONES DEL MES';
        $this->SetAutoPageBreak(false);

        if ($data->isEmpty()) {
            $this->noDatos($titulo, str_pad((string) $mesNum, 2, '0', STR_PAD_LEFT), 50, 5);

            return $this->salida($titulo.'.pdf');
        }

        $mesTitulo = str_pad((string) $mesNum, 2, '0', STR_PAD_LEFT);
        $this->inicio($titulo, $mesTitulo, 50, 5);
        $this->titulos(6, 10, 30, $campos);

        $posY = 36;
        $max = 25;
        $i = 1;

        foreach ($data as $arr) {
            $this->SetFont('Arial', '', 13);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, $posY);
            $this->Cell(100, 6, $this->txt((string) $arr->nombcliente), 1, 0, 'L', 1);
            $this->Cell(30, 6, $this->d($arr->fecha_conciliacion), 1, 0, 'R', 1);
            $this->Cell(30, 6, $this->d($arr->fecha_firma) ?: ' PENDIENTE ', 1, 0, 'R', 1);

            $posY += 6;
            $i++;
            if ($i == $max) {
                $this->inicio($titulo, $mesTitulo, 50, 5);
                $this->titulos(6, 10, 30, $campos);
                $posY = 55;
                $i = 1;
            }
        }

        return $this->salida($titulo.'.pdf');
    }

    // ==================================================================
    // 59 — COMPROBANTE DE TRANSACCIONES (CONTABILIDAD)
    // ==================================================================

    public function pdfImpresionComprobantes(?string $fecha): \Illuminate\Http\Response
    {
        $titulo = 'COMPROBANTE TRANSACCIONES';

        // El legacy lee de un modelo de comprobantes que no tiene tabla destino
        // en el esquema nuevo. Se usa el asiento contable más cercano.
        $data = collect();
        if ($fecha) {
            $asiento = DB::table('contabilidad')
                ->whereDate('fecha_asiento', $fecha)
                ->orderBy('numero_asiento')
                ->first();
            if ($asiento) {
                $data = DB::table('contabilidad_detalle')
                    ->where('id_asiento', $asiento->id)
                    ->orderBy('id')
                    ->get();
                $titulo = 'COMPROBANTE TRANSACCIONES REGISTRO '.$asiento->numero_asiento;
            }
        }

        $this->SetAutoPageBreak(false);

        if ($data->isEmpty()) {
            $this->noDatos($titulo, $fecha);

            return $this->salida($titulo.'.pdf');
        }

        $campos = [
            ['titulo' => 'NRO', 'ancho' => 10, 'direccion' => 'C'],
            ['titulo' => 'CUENTA', 'ancho' => 45, 'direccion' => 'C'],
            ['titulo' => 'NOMBRE DEL CLIENTE', 'ancho' => 65, 'direccion' => 'C'],
            ['titulo' => 'MON', 'ancho' => 15, 'direccion' => 'C'],
            ['titulo' => 'DEBITO', 'ancho' => 20, 'direccion' => 'C'],
            ['titulo' => 'CREDITO', 'ancho' => 20, 'direccion' => 'C'],
        ];

        $this->inicio($titulo, $fecha, 50, 5);
        $this->titulos(6, 10, 31, $campos);
        $this->SetXY(15, 25);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 6, 'CONTABILIZADO '.$this->txt((string) $data[0]->id_asiento), 0, 1, 'L');

        $posY = 37;
        $max = 38;
        $i = 1;
        $debito = 0.0;
        $credito = 0.0;

        foreach ($data as $arr) {
            $this->SetFont('Arial', '', 12);
            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->Cell(10, 6, $this->txt((string) $arr->id), 1, 0, 'C', 1);
            $this->Cell(45, 6, $this->txt((string) $arr->cuenta_contable), 1, 0, 'L', 1);
            $this->SetFont('Arial', '', 9);
            $this->Cell(65, 6, $this->txt((string) $arr->descripcion), 1, 0, 'L', 1);
            $this->SetFont('Arial', '', 12);
            $this->Cell(15, 6, '', 1, 0, 'C', 1);
            $this->Cell(20, 6, $this->cambiarVariable($arr->debe, 2), 1, 0, 'R', 1);
            $this->Cell(20, 6, $this->cambiarVariable($arr->haber, 2), 1, 0, 'R', 1);

            $debito += (float) $arr->debe;
            $credito += (float) $arr->haber;
            $posY += 6;
            $i++;
            if ($i == $max) {
                $this->inicio($titulo, $fecha, 50, 5);
                $this->titulos(6, 10, 35, $campos);
                $posY = 41;
                $i = 1;
            }
        }

        $this->SetFont('Arial', 'B', 12);
        $this->SetXY(10, $posY);
        $this->SetFillColor(999, 999, 999);
        $this->Cell(135, 6, 'TOTALES DE LA TRANSACCION', 1, 0, 'C', 1);
        $this->Cell(20, 6, $this->cambiarVariable($debito, 2), 1, 0, 'R', 1);
        $this->Cell(20, 6, $this->cambiarVariable($credito, 2), 1, 0, 'R', 1);

        return $this->salida($titulo.'.pdf');
    }

    // ==================================================================
    // 13 — IMPRESIÓN DE UNA FACTURA
    // ==================================================================

    protected function facturaDatos(int $id): ?object
    {
        return DB::table('facturas as f')
            ->leftJoin('clientes as c', 'c.id', '=', 'f.id_cliente')
            ->leftJoin('monedas as m', 'm.id', '=', 'c.idmonedas')
            ->leftJoin('users as u', 'u.id', '=', 'f.id_user')
            ->where('f.id', $id)
            ->select(
                'f.*',
                'c.codigo as codcliente',
                'c.nombre as nombcliente',
                'c.nit as cnit',
                'c.codreup',
                'c.ctamn',
                'c.nrocontrato',
                'c.direccion as dircliente',
                'm.nombre as monedas',
                'u.name as nombrecompleto',
                'u.apellidos',
            )
            ->first();
    }

    protected function facturaCartas(int $id)
    {
        return DB::table('aforos as a')
            ->join('cartas_porte as cp', 'cp.id', '=', 'a.id_carta_porte')
            ->leftJoin('solicitudes_servicio as s', 's.id', '=', 'cp.id_solicitud')
            ->leftJoin('tipos_cargas as tc', 'tc.id', '=', 's.id_tipo_carga')
            ->where('a.id_factura', $id)
            ->orderBy('cp.numero')
            ->select(
                'cp.numero as nrocp',
                'tc.nombre as tipocargas',
                'a.flete_mt as fletemtt',
                'a.flete_mlc as fletemlc',
                'a.flete_demora as fletedemt',
                'a.otros_mt as otrosmtt',
                'a.ingreso_mt as ingresomt',
            )->get();
    }

    protected function facturaEncabezado(object $f): void
    {
        $this->AddPage();

        $logo = public_path('images/emcarga.png');
        if (file_exists($logo)) {
            $this->Image($logo, 10, 5, 35, 20);
        }

        $this->SetXY(55, 18);
        if ($f->oventas == 0) {
            $this->SetFont('Arial', 'B', 50);
            $this->Cell(0, 6, 'FACTURA ', 0, 1, 'L');
        } else {
            $this->SetFont('Arial', 'B', 25);
            $this->Cell(0, 6, 'FACTURA OVENTAS', 0, 1, 'L');
        }

        $this->SetFont('Arial', 'B', 20);
        $this->SetXY(150, 15);
        $this->Cell($this->GetStringWidth('NRO- '.$f->numero), 6, 'NRO- '.$f->numero, 'B', 1, 'C');

        $this->SetFont('Arial', 'B', 12);
        $this->SetXY(150, 21);
        $this->Cell($this->GetStringWidth('FECHA EMISION- '.$this->d($f->fecha_emision)), 6, 'FECHA EMISION- '.$this->d($f->fecha_emision), 'B', 1, 'C');

        // Datos transportista
        $this->SetXY(10, 30);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 6, 'DATOS TRANSPORTISTA', 0, 1, 'L');
        $this->SetXY(70, 30);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell($this->GetStringWidth($this->txt((string) ($this->entidad->nombre ?? ''))), 6, $this->txt((string) ($this->entidad->nombre ?? '')), 'B', 1, 'L');

        $posX = 10;
        $posY = 36;
        $this->SetXY($posX, $posY);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 6, 'CODIGO:'.$this->txt((string) ($this->entidad->codigo ?? '')), 0, 1, 'L');
        $this->SetXY($posX, $posY + 6);
        $this->Cell(0, 6, 'NIT:'.$this->txt((string) ($this->entidad->nit ?? '')), 0, 1, 'L');
        $this->SetXY($posX, $posY + 12);
        $this->Cell(0, 6, ' REALIZAR PAGOS A NRO CUENTA:'.$this->txt((string) ($this->entidad->cta_mn ?? '')), 0, 1, 'L');
        $this->Cell(0, 6, ' NOMBRE DEL TITULAR:'.$this->txt((string) ($this->entidad->nombre ?? '')), 0, 1, 'L');
        $this->SetXY(70, $posY);
        $this->Cell(0, 6, ' DIRECCION: '.$this->txt(trim((string) ($this->entidad->direccion ?? ''))), 0, 1, 'L');

        // Datos del cliente
        $this->SetXY(10, 62);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 6, 'DATOS DEL CLIENTE', 0, 1, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(70, 62);
        $this->Cell($this->GetStringWidth($this->txt(trim((string) $f->nombcliente))), 6, $this->txt(trim((string) $f->nombcliente)), 'B', 1, 'L');

        $posX = 10;
        $posY = 68;
        $this->SetFont('Arial', 'B', 9);
        $this->SetXY($posX, $posY);
        $this->Cell(0, 6, 'CODIGO:'.$this->txt((string) $f->codcliente), 0, 1, 'L');
        $this->SetXY($posX, $posY + 6);
        $this->Cell(0, 6, 'REEUP:'.$this->txt((string) $f->codreup), 0, 1, 'L');
        $this->SetXY(70, $posY + 6);
        $this->Cell(0, 6, ' CTA MN:'.$this->txt(substr((string) $f->ctamn, 0, 14)), 0, 1, 'L');
        $this->SetXY($posX, $posY + 12);
        $this->Cell(0, 6, 'NIT:'.$this->txt((string) $f->cnit), 0, 1, 'L');
        $this->SetXY(71, $posY + 12);
        $this->Cell(0, 6, 'CONTRATO: '.$this->txt(substr((string) $f->nrocontrato, 0, 14)), 0, 1, 'L');
        $this->SetXY(70, $posY);
        $this->Cell(0, 6, ' DIRECCION: '.$this->txt(substr(trim((string) $f->dircliente), 0, 60)), 0, 1, 'L');
    }

    protected function facturaPie(object $f): void
    {
        $notas = (string) ($this->entidad->notas_fact ?? '');
        if (strlen($notas) > 0) {
            $this->SetFont('Arial', 'B', 12);
            $this->SetXY(10, 210);
            $this->MultiCell(0, 6, $this->txt($notas), 0);
        }

        $nombre = trim(($f->nombrecompleto ?? '').' '.($f->apellidos ?? ''));

        foreach ([
            [10, 'ENTREGA A SUMINISTRADOR', $nombre, ''],
            [70, 'ENTREGA A TRANSPORTADOR', '', ''],
            [130, 'RECIBIDO CLIENTE', '', ''],
        ] as [$posX, $titulo, $nombreFirma, $cargo]) {
            $posY = 230;
            $this->SetFont('Arial', 'B', 9);
            $this->SetXY($posX, $posY);
            $this->Cell(0, 6, $titulo, 0, 1, 'L');
            $this->SetFont('Arial', 'B', 7);
            $this->SetXY($posX, $posY + 6);
            $this->Cell(0, 6, 'NOMBRE Y APELLIDOS', 0, 1, 'L');
            $this->SetXY($posX, $posY + 12);
            $this->Cell(48, 2, $this->txt($nombreFirma), 'B', 1, 'L');
            $this->SetXY($posX, $posY + 16);
            $this->Cell(0, 2, 'CARGO', 0, 1, 'L');
            $this->SetXY($posX, $posY + 20);
            $this->Cell(48, 2, $this->txt($cargo), 'B', 1, 'L');
            $this->SetXY($posX, $posY + 26);
            $this->Cell(0, 2, 'FIRMA Y CUÑO', 0, 1, 'L');
            $this->SetXY($posX, $posY + 30);
            $this->Cell(25, 2, '', 'B', 1, 'L');
            $this->SetXY($posX + 30, $posY + 26);
            $this->Cell(0, 2, 'FECHA', 0, 1, 'L');
            $this->SetXY($posX + 30, $posY + 30);
            $this->Cell(20, 2, '', 'B', 1, 'L');
        }

        $this->SetFont('Arial', 'B', 9);
        $this->SetXY(185, 250);
        $this->Cell(0, 6, 'ANOTADO', 0, 1, 'L');
        $this->SetXY(185, 260);
        $this->Cell(20, 2, '', 'B', 1, 'C');
    }

    public function pdfFacturaImpresion(int $idFactura): \Illuminate\Http\Response
    {
        $f = $this->facturaDatos($idFactura);

        if (! $f) {
            $this->inicio('FACTURA');
            $this->SetFont('Arial', 'B', 25);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, 125);
            $this->Cell(0, 6, 'NO EXISTEN DATOS PARA MOSTRAR', 0, 1, 'C');

            return $this->salida('factura.pdf');
        }

        $this->facturaEncabezado($f);
        $this->facturaPie($f);

        $titulo = 'FACTURA '.$f->numero;
        $this->SetAutoPageBreak(false);

        if ($f->cancelada == 1) {
            $this->SetFont('Arial', 'B', 40);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, 125);
            $this->Cell(0, 6, 'FACTURA CANCELADA ', 0, 1, 'C');

            return $this->salida('factura.pdf');
        }
        if ($f->refacturada == 1) {
            $this->SetFont('Arial', 'B', 40);
            $this->SetFillColor(999, 999, 999);
            $this->SetXY(10, 125);
            $this->Cell(0, 6, 'FACTURA REFACTURADA ', 0, 1, 'C');

            return $this->salida('factura.pdf');
        }

        $data = $this->facturaCartas($idFactura);

        $ingresomt = (float) $f->ingreso_mt;
        $fletemtt = (float) $f->flete_mt;
        $fletemlc = (float) $f->flete_mlc;
        $fletedemt = (float) $f->flete_demora;
        $otrosmtt = (float) $f->otros_mt;
        $fleteUnico = ($ingresomt > 0 && $fletemtt == $ingresomt && $fletemlc == 0);

        $posY = 96;
        $i = 1;
        $max = 22;
        $cp = 0;
        $sumFlete = 0.0;
        $sumDemora = 0.0;
        $sumOtros = 0.0;
        $sumIngreso = 0.0;
        $sumMlc = 0.0;

        if ($data->isNotEmpty()) {
            // Cabecera de la tabla
            $this->SetFont('Arial', 'B', 8);
            $this->SetXY(10, 90);
            $this->SetFillColor(999, 999, 999);
            $this->Cell(25, 6, 'CARTA PORTE', 1, 0, 'C', 1);
            $this->SetFont('Arial', 'B', 9);
            $this->Cell(50, 6, 'TIPO DE CARGAS ', 1, 0, 'C', 1);

            if ($ingresomt > 0) {
                if ($fleteUnico) {
                    $this->Cell(25, 6, 'FLETE', 1, 0, 'C', 1);
                } else {
                    $this->Cell(25, 6, 'FLETE MN', 1, 0, 'C', 1);
                    if ($fletedemt > 0) {
                        $this->Cell(20, 6, 'DEMORA ', 1, 0, 'C', 1);
                    }
                    if ($otrosmtt > 0) {
                        $this->Cell(20, 6, 'OTROS', 1, 0, 'C', 1);
                    }
                    if ($otrosmtt > 0 || $fletedemt > 0) {
                        $this->Cell(30, 6, 'TOTAL', 1, 0, 'C', 1);
                    }
                }
            }

            foreach ($data as $arr) {
                $this->SetFont('Arial', 'B', 12);
                $this->SetXY(10, $posY);
                $this->SetFillColor(999, 999, 999);
                $this->Cell(25, 6, $this->txt((string) $arr->nrocp), 1, 0, 'C', 1);
                $this->SetFont('Arial', 'B', 8);
                $this->Cell(50, 6, $this->txt((string) $arr->tipocargas), 1, 0, 'L', 1);
                $this->SetFont('Arial', 'B', 11);

                if ($ingresomt > 0) {
                    if ($fleteUnico) {
                        $this->Cell(25, 6, $this->cambiarVariable($arr->ingresomt, 2), 1, 0, 'R', 1);
                    } else {
                        $this->Cell(25, 6, $this->cambiarVariable($arr->fletemtt, 2), 1, 0, 'R', 1);
                        if ($fletedemt > 0) {
                            $this->Cell(20, 6, $this->cambiarVariable($arr->fletedemt, 2), 1, 0, 'R', 1);
                        }
                        if ($otrosmtt > 0) {
                            $this->Cell(20, 6, $this->cambiarVariable($arr->otrosmtt, 2), 1, 0, 'R', 1);
                        }
                        if ($otrosmtt > 0 || $fletedemt > 0) {
                            $this->Cell(30, 6, $this->cambiarVariable($arr->ingresomt, 2), 1, 0, 'R', 1);
                        }
                        if ((float) $arr->fletemlc > 0) {
                            $this->Cell(25, 6, 'CL = '.$this->cambiarVariable($arr->fletemlc, 2), 1, 0, 'R', 1);
                        }
                    }
                } elseif ((float) $arr->fletedemt > 0 || (float) $arr->otrosmtt > 0) {
                    if ($fletedemt > 0) {
                        $this->Cell(25, 6, $this->cambiarVariable($arr->fletedemt, 2), 1, 0, 'R', 1);
                    }
                    if ($otrosmtt > 0) {
                        $this->Cell(25, 6, $this->cambiarVariable($arr->otrosmtt, 2), 1, 0, 'R', 1);
                    }
                    if ($fletedemt > 0 && $otrosmtt > 0) {
                        $this->Cell(30, 6, $this->cambiarVariable((float) $arr->fletedemt + (float) $arr->otrosmtt, 2), 1, 0, 'R', 1);
                    }
                }

                $sumFlete += (float) $arr->fletemtt;
                $sumMlc += (float) $arr->fletemlc;
                $sumDemora += (float) $arr->fletedemt;
                $sumOtros += (float) $arr->otrosmtt;
                $sumIngreso += (float) $arr->ingresomt;
                $cp++;
                $posY += 6;
                $i++;

                if ($i == $max) {
                    $this->facturaEncabezado($f);
                    $this->facturaPie($f);
                    $posY = 96;
                    $i = 1;
                }
            }

            // Totales
            $this->SetFont('Arial', 'B', 11);
            $this->SetXY(10, $posY);
            $this->SetFillColor(999, 999, 999);
            $this->Cell(75, 10, 'TOTALES PRODUCCION ('.$cp.')', 1, 0, 'L', 1);
            if ($ingresomt > 0) {
                if ($fleteUnico) {
                    $this->Cell(25, 10, $this->cambiarVariable($sumIngreso, 2), 1, 0, 'R', 1);
                } else {
                    $this->Cell(25, 10, $this->cambiarVariable($sumFlete, 2), 1, 0, 'R', 1);
                    if ($sumDemora > 0) {
                        $this->Cell(20, 10, $this->cambiarVariable($sumDemora, 2), 1, 0, 'R', 1);
                    }
                    if ($sumOtros > 0) {
                        $this->Cell(20, 10, $this->cambiarVariable($sumOtros, 2), 1, 0, 'R', 1);
                    }
                    if ($sumDemora > 0 || $sumOtros > 0) {
                        $this->Cell(30, 10, $this->cambiarVariable($sumIngreso, 2), 1, 0, 'R', 1);
                    }
                    if ($fletemlc > 0) {
                        $this->Cell(25, 10, 'CL = '.$this->cambiarVariable($sumMlc, 2), 1, 0, 'R', 1);
                    }
                }
            }
        }

        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Página '.$this->PageNo(), 0, 0, 'C');

        return $this->salida('factura.pdf');
    }
}
