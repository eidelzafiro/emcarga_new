<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoCalificador;

class TiposCalificadoresController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoCalificador::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-calificadores.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Calificadores';
    }
}
