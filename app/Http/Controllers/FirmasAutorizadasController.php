<?php

namespace App\Http\Controllers;

use App\Models\FirmaAutorizada;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

class FirmasAutorizadasController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return FirmaAutorizada::class;
    }

    protected function getRouteName(): string
    {
        return 'firmas-autorizadas.index';
    }

    protected function getTitle(): string
    {
        return 'Firmas Autorizadas';
    }

    protected function getSearchFields(): array
    {
        return ['nombre', 'cargo'];
    }

    protected function getSortField(): string
    {
        return 'nombre';
    }

    protected function getValidationRules($id = null): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'cargo' => 'nullable|string|max:255',
            'activo' => 'boolean',
        ];
    }

    protected function getExtraFields(): array
    {
        return [
            'cargo' => ['label' => 'Cargo', 'type' => 'text', 'required' => false],
        ];
    }
}
