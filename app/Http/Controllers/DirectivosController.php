<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

/**
 * Módulo del perfil DIRECTIVOS: panel con los dashboards de los módulos
 * operativos y una vista unificada de reportes.
 *
 * La vista de reportes usa los MÓDULOS REALES del sistema (los mismos que
 * Reportes: Técnica, Combustibles, Facturación, Documentos, Nómina/Salarios,
 * Ingresos e Indicadores y Modelo 1), no el catálogo legacy crudo.
 */
class DirectivosController extends Controller
{
    public function reportes()
    {
        abort_unless(auth()->user()->can('reportes.ver'), 403);

        return Inertia::render('Directivos/Reportes', [
            'title' => 'Reportes',
            'mesOperaciones' => session('fecha_operaciones') ?? now()->toDateString(),
        ]);
    }
}
