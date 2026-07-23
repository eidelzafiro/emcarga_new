<?php

namespace App\Http\Controllers;

use App\Models\TipoEstado;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

class TiposEstadosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoEstado::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-estados.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Estados';
    }
}
