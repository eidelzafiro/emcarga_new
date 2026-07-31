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
        return 'tipos-cargas-reporte';
    }

    protected function getTitle(): string
    {
        return 'Tipos Cargas Reporte';
    }

    protected function getSortField(): string
    {
        return 'id';
    }

    protected function getSearchFields(): array
    {
        return ['km1', 'km2', 'km3', 'km4'];
    }
}
