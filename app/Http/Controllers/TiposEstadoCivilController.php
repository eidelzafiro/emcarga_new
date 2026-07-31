<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoEstadoCivil;

class TiposEstadoCivilController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoEstadoCivil::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-estado-civil';
    }

    protected function getTitle(): string
    {
        return 'Tipos Estado Civil';
    }
}
