<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoDeduccione;

class TiposDeduccionesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoDeduccione::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-deducciones';
    }

    protected function getTitle(): string
    {
        return 'Tipos Deducciones';
    }
}
