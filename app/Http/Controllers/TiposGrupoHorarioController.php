<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoGrupoHorario;

class TiposGrupoHorarioController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoGrupoHorario::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-grupo-horario';
    }

    protected function getTitle(): string
    {
        return 'Tipos Grupo Horario';
    }
}
