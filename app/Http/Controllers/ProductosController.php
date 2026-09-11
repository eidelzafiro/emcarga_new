<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Producto;

class ProductosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Producto::class;
    }

    protected function getRouteName(): string
    {
        return 'productos';
    }

    protected function getTitle(): string
    {
        return 'Productos';
    }

    protected function getExtraFields(): array
    {
        return [
            'descripcion' => ['label' => 'Descripción', 'type' => 'textarea'],
        ];
    }
}
