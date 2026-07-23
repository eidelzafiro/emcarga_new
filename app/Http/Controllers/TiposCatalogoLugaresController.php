<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoCatalogoLugare;

class TiposCatalogoLugaresController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoCatalogoLugare::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-catalogo-lugares.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Catálogo Lugares';
    }
}
