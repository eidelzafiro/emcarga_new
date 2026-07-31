<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoEstado;

class TiposEstadosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoEstado::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-estados';
    }

    protected function getTitle(): string
    {
        return 'Tipos Estados';
    }
}
