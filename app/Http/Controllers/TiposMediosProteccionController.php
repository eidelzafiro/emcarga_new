<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoMedioProteccion;

class TiposMediosProteccionController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoMedioProteccion::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-medios-proteccion';
    }

    protected function getTitle(): string
    {
        return 'Tipos Medios Protección';
    }
}
