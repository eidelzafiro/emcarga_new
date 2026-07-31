<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Area;

class AreasController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Area::class;
    }

    protected function getRouteName(): string
    {
        return 'areas';
    }

    protected function getTitle(): string
    {
        return 'Áreas';
    }

    protected function getExtraFields(): array
    {
        return [];
    }

    protected function isEntityScoped(): bool
    {
        return true;
    }
}
