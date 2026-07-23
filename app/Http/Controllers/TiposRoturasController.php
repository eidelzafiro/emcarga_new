<?php

namespace App\Http\Controllers;

use App\Models\TipoRotura;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ManagesCatalog;

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
