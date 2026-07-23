<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\CentrosCosto;

class CentrosCostosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return CentrosCosto::class;
    }

    protected function getRouteName(): string
    {
        return 'centros-costos';
    }

    protected function getTitle(): string
    {
        return 'Centros de Costo';
    }
}
