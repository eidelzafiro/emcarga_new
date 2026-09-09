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
     * Página de Salarios y Prenóminas (choferes/administrativo) + Modelo 1.
     * Permite elegir el mes, el tipo de prenómina, y un chofer (opcional) para
     * emitir su Modelo 1 o el de todos los choferes del mes, en PDF o Excel.
     */
    public function salarios(Request $request)
    {
        $fechaOps = session('fecha_operaciones') ?? now()->toDateString();
        $mes = (int) $request->integer('mes', (int) Carbon::parse($fechaOps)->format('m'));
        $ano = (int) $request->integer('ano', (int) Carbon::parse($fechaOps)->format('Y'));

        $choferes = $this->choferesDelMes($mes, $ano);

        return Inertia::render('Reportes/Salarios', [
            'title' => 'Salarios y Prenóminas',
            'mes' => $mes,
            'ano' => $ano,
            'choferes' => $choferes,
        ]);
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
