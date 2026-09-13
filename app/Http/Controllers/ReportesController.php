<?php

namespace App\Http\Controllers;

use App\Jobs\ProcesarExportacionTabla;
use App\Models\Bolsa;
use App\Models\CatalogoItem;
use App\Models\Entidad;
use App\Models\ReporteLegacy;
use App\Models\Tarjeta;
use App\Models\TipoPenalizacione;
use App\Models\Tractivo;
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
        // La página "Reportes" del menú usa la vista unificada por agrupador.
        return $this->vista($catalogo);
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
        $tiposPenalizacion = TipoPenalizacione::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->nombre])
            ->values()
            ->all();

        // Sistemas de pago (catálogo unificado).
        $sistemasPago = CatalogoItem::query()
            ->where('tipo', 'tipos_sistemas_pago')
            ->where('activo', true)
            ->orderBy('origen_id')
            ->get(['id', 'nombre'])
            ->map(fn ($s) => ['id' => $s->id, 'nombre' => $s->nombre])
            ->values()
            ->all();

        // Tipos de pago adicional (catálogo unificado) para el reporte de
        // penalizaciones x pago adicional.
        $tiposPagoAdicional = CatalogoItem::query()
            ->where('tipo', 'tipos_pagos_adicionales')
            ->where('activo', true)
            ->orderBy('origen_id')
            ->get(['id', 'nombre', 'origen_id'])
            ->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->nombre, 'origen_id' => $p->origen_id])
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
            'tiposPagoAdicional' => $tiposPagoAdicional,
        ]);
    }

    /**
     * Tipos de incidencia (catálogo unificado) que tienen al menos una
     * incidencia registrada en el mes/año de la entidad activa.
     */
    private function tiposIncidenciaDelMes(int $mes, int $ano): array
    {
        $entidadId = (int) entidadActivaId();
        $entidades = $entidadId ? Entidad::idsPermitidos($entidadId) : [];

        return CatalogoItem::query()
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
        $entidades = $entidadId ? Entidad::idsPermitidos($entidadId) : [];

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
            'mes' => $request->input('mes'),
            'fecha' => $request->input('fecha'),
            'consecutivo_desde' => $request->input('consecutivo_desde'),
            'consecutivo_hasta' => $request->input('consecutivo_hasta'),
        ], fn ($v) => $v !== null && $v !== '');

        return app(ReportesDispatcher::class)->generar($id, $filtros);
    }

    // =====================================================================
    // Reportes agrupados de TÉCNICA y COMBUSTIBLE
    // =====================================================================

    /** Tipos de la tabla `reportes` que agrupa la página de Técnica. */
    private const TIPOS_TECNICA = ['TECNICA', 'BATERIAS', 'NEUMATICOS', 'CONTROL TALLER'];

    /** Tipos de la tabla `reportes` que agrupa la página de Combustibles. */
    private const TIPOS_COMBUSTIBLE = ['COMBUSTIBLE'];

    /** Tipos de la tabla `reportes` que agrupa la página de Facturación. */
    private const TIPOS_FACTURACION = ['FACTURACION'];

    /** Agrupador legacy (tipo) → permiso que habilita verlo. */
    private const PERMISO_POR_TIPO = [
        'COMBUSTIBLE' => 'reportes-combustible.ver',
        'CONTROL TALLER' => 'reportes-tecnico.ver',
        'NEUMATICOS' => 'reportes-tecnico.ver',
        'ENERGIA' => 'reportes-tecnico.ver',
        'BATERIAS' => 'reportes-tecnico.ver',
        'EXISTENCIA' => 'reportes-tecnico.ver',
        'TECNICA' => 'reportes-tecnico.ver',
        'FACTURACION' => 'reportes-ingresos.ver',
        'INGRESOS' => 'reportes-ingresos.ver',
        'INDICADORES' => 'reportes-ingresos.ver',
        'COSTOS' => 'reportes-ingresos.ver',
        'GPS' => 'reportes-ingresos.ver',
        'ADMINISTRACION' => 'reportes-ingresos.ver',
        'PIZARRA' => 'reportes-ingresos.ver',
        'TIEMPOS' => 'reportes-ingresos.ver',
        'EMCARGA' => 'reportes-ingresos.ver',
        'OTROS' => 'reportes-ingresos.ver',
        'DOCUMENTOS' => 'reportes.ver',
        '0.EXPORTAR TABLAS ' => 'reportes.ver',
        '1.DATOS P/NOMINAS(A CALCULAR)' => 'reportes-nomina.ver',
        '2.DATOS P/NOMINAS(CHOFERES)' => 'reportes-nomina.ver',
        'CERTIFICOS' => 'reportes-nomina.ver',
    ];

    /**
     * Vista unificada de reportes: todos los agrupadores legacy con sus
     * reportes implementados, filtrados por el permiso del módulo. SUPERADMIN
     * y DIRECTIVOS ven todos los agrupadores.
     */
    public function vista(ReporteCatalogoService $catalogo)
    {
        $user = auth()->user();
        $perfil = session('perfil_activo');
        $esTodo = $user->hasRole('SUPERADMIN') || $perfil === 'DIRECTIVOS' || $user->hasRole('DIRECTIVOS');

        $grupos = $catalogo->usadosAgrupados();
        $filtrados = [];

        foreach ($grupos as $tipo => $reportes) {
            $permiso = self::PERMISO_POR_TIPO[$tipo] ?? 'reportes.ver';

            if (! $esTodo && ! $user->can($permiso)) {
                continue;
            }

            // Solo los reportes con handler implementado.
            $implementados = array_values(array_filter(
                $reportes,
                fn ($r) => ReportesDispatcher::estaMapeado((int) $r['id'])
            ));

            if (empty($implementados)) {
                continue;
            }

            $implementados = array_map(function ($r) {
                $r['nombre'] = trim(strip_tags((string) $r['nombre']));
                $r['puede_excel'] = ReportesDispatcher::esExportacion((int) $r['id']);

                return $r;
            }, $implementados);

            usort($implementados, fn ($a, $b) => strcasecmp($a['nombre'], $b['nombre']));

            $filtrados[$tipo] = $implementados;
        }

        // Listados de codificadores (rutas dedicadas, sin variables).
        $filtrados['CODIFICADORES'] = [
            ['id' => 'marcas', 'nombre' => 'Listado de Marcas', 'variable' => '', 'filtro' => 'directo', 'url' => route('reportes.marcas'), 'puede_excel' => false],
            ['id' => 'modelos', 'nombre' => 'Listado de Modelos', 'variable' => '', 'filtro' => 'directo', 'url' => route('reportes.modelos'), 'puede_excel' => false],
            ['id' => 'paises', 'nombre' => 'Listado de Países', 'variable' => '', 'filtro' => 'directo', 'url' => route('reportes.paises'), 'puede_excel' => false],
        ];

        // Ingresos e Indicadores: página de resumen (PDF y Excel).
        $filtrados['INGRESOS E INDICADORES'] = [
            ['id' => 'resumen', 'nombre' => 'Resumen de Ingresos e Indicadores de Explotación', 'variable' => '', 'filtro' => 'directo', 'url' => route('reportes.resumen'), 'puede_excel' => true],
        ];

        // Operaciones EMCARGA (Excel detallado por aforo, mes de operaciones).
        $filtrados['OPERACIONES EMCARGA'] = [
            ['id' => 'operaciones-emcarga', 'nombre' => 'Reporte de Operaciones EMCARGA (Excel)', 'variable' => 'mes', 'filtro' => 'mes', 'url' => route('reportes.operaciones-emcarga-excel'), 'puede_excel' => true],
        ];

        return Inertia::render('Reportes/Vista', [
            'title' => 'Reportes',
            'grupos' => $filtrados,
            'opciones' => $this->opcionesFiltros(),
            'mesOperaciones' => session('fecha_operaciones') ?? now()->toDateString(),
        ]);
    }

    /**
     * Página de Reportes de Técnica: reportes del módulo técnico (Técnica,
     * Baterías, Neumáticos, Control Taller) con sus variables para generar el PDF.
     */
    public function tecnicas(ReporteCatalogoService $catalogo)
    {
        abort_unless(auth()->user()->can('reportes-tecnico.ver'), 403);

        return Inertia::render('Reportes/Generador', $this->payloadAgrupado(
            $catalogo,
            self::TIPOS_TECNICA,
            'Reportes de Técnica',
            // CERTIFICO INDICES DE CONSUMO (dominio técnico, tipo CERTIFICOS)
            [4025],
        ));
    }

    public function tecnicasGenerar(Request $request, int $id)
    {
        abort_unless(auth()->user()->can('reportes-tecnico.ver'), 403);

        return $this->generarAgrupado($request, $id);
    }

    /**
     * Página de Reportes de Combustibles (dominio de Contabilidad).
     */
    public function combustibles(ReporteCatalogoService $catalogo)
    {
        abort_unless(auth()->user()->can('reportes-combustible.ver'), 403);

        return Inertia::render('Reportes/Generador', $this->payloadAgrupado(
            $catalogo,
            self::TIPOS_COMBUSTIBLE,
            'Reportes de Combustibles',
            // Conciliaciones de combustible (tipo INDICADORES): 23 y 28.
            [23, 28],
        ));
    }

    public function combustiblesGenerar(Request $request, int $id)
    {
        abort_unless(auth()->user()->can('reportes-combustible.ver'), 403);

        return $this->generarAgrupado($request, $id);
    }

    /**
     * Página de Reportes de Facturación (dominio de Contabilidad y Comercial).
     */
    public function facturacion(ReporteCatalogoService $catalogo)
    {
        abort_unless(auth()->user()->can('reportes-facturacion.ver'), 403);

        return Inertia::render('Reportes/Generador', $this->payloadAgrupado(
            $catalogo,
            self::TIPOS_FACTURACION,
            'Reportes de Facturación',
        ));
    }

    public function facturacionGenerar(Request $request, int $id)
    {
        abort_unless(auth()->user()->can('reportes-facturacion.ver'), 403);

        return $this->generarAgrupado($request, $id);
    }

    /**
     * Payload de una página de reportes agrupados: reportes de los tipos
     * indicados (más ids extra), catálogos de filtros y mes de operaciones.
     */
    private function payloadAgrupado(ReporteCatalogoService $catalogo, array $tipos, string $title, array $idsExtra = []): array
    {
        $grupos = $catalogo->usadosAgrupados();

        $reportes = collect();
        foreach ($tipos as $tipo) {
            $reportes = $reportes->merge($grupos[$tipo] ?? []);
        }

        if ($idsExtra) {
            $ids = array_map('intval', $idsExtra);
            $reportes = $reportes->merge(
                collect($grupos)->flatten(1)->filter(fn ($r) => in_array((int) $r['id'], $ids, true))
            );
        }

        return [
            'title' => $title,
            'reportes' => $reportes->unique('id')->sortBy('id')->values()->all(),
            'opciones' => $this->opcionesFiltros(),
            'mesOperaciones' => session('fecha_operaciones') ?? now()->toDateString(),
        ];
    }

    /**
     * Catálogos para los selectores de filtro de las páginas agrupadas.
     */
    private function opcionesFiltros(): array
    {
        return [
            'tractivo' => Tractivo::query()
                ->whereNull('fecha_baja')->orderBy('codigo')
                ->get(['id', 'codigo'])
                ->map(fn ($t) => ['id' => $t->id, 'label' => $t->codigo])->values()->all(),
            'tarjeta' => Tarjeta::query()
                ->orderBy('numero')
                ->get(['id', 'numero'])
                ->map(fn ($t) => ['id' => $t->id, 'label' => $t->numero])->values()->all(),
            'unidad' => Entidad::query()
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->map(fn ($e) => ['id' => $e->id, 'label' => $e->nombre])->values()->all(),
        ];
    }

    /**
     * Genera un reporte agrupado (Técnica/Combustible) traduciendo el valor del
     * filtro al parámetro que espera el servicio del reporte.
     */
    private function generarAgrupado(Request $request, int $id)
    {
        $reporte = ReporteLegacy::find($id);
        $variable = strtolower(trim((string) ($reporte->variable ?? '')));
        $tipo = app(ReporteCatalogoService::class)->tipoFiltroDe($variable);

        $filtros = [];
        switch ($tipo) {
            case 'mes':
                $filtros['mes'] = $request->input('mes');
                break;
            case 'fecha':
                $filtros['fecha'] = $request->input('fecha');
                break;
            case 'consecutivo':
                $filtros['consecutivo_desde'] = $request->input('consecutivo_desde');
                $filtros['consecutivo_hasta'] = $request->input('consecutivo_hasta');
                break;
            default:
                $valor = $request->input('valor');
                if ($valor !== null && $valor !== '') {
                    $clave = match ($variable) {
                        'tractivo2' => 'tractivo',
                        'nombrecompleto2' => 'nombrecompleto',
                        '' => 'valor',
                        default => $variable,
                    };
                    $filtros[$clave] = $valor;
                }
        }

        if ($request->filled('desde')) {
            $filtros['desde'] = $request->input('desde');
        }
        if ($request->filled('hasta')) {
            $filtros['hasta'] = $request->input('hasta');
        }

        return app(ReportesDispatcher::class)->generar($id, array_filter($filtros, fn ($v) => $v !== null && $v !== ''));
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
