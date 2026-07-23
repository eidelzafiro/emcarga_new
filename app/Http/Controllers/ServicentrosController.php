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
        return 'servicentros.index';
    }

    protected function getTitle(): string
    {
        return 'Servicentros';
    }

    protected function getExtraFields(): array
    {
        return [
            'ubicacion' => ['label' => 'Ubicación', 'type' => 'text'],
        ];
    }

    protected function getSearchFields(): array
    {
        return ['codigo', 'nombre', 'ubicacion'];
    }
}
