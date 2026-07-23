<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Osde;

class OsdesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Osde::class;
    }

    protected function getRouteName(): string
    {
        return 'osdes.index';
    }

    protected function getTitle(): string
    {
        return 'OSDEs';
    }

    protected function getSearchFields(): array
    {
        return ['codigo', 'nombre', 'siglas'];
    }

    protected function getExtraFields(): array
    {
        return [
            'siglas' => ['label' => 'Siglas', 'type' => 'text', 'required' => false],
        ];
    }
}
