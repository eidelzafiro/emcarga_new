<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoTalla;

class TiposTallasController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoTalla::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-tallas.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Tallas';
    }
}
