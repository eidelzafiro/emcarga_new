<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoUbicacionDefensa;

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
