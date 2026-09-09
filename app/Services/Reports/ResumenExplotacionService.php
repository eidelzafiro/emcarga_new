<?php

namespace App\Services\Reports;

use App\Models\Entidad;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Motor unificado de "Resumen de Ingresos / Indicadores de Explotación".
 *
 * Una sola consulta base sobre `aforos` (resultados calculados) con todos los
 * joins de dimensión. Lo único que cambia entre variantes es el GROUP BY /
 * columna de etiqueta, de modo que los totales de ingresos, toneladas y
 * kilómetros son idénticos por construcción para cualquier variable.
 *
 * Dimensiones: tractivo, chofer, cliente, fecha (parte), producto, organismo,
 * tipo_carga, organismo_producto, cliente_producto.
 */
class ResumenExplotacionService extends BaseReportService
{
    public const DIMENSIONES = [
        'tractivo' => 'Tractivo',
        'chofer' => 'Chóferes',
        'cliente' => 'Clientes',
        'fecha' => 'Fecha de parte',
        'producto' => 'Productos',
        'organismo' => 'Organismos',
        'tipo_carga' => 'Tipos de carga',
        'organismo_producto' => 'Organismo - Producto',
        'cliente_producto' => 'Cliente - Producto',
    ];

    /**
     * Configuración de cada dimensión: expresión SQL de etiqueta (una o dos
     * columnas), GROUP BY, orden y título de columna del reporte.
     */
    private function configDimension(string $dimension): array
    {
        $map = [
            'tractivo' => [
                'titulo' => 'TRACTIVO', 'letra' => 16,
                'label' => 't.codigo', 'label2' => null,
                'groupBy' => ['t.codigo'], 'orden' => 't.codigo',
                'detalle' => 'id_tractivo', 'combinado' => false,
            ],
            'chofer' => [
                'titulo' => 'CHOFER', 'letra' => 12,
                'label' => "CONCAT(COALESCE(b.nombre,''),' ',COALESCE(b.apellidos,''))", 'label2' => null,
                'groupBy' => ["CONCAT(COALESCE(b.nombre,''),' ',COALESCE(b.apellidos,''))"], 'orden' => "CONCAT(COALESCE(b.nombre,''),' ',COALESCE(b.apellidos,''))",
                'detalle' => 'id_chofer', 'combinado' => false,
            ],
            'cliente' => [
                'titulo' => 'CLIENTE', 'letra' => 11,
                'label' => 'c.nombre', 'label2' => null,
                'groupBy' => ['c.nombre'], 'orden' => 'c.nombre',
                'detalle' => 'id_cliente', 'combinado' => false,
            ],
            'fecha' => [
                'titulo' => 'FECHA PARTE', 'letra' => 14,
                'label' => 'a.fecha_parte', 'label2' => null,
                'groupBy' => ['a.fecha_parte'], 'orden' => 'a.fecha_parte',
                'detalle' => 'fecha_parte', 'combinado' => false,
            ],
            'producto' => [
                'titulo' => 'PRODUCTO', 'letra' => 14,
                'label' => 'p.nombre', 'label2' => null,
                'groupBy' => ['p.nombre'], 'orden' => 'p.nombre',
                'detalle' => 'id_producto', 'combinado' => false,
            ],
            'organismo' => [
                'titulo' => 'ORGANISMO', 'letra' => 12,
                'label' => 'org.nombre', 'label2' => null,
                'groupBy' => ['org.nombre'], 'orden' => 'org.nombre',
                'detalle' => 'id_organismo', 'combinado' => false,
            ],
            'tipo_carga' => [
                'titulo' => 'TIPO CARGA', 'letra' => 14,
                'label' => 'tc.nombre', 'label2' => null,
                'groupBy' => ['tc.nombre'], 'orden' => 'tc.nombre',
                'detalle' => 'id_tipo_carga', 'combinado' => false,
            ],
            'organismo_producto' => [
                'titulo' => 'ORGANISMO - PRODUCTOS', 'letra' => 12,
                'label' => 'org.nombre', 'label2' => 'p.nombre',
                'groupBy' => ['org.nombre', 'p.nombre'], 'orden' => 'org.nombre, p.nombre',
                'detalle' => 'id_organismo', 'combinado' => true,
            ],
            'cliente_producto' => [
                'titulo' => 'CLIENTE - PRODUCTOS', 'letra' => 11,
                'label' => 'c.nombre', 'label2' => 'p.nombre',
                'groupBy' => ['c.nombre', 'p.nombre'], 'orden' => 'c.nombre, p.nombre',
                'detalle' => 'id_cliente', 'combinado' => true,
            ],
        ];

        if (! isset($map[$dimension])) {
            throw new \InvalidArgumentException("Dimensión no válida: {$dimension}");
        }

        return $map[$dimension];
    }

    /**
     * Consulta base con joins de dimensión y filtros comunes (periodo, entidad,
     * no canceladas). Devuelve un query builder listo para agregar.
     */
    private function queryBase(array $filtros)
    {
        [$d, $h, $acumulado] = $this->periodo($filtros);

        $ids = $this->entidadIds();

        $q = DB::table('aforos as a')
            ->join('cartas_porte as cp', 'a.id_carta_porte', '=', 'cp.id')
            ->join('hojas_ruta as h', 'cp.id_hoja_ruta', '=', 'h.id')
            ->join('tractivos as t', 'h.id_tractivo', '=', 't.id')
            ->leftJoin('bolsa as b', 'cp.id_chofer', '=', 'b.id')
            ->leftJoin('solicitudes_servicio as s', 'cp.id_solicitud', '=', 's.id')
            ->leftJoin('clientes as c', 's.id_cliente', '=', 'c.id')
            ->leftJoin('catalogo_items as org', 'c.idorganismos', '=', 'org.id')
            ->leftJoin('productos as p', 's.id_producto', '=', 'p.id')
            ->leftJoin('tipos_cargas as tc', 's.id_tipo_carga', '=', 'tc.id')
            ->where('cp.cancelada', 0)
            ->whereNull('cp.deleted_at')
            ->whereIn('t.id_entidad', $ids);

        if ($d && $h) {
            $q->whereBetween('a.fecha_parte', [$d, $h]);
        }

        return [$q, $d, $h, $acumulado];
    }

    /**
     * Normaliza el periodo desde los filtros (mes como 'YYYY-MM', '00' acumulado,
     * o mes+ano enteros). Devuelve [desde, hasta, acumulado].
     */
    private function periodo(array $filtros): array
    {
        $ano = (int) ($filtros['ano'] ?? 0);
        $mes = $filtros['mes'] ?? null;

        // mes+ano enteros desde el formulario de la vista
        if ($ano > 0 && is_numeric($mes) && (int) $mes >= 1 && (int) $mes <= 12) {
            $m = (int) $mes;
            $d = sprintf('%04d-%02d-01', $ano, $m);
            $h = Carbon::parse($d)->endOfMonth()->toDateString();

            return [$d, $h, false];
        }

        // mes como '00' (acumulado) o 'YYYY-MM'
        if (is_string($mes) && strlen($mes) >= 2) {
            $anoOps = (int) (session('fecha_operaciones') ? substr((string) session('fecha_operaciones'), 0, 4) : date('Y'));
            $mesOps = (int) (session('fecha_operaciones') ? substr((string) session('fecha_operaciones'), 5, 2) : date('m'));

            if ($mes === '00') {
                return [sprintf('%04d-01-01', $anoOps), sprintf('%04d-%02d-28', $anoOps, $mesOps), true];
            }

            try {
                $c = Carbon::parse($mes);
                $d = $c->copy()->startOfMonth()->toDateString();
                $h = $c->copy()->endOfMonth()->toDateString();

                return [$d, $h, false];
            } catch (\Exception) {}
        }

        // Sin filtro: mes de operaciones actual
        $fecha = session('fecha_operaciones') ?? now()->toDateString();
        try {
            $c = Carbon::parse($fecha);

            return [$c->startOfMonth()->toDateString(), $c->endOfMonth()->toDateString(), false];
        } catch (\Exception) {
            return [null, null, false];
        }
    }

    private function entidadIds(): array
    {
        $activa = (int) entidadActivaId();
        if (! $activa) {
            return [23];
        }

        return Entidad::idsPermitidos($activa);
    }

    /**
     * Datos agregados del resumen para una dimensión. Devuelve:
     * filas (con 'label' y opcional 'label2'), totales, titulo, periodo, entidad.
     */
    public function datosResumen(string $dimension, array $filtros, bool $indicadores = false): array
    {
        $cfg = $this->configDimension($dimension);

        [$q, $d, $h, $acumulado] = $this->queryBase($filtros);

        $select = [
            DB::raw($cfg['label'].' as label'),
            DB::raw('COUNT(*) as cp'),
            DB::raw('SUM(a.tn_pos_total) as tn_pos'),
            DB::raw('SUM(a.tn_real_total) as tn_real'),
            DB::raw('SUM(a.km_carga_total) as km_carga'),
            DB::raw('SUM(a.km_vacio_total) as km_vacio'),
            DB::raw('SUM(a.km_total_total) as km_total'),
            DB::raw('SUM(a.traf_pos_total) as traf_pos'),
            DB::raw('SUM(a.traf_real_total) as traf_real'),
            DB::raw('SUM(a.viajes) as viajes'),
            DB::raw('SUM(a.flete_mt) as flete'),
            DB::raw('SUM(a.flete_demora) as demora'),
            DB::raw('SUM(a.otros_mt) as otros'),
            DB::raw('SUM(a.ingreso_mt) as ingreso'),
        ];

        if ($cfg['label2']) {
            array_unshift($select, DB::raw($cfg['label2'].' as label2'));
        }

        $rows = $q->select($select)
            ->groupByRaw(implode(', ', $cfg['groupBy']))
            ->orderByRaw($cfg['orden'])
            ->get();

        $filas = $rows->map(function ($r) {
            $fila = [
                'label' => trim((string) ($r->label ?? '')) ?: 'SIN DEFINIR',
                'cp' => (int) $r->cp,
                'tn_pos' => round((float) $r->tn_pos, 2),
                'tn_real' => round((float) $r->tn_real, 2),
                'km_carga' => round((float) $r->km_carga, 2),
                'km_vacio' => round((float) $r->km_vacio, 2),
                'km_total' => round((float) $r->km_total, 2),
                'traf_pos' => round((float) $r->traf_pos, 2),
                'traf_real' => round((float) $r->traf_real, 2),
                'viajes' => round((float) $r->viajes, 2),
                'flete' => round((float) $r->flete, 2),
                'demora' => round((float) $r->demora, 2),
                'otros' => round((float) $r->otros, 2),
                'ingreso' => round((float) $r->ingreso, 2),
            ];
            if (property_exists($r, 'label2')) {
                $fila['label2'] = trim((string) $r->label2) ?: 'SIN DEFINIR';
            }

            return $fila;
        })->values()->toArray();

        $totales = [
            'cp' => $rows->sum('cp'),
            'tn_pos' => round($rows->sum('tn_pos'), 2),
            'tn_real' => round($rows->sum('tn_real'), 2),
            'km_carga' => round($rows->sum('km_carga'), 2),
            'km_vacio' => round($rows->sum('km_vacio'), 2),
            'km_total' => round($rows->sum('km_total'), 2),
            'traf_pos' => round($rows->sum('traf_pos'), 2),
            'traf_real' => round($rows->sum('traf_real'), 2),
            'viajes' => round($rows->sum('viajes'), 2),
            'flete' => round($rows->sum('flete'), 2),
            'demora' => round($rows->sum('demora'), 2),
            'otros' => round($rows->sum('otros'), 2),
            'ingreso' => round($rows->sum('ingreso'), 2),
        ];

        $titulo = $this->titulo($dimension, $cfg, $acumulado, $indicadores);
        $entidad = Entidad::find((int) entidadActivaId());

        return [
            'filas' => $filas,
            'totales' => $totales,
            'titulo' => $titulo,
            'periodo' => $d ? Carbon::parse($d)->format('Y-m') : '',
            'entidadNombre' => $entidad?->nombre ?? '',
            'entidadAbreviatura' => $entidad?->abreviatura ?? '',
            'cfg' => $cfg,
            'acumulado' => $acumulado,
        ];
    }

    /**
     * Título del reporte según familia (ingresos/indicadores) y acumulado.
     */
    private function titulo(string $dimension, array $cfg, bool $acumulado, bool $indicadores): string
    {
        if ($cfg['combinado']) {
            $base = $indicadores
                ? ($acumulado ? 'INDICADORES ACUMULADOS ' : 'INDICADORES MENSUALES ')
                : ($acumulado ? 'INGRESOS ACUMULADOS ' : 'INGRESOS MENSUALES ');

            return $base.$cfg['titulo'];
        }

        if ($indicadores) {
            $base = $acumulado ? 'INDICADORES ACUMULADOS EXPLOTACION ' : 'INDICADORES MENSUALES EXPLOTACION ';
        } else {
            $base = $acumulado ? 'PARTE INGRESOS ACUMULADOS ' : 'PARTE INGRESOS MENSUALES ';
        }

        return $base.$cfg['titulo'];
    }

    /**
     * Índice de consumo de combustible del periodo (comb_hab / kms_totales),
     * usado para estimar el combustible de las dimensiones de CP (igual que el
     * legacy: comb_cons = kmstot * indice).
     */
    public function indiceCombustible(array $filtros): float
    {
        [$d, $h] = $this->periodo($filtros);
        $ids = $this->entidadIds();

        $row = DB::table('hojas_ruta as h')
            ->join('tractivos as t', 'h.id_tractivo', '=', 't.id')
            ->where('h.cancelada', 0)
            ->whereNull('h.deleted_at')
            ->whereIn('t.id_entidad', $ids)
            ->when($d && $h, fn ($q) => $q->whereBetween('h.fecha_cierre', [$d, $h]))
            ->selectRaw('SUM(h.combustible_habilitado) as comb, SUM(h.kms_totales) as kms')
            ->first();

        $comb = (float) ($row->comb ?? 0);
        $kms = (float) ($row->kms ?? 0);

        return $kms > 0 ? $comb / $kms : 0;
    }

    /**
     * Combustible habilitado directo por tractivo (dimensiones de HR).
     * Devuelve mapa codigo → combustible.
     */
    public function combustiblePorTractivo(array $filtros): array
    {
        [$d, $h] = $this->periodo($filtros);
        $ids = $this->entidadIds();

        $rows = DB::table('hojas_ruta as h')
            ->join('tractivos as t', 'h.id_tractivo', '=', 't.id')
            ->where('h.cancelada', 0)
            ->whereNull('h.deleted_at')
            ->whereIn('t.id_entidad', $ids)
            ->when($d && $h, fn ($q) => $q->whereBetween('h.fecha_cierre', [$d, $h]))
            ->selectRaw('t.codigo as label, SUM(h.combustible_habilitado) as combustible')
            ->groupBy('t.codigo')
            ->get();

        return $rows->mapWithKeys(fn ($r) => [$r->label => round((float) $r->combustible, 2)])->toArray();
    }

    /**
     * Factor del tipo de combustible 14 (para el índice DIESEL del legado).
     */
    public function factorCombustible(): float
    {
        return (float) (DB::table('tipos_combustibles')->where('id', 14)->value('factor') ?? 0);
    }

    // === Generadores (PDF/Excel) ===

    public function generarIngresosPdf(string $dimension, array $filtros): \Illuminate\Http\Response
    {
        $datos = $this->datosResumen($dimension, $filtros, false);
        $entidadId = (int) entidadActivaId() ?: null;
        [$mes, $ano] = $this->mesAno($filtros);

        $report = new \App\Services\Reports\Fpdf\IngresosResumenFpdfReport($entidadId, $mes, $ano, $dimension, $filtros);
        $content = $report->generate();

        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$this->nombreArchivoPdf($datos['titulo'], $mes, $ano).'"');
    }

    public function generarIndicadoresPdf(string $dimension, array $filtros): \Illuminate\Http\Response
    {
        $datos = $this->datosResumen($dimension, $filtros, true);
        $entidadId = (int) entidadActivaId() ?: null;
        [$mes, $ano] = $this->mesAno($filtros);

        $report = new \App\Services\Reports\Fpdf\IndicadoresResumenFpdfReport($entidadId, $mes, $ano, $dimension, $filtros);
        $content = $report->generate();

        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$this->nombreArchivoPdf($datos['titulo'], $mes, $ano).'"');
    }

    /**
     * Nombre de archivo del PDF: nombre del reporte + mes-año (o solo el año
     * cuando es acumulado). Ej: "PARTE_INGRESOS_MENSUALES_TRACTIVO_2026-09.pdf".
     */
    private function nombreArchivoPdf(string $titulo, string $mes, string $ano): string
    {
        $sufijo = $mes === '00' ? $ano : $ano.'-'.str_pad($mes, 2, '0', STR_PAD_LEFT);

        return $this->safeName($titulo).'_'.$sufijo.'.pdf';
    }

    public function generarExcel(string $dimension, array $filtros, bool $indicadores = false): \Symfony\Component\HttpFoundation\Response
    {
        $datos = $this->datosResumen($dimension, $filtros, $indicadores);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($this->safeName($datos['titulo']), 0, 31));

        // Título y periodo
        $sheet->setCellValue('A1', $datos['titulo']);
        $sheet->setCellValue('A2', $datos['periodo'] ? 'Periodo: '.$datos['periodo'] : '');

        if ($indicadores) {
            $columnas = [
                ['label' => $this->configDimension($dimension)['titulo']],
                ['label' => 'VIAJES'], ['label' => 'COMBUSTIBLE'],
                ['label' => 'TN POS'], ['label' => 'TN REAL'],
                ['label' => 'KM CARGA'], ['label' => 'KM VACIO'], ['label' => 'KM TOTAL'],
                ['label' => 'TRAF POS'], ['label' => 'TRAF REAL'],
                ['label' => 'CAR'], ['label' => 'CACE'], ['label' => 'DIST MEDIA'], ['label' => 'DIESEL'],
            ];
        } else {
            $columnas = [
                ['label' => $this->configDimension($dimension)['titulo']],
                ['label' => 'CP'],
                ['label' => 'TONS POS'], ['label' => 'TONS REAL'],
                ['label' => 'KMS CARGA'], ['label' => 'KMS VACIO'], ['label' => 'KMS TOTAL'],
                ['label' => 'FLETE'], ['label' => 'DEMORA'], ['label' => 'OTROS'], ['label' => 'IMPORTE'],
            ];
        }

        $fila = 4;
        $col = 1;
        foreach ($columnas as $c) {
            $sheet->setCellValueByColumnAndRow($col++, $fila, $c['label']);
        }

        $combTractivo = $indicadores ? $this->combustiblePorTractivo($filtros) : [];
        $indice = $indicadores ? $this->indiceCombustible($filtros) : 0;

        foreach ($datos['filas'] as $f) {
            $fila++;
            $col = 1;
            $sheet->setCellValueByColumnAndRow($col++, $fila, $f['label'] ?? '');
            if (isset($f['label2'])) {
                $sheet->setCellValueByColumnAndRow($col, $fila, $f['label2']);
            }
            $col++;

            if ($indicadores) {
                $comb = $this->combustibleFila($dimension, $f, $combTractivo, $indice);
                $indic = $this->indicadoresDerivados($f, $comb);
                $vals = [$f['viajes'], $comb, $f['tn_pos'], $f['tn_real'],
                    $f['km_carga'], $f['km_vacio'], $f['km_total'],
                    $f['traf_pos'], $f['traf_real'],
                    $indic['car'], $indic['cace'], $indic['distmedia'], $indic['diesel']];
            } else {
                $vals = [$f['cp'], $f['tn_pos'], $f['tn_real'],
                    $f['km_carga'], $f['km_vacio'], $f['km_total'],
                    $f['flete'], $f['demora'], $f['otros'], $f['ingreso']];
            }
            foreach ($vals as $v) {
                $sheet->setCellValueByColumnAndRow($col++, $fila, $v);
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tmp = tempnam(sys_get_temp_dir(), 'res').'.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, $this->safeName($datos['titulo']).'.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * Calcula los indicadores derivados (CAR, CACE, DIST MEDIA, DIESEL) a partir
     * de una fila agregada (paridad con el legacy).
     */
    private function indicadoresDerivados(array $f, float $comb): array
    {
        $car = ($f['km_carga'] > 0 && $f['km_total'] > 0) ? round(($f['km_carga'] / $f['km_total']) * 100, 2) : 0;
        $cace = ($f['tn_real'] > 0 && $f['tn_pos'] > 0) ? round(($f['tn_real'] / $f['tn_pos']) * 100, 2) : 0;
        $dist = ($f['traf_real'] > 0 && $f['tn_real'] > 0) ? round($f['traf_real'] / $f['tn_real'], 2) : 0;

        $factor = $this->factorCombustible();
        $diesel = 0;
        if ($comb > 0 && $f['traf_real'] > 0 && $factor > 0) {
            $diesel = round(($comb / $factor) / ($f['traf_real'] / 1000000), 2);
        }

        return ['car' => $car, 'cace' => $cace, 'distmedia' => $dist, 'diesel' => $diesel];
    }

    private function combustibleFila(string $dimension, array $f, array $combTractivo, float $indice): float
    {
        if ($dimension === 'tractivo') {
            return $combTractivo[$f['label']] ?? 0;
        }

        return round($f['km_total'] * $indice, 2);
    }

    private function mesAno(array $filtros): array
    {
        $ano = (int) ($filtros['ano'] ?? 0);
        $mes = $filtros['mes'] ?? null;

        if ($ano > 0 && is_numeric($mes) && (int) $mes >= 1 && (int) $mes <= 12) {
            return [str_pad((string) (int) $mes, 2, '0', STR_PAD_LEFT), (string) $ano];
        }

        if (is_string($mes) && $mes === '00') {
            return ['00', (string) $ano];
        }

        if (is_string($mes) && preg_match('/^\d{4}-(\d{2})$/', $mes, $m)) {
            return [$m[1], substr($mes, 0, 4)];
        }

        $fecha = session('fecha_operaciones') ?? now()->toDateString();
        try {
            $c = Carbon::parse($fecha);

            return [$c->format('m'), $c->format('Y')];
        } catch (\Exception) {
            return [date('m'), date('Y')];
        }
    }
}
