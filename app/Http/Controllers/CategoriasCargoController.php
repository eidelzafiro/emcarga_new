<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\CategoriaCargo;

class CategoriasCargoController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return CategoriaCargo::class;
    }

    protected function getRouteName(): string
    {
        return 'categorias-cargo';
    }

    protected function getTitle(): string
    {
        return 'Categorías de Cargo';
    }

    protected function getExtraFields(): array
    {
        return [
            'abreviatura' => ['label' => 'Abreviatura', 'type' => 'text'],
        ];
    }
}
