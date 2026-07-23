<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoCargasReporte;

class TiposCargasReporteController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoCargasReporte::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-cargas-reporte.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Cargas Reporte';
    }
}
