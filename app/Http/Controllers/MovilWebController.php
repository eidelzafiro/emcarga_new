<?php

namespace App\Http\Controllers;

use App\Models\MovilWeb;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

class MovilWebController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return MovilWeb::class;
    }

    protected function getRouteName(): string
    {
        return 'movil-web.index';
    }

    protected function getTitle(): string
    {
        return 'Móvil Web';
    }

    protected function getSortField(): string
    {
        return 'created_at';
    }

    protected function getSearchFields(): array
    {
        return ['hoja_ruta'];
    }

    protected function getExtraFields(): array
    {
        return [
            'hoja_ruta' => ['label' => 'Hoja Ruta', 'type' => 'text'],
            'km' => ['label' => 'KM', 'type' => 'number'],
            'combustible' => ['label' => 'Combustible', 'type' => 'number'],
        ];
    }

    protected function getValidationRules($id = null): array
    {
        return [
            'fecha' => 'nullable|date',
            'hoja_ruta' => 'nullable|max:255',
            'km' => 'nullable|numeric|min:0',
            'combustible' => 'nullable|numeric|min:0',
        ];
    }
}
