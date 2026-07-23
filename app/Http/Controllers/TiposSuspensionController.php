<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoSuspension;

class TiposSuspensionController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoSuspension::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-suspension';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Suspensi\xc3\xb3n';
    }
}
