<?php

namespace App\Http\Controllers;

use App\Models\TipoDeduccione;
use App\Http\Controllers\Traits\ManagesCatalog;

class TiposDeduccionesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoDeduccione::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-deducciones.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Deducciones';
    }
}
