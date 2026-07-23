<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoSexo;

class TiposSexoController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoSexo::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-sexo.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Sexo';
    }
}
