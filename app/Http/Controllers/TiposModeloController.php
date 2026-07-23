<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoModelo;

class TiposModeloController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoModelo::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-modelo.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Modelo';
    }
}
