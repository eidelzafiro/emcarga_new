<?php

namespace App\Http\Controllers;

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
     */
    public function generar(Request $request, int $id)
    {
        return app(ReportesDispatcher::class)->generar($id, $request->input('filtros', []));
    }
}
