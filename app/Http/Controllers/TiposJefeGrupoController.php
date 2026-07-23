<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TiposJefeGrupo;

class TiposJefeGrupoController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TiposJefeGrupo::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-jefe-grupo';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Jefe de Grupo';
    }
}
