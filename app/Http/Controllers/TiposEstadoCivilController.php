<?php

namespace App\Http\Controllers;

use App\Models\TipoEstadoCivil;
use App\Http\Controllers\Traits\ManagesCatalog;

class TiposEstadoCivilController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoEstadoCivil::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-estado-civil.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Estado Civil';
    }
}
