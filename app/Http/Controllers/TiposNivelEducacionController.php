<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoNivelEducacion;

class TiposNivelEducacionController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoNivelEducacion::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-nivel-educacion.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Nivel Educación';
    }
}
