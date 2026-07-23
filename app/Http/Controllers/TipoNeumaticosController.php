<?php

namespace App\Http\Controllers;

use App\Models\TipoNeumatico;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

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
