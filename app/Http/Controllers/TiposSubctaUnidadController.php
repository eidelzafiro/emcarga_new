<?php

namespace App\Http\Controllers;

use App\Models\TiposSubctaUnidad;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ManagesCatalog;

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
