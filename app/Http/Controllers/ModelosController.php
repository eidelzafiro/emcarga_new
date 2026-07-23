<?php

namespace App\Http\Controllers;

use App\Models\Modelo;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

class ModelosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Modelo::class;
    }

    protected function getRouteName(): string
    {
        return 'modelos';
    }

    protected function getTitle(): string
    {
        return 'Modelos';
    }
    
    protected function getExtraFields(): array
    {
        return [
            'id_marca' => ['label' => 'ID Marca', 'type' => 'text'],
        ];
    }
}
