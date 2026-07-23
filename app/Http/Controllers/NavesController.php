<?php

namespace App\Http\Controllers;

use App\Models\Nave;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

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
    
    protected function getExtraFields(): array
    {
        return [
            'ubicacion' => ['label' => 'Ubicación', 'type' => 'text'],
        ];
    }
}
