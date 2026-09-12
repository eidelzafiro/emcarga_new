<?php

namespace App\Http\Controllers;

use App\Jobs\ProcesarExportacionTabla;
use App\Models\Bolsa;
use App\Services\Reports\ReporteCatalogoService;
use App\Services\Reports\ReportesDispatcher;
use App\Services\Reports\ResumenExplotacionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Fase A: página índice del catálogo de reportes usados, agrupados por tipo,
 * con el formulario de filtro reutilizable por reporte.
 */
class ReportesController extends Controller
{
    public function index(ReporteCatalogoService $catalogo)
    {
        return Inertia::render('Reportes/Index', [
            'grupos'  => $catalogo->usadosAgrupados(),
            'resumen' => $catalogo->resumenPorTipo(),
        ]);
    }

    /**
     * Página de filtros del Modelo 1 (control de transportaciones por chofer).
     * Permite elegir el mes de operaciones y el chofer para el que se genera
     * el Modelo 1 (PDF o Excel). El corte temporal se hace por la fecha de
     * cierre de la hoja de ruta del mes seleccionado.
     */
    public function modelo1(Request $request)
    {
        $fechaOps = session('fecha_operaciones') ?? now()->toDateString();
        $mes = (int) $request->integer('mes', (int) Carbon::parse($fechaOps)->format('m'));
        $ano = (int) $request->integer('ano', (int) Carbon::parse($fechaOps)->format('Y'));

        $choferes = $this->choferesDelMes($mes, $ano);

        return Inertia::render('Reportes/Modelo1', [
            'title' => 'Modelo 1 - Control de Transportaciones',
            'mes' => $mes,
            'ano' => $ano,
            'choferes' => $choferes,
        ]);
    }

    /**
     * Página de Salarios y Prenóminas: una sola tarjeta de variables
     * (mes/año/tipo incidencia/tipo penalización/sistema de pago) y un combo
     * con TODOS los reportes de RRHH; al elegir el reporte se inactivan las
     * variables que no le corresponden. Cada reporte sale en PDF o Excel.
     */
    public function salarios(Request $request)
    {
        $fechaOps = session('fecha_operaciones') ?? now()->toDateString();
        $mes = (int) $request->integer('mes', (int) Carbon::parse($fechaOps)->format('m'));
        $ano = (int) $request->integer('ano', (int) Carbon::parse($fechaOps)->format('Y'));

        $choferes = $this->choferesDelMes($mes, $ano);

        // SOLO los tipos de incidencia que existen en el mes (con incidencias
        // registradas de trabajadores de la entidad activa).
        $tiposIncidencia = $this->tiposIncidenciaDelMes($mes, $ano);

        // Tipos de penalización (para reportes que los usan).
        $tiposPenalizacion = \App\Models\TipoPenalizacione::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->nombre])
            ->values()
            ->all();

        // Sistemas de pago (catálogo unificado).
        $sistemasPago = \App\Models\CatalogoItem::query()
            ->where('tipo', 'tipos_sistemas_pago')
            ->where('activo', true)
            ->orderBy('origen_id')
            ->get(['id', 'nombre'])
            ->map(fn ($s) => ['id' => $s->id, 'nombre' => $s->nombre])
            ->values()
            ->all();

        return Inertia::render('Reportes/Salarios', [
            'title' => 'Salarios y Prenóminas',
            'mes' => $mes,
            'ano' => $ano,
            'choferes' => $choferes,
            'tiposIncidencia' => $tiposIncidencia,
            'tiposPenalizacion' => $tiposPenalizacion,
            'sistemasPago' => $sistemasPago,
        ]);
    }

    /**
     * Tipos de incidencia (catálogo unificado) que tienen al menos una
     * incidencia registrada en el mes/año de la entidad activa.
     */
    private function tiposIncidenciaDelMes(int $mes, int $ano): array
    {
        $entidadId = (int) entidadActivaId();
        $entidades = $entidadId ? \App\Models\Entidad::idsPermitidos($entidadId) : [];

        return \App\Models\CatalogoItem::query()
            ->where('tipo', 'tipos_incidencias')
            ->where('activo', true)
            ->whereHas('incidencias', function ($q) use ($mes, $ano, $entidades) {
                $q->whereYear('fecha_inicio', $ano)->whereMonth('fecha_inicio', $mes);
                if (! empty($entidades)) {
                    $q->whereHas('bolsa', fn ($b) => $b->whereIn('id_entidad', $entidades));
                }
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'origen_id'])
            ->map(fn ($t) => ['id' => $t->id, 'nombre' => $t->nombre, 'origen_id' => $t->origen_id])
            ->values()
            ->all();
    }

    /**
     * Choferes con cartas de porte cuya hoja de ruta cerró en el mes/año
     * seleccionado (mismo corte que el Modelo 1).
     */
    private function choferesDelMes(int $mes, int $ano): array
    {
        $entidadId = (int) entidadActivaId();
        $entidades = $entidadId ? \App\Models\Entidad::idsPermitidos($entidadId) : [];

        return Bolsa::query()
            ->where('activo', true)
            ->whereHas('cartasPorte', function ($cp) use ($mes, $ano) {
                $cp->whereNull('deleted_at')
                   ->whereHas('hojaRuta', function ($hr) use ($mes, $ano) {
                       $hr->whereYear('fecha_cierre', $ano)
                          ->whereMonth('fecha_cierre', $mes)
                          ->whereNull('deleted_at');
                   });
            })
            ->when(! empty($entidades), fn ($q) => $q->whereIn('id_entidad', $entidades))
            ->orderBy('nombre')
            ->orderBy('apellidos')
            ->get(['id', 'nombre', 'apellidos', 'ci'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'nombre' => $c->nombrecompleto,
                'ci' => $c->ci ?? '',
            ])
            ->values()
            ->all();
    }

    /**
     * Página de Reportes de Documentos: listado de los reportes legacy del
     * grupo DOCUMENTOS (cartas de porte y hojas de ruta) con sus variables
     * (mes / fecha / rango de consecutivo) para generar el PDF.
     */
    public function documentos(ReporteCatalogoService $catalogo)
    {
        abort_unless(auth()->user()->can('reportes.ver'), 403);

        $grupos = $catalogo->usadosAgrupados();
        $reportes = collect($grupos['DOCUMENTOS'] ?? [])
            ->sortBy('id')
            ->values()
            ->all();

        return Inertia::render('Reportes/Documentos', [
            'title' => 'Reportes de Documentos',
            'reportes' => $reportes,
            'mesOperaciones' => session('fecha_operaciones') ?? now()->toDateString(),
        ]);
    }

    /**
     * Genera un reporte de Documentos por su id legacy, construyendo las
     * variables (mes, fecha, rango de consecutivo) y delegando al dispatcher.
     */
    public function documentosGenerar(Request $request, int $id)
    {
        abort_unless(auth()->user()->can('reportes.ver'), 403);

        $filtros = array_filter([
            'mes'               => $request->input('mes'),
            'fecha'             => $request->input('fecha'),
            'consecutivo_desde' => $request->input('consecutivo_desde'),
            'consecutivo_hasta' => $request->input('consecutivo_hasta'),
        ], fn ($v) => $v !== null && $v !== '');

        return app(ReportesDispatcher::class)->generar($id, $filtros);
    }

    /**
     * Fase A: dispara la generación del reporte migrado vía el dispatcher.
     *
     * R-3: los reportes del grupo EXPORTAR TABLAS se encolan (cola database) y
     * se notifica al usuario cuando están listos; el resto se genera en línea.
     */
    public function generar(Request $request, int $id)
    {
        $filtros = $request->input('filtros', []);
        if (is_string($filtros)) {
            $filtros = json_decode($filtros, true) ?: [];
        }

        if (ReportesDispatcher::esExportacion($id)) {
            ProcesarExportacionTabla::dispatch($id, $filtros, $request->user()->id);

            return back()->with('success', 'Exportación en cola. Te avisaremos cuando esté lista.');
        }

        return app(ReportesDispatcher::class)->generar($id, $filtros);
    }

    /**
     * Página de filtros del Resumen de Ingresos / Indicadores de Explotación.
     * Permite elegir mes, año, variable de agrupación y familia
     * (ingresos/indicadores); genera PDF o Excel.
     */
    public function resumen(Request $request)
    {
        $fechaOps = session('fecha_operaciones') ?? now()->toDateString();
        $mes = (int) $request->integer('mes', (int) Carbon::parse($fechaOps)->format('m'));
        $ano = (int) $request->integer('ano', (int) Carbon::parse($fechaOps)->format('Y'));

        $dimensiones = [];
        foreach (ResumenExplotacionService::DIMENSIONES as $clave => $nombre) {
            $dimensiones[] = ['id' => $clave, 'nombre' => $nombre];
        }

        return Inertia::render('Reportes/Resumen', [
            'title' => 'Resumen de Ingresos e Indicadores de Explotación',
            'mes' => $mes,
            'ano' => $ano,
            'dimensiones' => $dimensiones,
        ]);
    }

    /**
     * Genera el PDF/Excel del Resumen de Explotación por variable.
     */
    public function resumenGenerar(Request $request, string $formato)
    {
        abort_unless(auth()->user()->can('reportes-ingresos.ver'), 403);

        $dimension = (string) $request->input('dimension', 'tractivo');
        $indicadores = $request->boolean('indicadores', false);
        $filtros = [
            'mes' => $request->input('mes'),
            'ano' => $request->input('ano'),
        ];

        $service = app(ResumenExplotacionService::class);

        if ($formato === 'excel') {
            return $service->generarExcel($dimension, $filtros, $indicadores);
        }

        return $indicadores
            ? $service->generarIndicadoresPdf($dimension, $filtros)
            : $service->generarIngresosPdf($dimension, $filtros);
    }
}
