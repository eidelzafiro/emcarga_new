<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\PosicionNeumatico;

class PosicionesNeumaticosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return PosicionNeumatico::class;
    }

    protected function getRouteName(): string
    {
        return 'posiciones-neumaticos';
    }

    protected function getTitle(): string
    {
        return 'Posiciones Neumáticos';
    }
}
