<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoPlantilla;

class TiposPlantillasController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoPlantilla::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-plantillas.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Plantillas';
    }
}
