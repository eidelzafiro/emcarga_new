<?php

namespace App\Http\Controllers;

use App\Models\Provincia;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

class ProvinciasController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Provincia::class;
    }

    protected function getRouteName(): string
    {
        return 'provincias.index';
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
