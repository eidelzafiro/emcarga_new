<?php

namespace App\Http\Controllers;

use App\Models\CentrosCosto;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ManagesCatalog;

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
