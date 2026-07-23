<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Marca;

class MarcasController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Marca::class;
    }

    protected function getRouteName(): string
    {
        return 'marcas';
    }

    protected function getTitle(): string
    {
        return 'Marcas';
    }

    protected function getExtraFields(): array
    {
        return [
            'tipo' => ['label' => 'Tipo', 'type' => 'text'],
        ];
    }
}
