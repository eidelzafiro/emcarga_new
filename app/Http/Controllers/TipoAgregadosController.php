<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoAgregado;

class TipoAgregadosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoAgregado::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-agregados';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Agregados';
    }
}
