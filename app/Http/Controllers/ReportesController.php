<?php

namespace App\Http\Controllers;

use App\Models\ReporteLegacy;
use App\Services\Reports\ReporteCatalogoService;
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
     * Fase A (esqueleto): dispara la generación de un reporte migrado.
     * Mientras los handlers por reporte se implementan incrementalmente,
     * devuelve 200 con un mensaje claro de "pendiente".
     */
    public function generar(Request $request, int $id)
    {
        $reporte = ReporteLegacy::findOrFail($id);

        // TODO Fase A/B/C: enrutar $reporte->controlador -> handler Zafiro
        // que construya el PDF/Excel usando BaseReportService + ReporteFiltro.
        return back()->with('mensaje', 'Reporte aún no migrado a Zafiro (Fase A pendiente): '.$reporte->nombreporte);
    }
}
