<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Entidad;

class EntidadesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Entidad::class;
    }

    protected function getRouteName(): string
    {
        return 'entidades';
    }

    protected function getTitle(): string
    {
        return 'Entidades';
    }

    protected function getExtraFields(): array
    {
        return [
            'abreviatura' => ['label' => 'Abreviatura', 'type' => 'text'],
        ];
    }
}
