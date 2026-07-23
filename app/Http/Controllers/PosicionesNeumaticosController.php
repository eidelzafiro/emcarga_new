<?php

namespace App\Http\Controllers;

use App\Models\PosicionNeumatico;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

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
        return 'Posiciones de Neumáticos';
    }
    
    protected function getExtraFields(): array
    {
        return [
            'descripcion' => ['label' => 'Descripción', 'type' => 'text'],
        ];
    }
}
