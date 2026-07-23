<?php

namespace App\Http\Controllers;

use App\Models\Consecutivo;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

class ConsecutivosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Consecutivo::class;
    }

    protected function getRouteName(): string
    {
        return 'consecutivos';
    }

    protected function getTitle(): string
    {
        return 'Consecutivos';
    }

    protected function getSortField(): string
    {
        return 'codigo';
    }

    protected function getSearchFields(): array
    {
        return ['codigo', 'descripcion'];
    }

    protected function getExtraFields(): array
    {
        return [
            'descripcion' => ['label' => 'Descripción', 'type' => 'text'],
            'ultimo' => ['label' => 'Último Valor', 'type' => 'number'],
            'formato' => ['label' => 'Formato', 'type' => 'text'],
        ];
    }

    protected function getValidationRules($id = null): array
    {
        $table = 'consecutivos';
        $unique = $id ? "unique:{$table},codigo,{$id}" : "unique:{$table},codigo";

        return [
            'codigo' => "required|string|max:50|{$unique}",
            'descripcion' => 'required|string|max:255',
            'ultimo' => 'integer|min:0',
            'formato' => 'nullable|string|max:50',
        ];
    }
}
