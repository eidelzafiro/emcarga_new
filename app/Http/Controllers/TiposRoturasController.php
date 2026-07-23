<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoRotura;

class TiposRoturasController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoRotura::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-roturas';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Roturas';
    }
}
