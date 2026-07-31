<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\FondoTiempo;

class FondosTiempoController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return FondoTiempo::class;
    }

    protected function getRouteName(): string
    {
        return 'fondos-tiempo';
    }

    protected function getTitle(): string
    {
        return 'Fondos de Tiempo';
    }

    protected function getSortField(): string
    {
        return 'id';
    }

    protected function getSearchFields(): array
    {
        return [];
    }

    protected function getValidationRules($id = null): array
    {
        return [
            'fondo_tiempo' => 'required|numeric|min:0',
        ];
    }

    protected function getExtraFields(): array
    {
        return [
            'fondo_tiempo' => ['label' => 'Fondo de Tiempo', 'type' => 'number', 'required' => true, 'step' => '0.0001'],
        ];
    }
}
