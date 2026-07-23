<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\CategoriasProducto;

class CategoriasProductosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return CategoriasProducto::class;
    }

    protected function getRouteName(): string
    {
        return 'categorias-productos';
    }

    protected function getTitle(): string
    {
        return 'Categorías de Productos';
    }
}
