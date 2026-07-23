<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoEquipo;

class TipoEquiposController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoEquipo::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-equipos';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Equipo';
    }
}
