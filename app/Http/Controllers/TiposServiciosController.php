<?php

namespace App\Http\Controllers;

use App\Models\TipoServicio;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

class TiposServiciosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoServicio::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-servicios';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Servicio';
    }
    
}
