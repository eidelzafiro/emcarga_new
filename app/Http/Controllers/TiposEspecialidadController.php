<?php

namespace App\Http\Controllers;

use App\Models\TipoEspecialidad;
use App\Http\Controllers\Traits\ManagesCatalog;

class TiposEspecialidadController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoEspecialidad::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-especialidad.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Especialidad';
    }
}
