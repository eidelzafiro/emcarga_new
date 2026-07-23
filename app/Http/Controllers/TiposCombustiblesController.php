<?php

namespace App\Http\Controllers;

use App\Models\TipoCombustible;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

class TiposCombustiblesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoCombustible::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-combustibles';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Combustible';
    }
    
}
