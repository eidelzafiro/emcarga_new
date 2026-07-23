<?php

namespace App\Http\Controllers;

use App\Models\TiposEntidad;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ManagesCatalog;

class TiposEntidadController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TiposEntidad::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-entidad';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Entidad';
    }
}
