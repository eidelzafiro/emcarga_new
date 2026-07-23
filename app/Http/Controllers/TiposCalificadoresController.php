<?php

namespace App\Http\Controllers;

use App\Models\TipoCalificador;
use App\Http\Controllers\Traits\ManagesCatalog;

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
