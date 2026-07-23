<?php

namespace App\Http\Controllers;

use App\Models\TipoCatalogoLugare;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

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
