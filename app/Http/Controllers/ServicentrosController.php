<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Servicentro;

class ServicentrosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Servicentro::class;
    }

    protected function getRouteName(): string
    {
        return 'servicentros';
    }

    protected function getTitle(): string
    {
        return 'Servicentros';
    }

    protected function getExtraFields(): array
    {
        return [];
    }

    protected function getSearchFields(): array
    {
        return ['nombre'];
    }

    protected function usaCodigoManual(): bool
    {
        return false;
    }

    protected function getValidationRules($id = null): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'activo' => 'boolean',
        ];
    }
}
