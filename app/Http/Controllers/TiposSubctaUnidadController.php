<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TiposSubctaUnidad;

class TiposSubctaUnidadController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TiposSubctaUnidad::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-subcta-unidad';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Subcta Unidad';
    }
}
