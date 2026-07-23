<?php

namespace App\Http\Controllers;

use App\Models\TipoClasificacionLaboral;
use App\Http\Controllers\Traits\ManagesCatalog;

class TiposClasificacionLaboralController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoClasificacionLaboral::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-clasificacion-laboral.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Clasificación Laboral';
    }
}
