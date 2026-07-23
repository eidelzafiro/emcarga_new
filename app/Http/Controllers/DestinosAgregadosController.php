<?php

namespace App\Http\Controllers;

use App\Models\DestinoAgregado;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

class DestinosAgregadosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return DestinoAgregado::class;
    }

    protected function getRouteName(): string
    {
        return 'destinos-agregados';
    }

    protected function getTitle(): string
    {
        return 'Destinos Agregados';
    }
    
}
