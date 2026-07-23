<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoNeumatico;

class TipoNeumaticosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoNeumatico::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-neumaticos';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Neumáticos';
    }
}
