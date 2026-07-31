<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Nave;

class NavesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Nave::class;
    }

    protected function getRouteName(): string
    {
        return 'naves';
    }

    protected function getTitle(): string
    {
        return 'Naves';
    }

    protected function isEntityScoped(): bool
    {
        return true;
    }

    protected function getExtraFields(): array
    {
        return [
            'ubicacion' => ['label' => 'Ubicación', 'type' => 'text'],
        ];
    }
}
