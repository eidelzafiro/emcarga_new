<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Organismo;

class OrganismosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Organismo::class;
    }

    protected function getRouteName(): string
    {
        return 'organismos';
    }

    protected function getTitle(): string
    {
        return 'Organismos';
    }

    protected function getNombreConfig(): array
    {
        return ['type' => 'textarea', 'rows' => 3];
    }

    protected function getExtraFields(): array
    {
        return [
            'abreviatura' => ['label' => 'Abreviatura', 'type' => 'text'],
        ];
    }
}
