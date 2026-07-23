<?php

namespace App\Http\Controllers;

use App\Models\TipoEquipo;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

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
