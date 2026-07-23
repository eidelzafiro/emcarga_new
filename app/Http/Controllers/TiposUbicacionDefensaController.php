<?php

namespace App\Http\Controllers;

use App\Models\TipoUbicacionDefensa;
use App\Http\Controllers\Traits\ManagesCatalog;

class TiposUbicacionDefensaController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoUbicacionDefensa::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-ubicacion-defensa.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Ubicación Defensa';
    }
}
