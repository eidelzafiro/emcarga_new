<?php

namespace App\Http\Controllers;

use App\Jobs\ProcesarExportacionTabla;
use App\Services\Reports\ReporteCatalogoService;
use App\Services\Reports\ReportesDispatcher;
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
     * Fase A: dispara la generación del reporte migrado vía el dispatcher.
     *
     * R-3: los reportes del grupo EXPORTAR TABLAS se encolan (cola database) y
     * se notifica al usuario cuando están listos; el resto se genera en línea.
     */
    public function generar(Request $request, int $id)
    {
        if (ReportesDispatcher::esExportacion($id)) {
            ProcesarExportacionTabla::dispatch($id, $request->input('filtros', []), $request->user()->id);

            return back()->with('success', 'Exportación en cola. Te avisaremos cuando esté lista.');
        }

        return app(ReportesDispatcher::class)->generar($id, $request->input('filtros', []));
    }
}
