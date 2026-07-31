<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Provincia;

class ProvinciasController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Provincia::class;
    }

    protected function getRouteName(): string
    {
        return 'provincias';
    }

    protected function getTitle(): string
    {
        return 'Provincias';
    }

    protected function getSearchFields(): array
    {
        return ['nombre'];
    }

    protected function getValidationRules($id = null): array
    {
        return [
            'nombre' => 'required|string|max:150',
        ];
    }

    protected function getExtraFields(): array
    {
        return [];
    }
}
