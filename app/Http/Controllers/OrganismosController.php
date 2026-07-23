<?php

namespace App\Http\Controllers;

use App\Models\Organismo;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

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
    
    protected function getExtraFields(): array
    {
        return [
            'abreviatura' => ['label' => 'Abreviatura', 'type' => 'text'],
        ];
    }
}
