<?php

namespace App\Policies;

use App\Models\CategoriasProducto;
use App\Models\User;

/**
 * Policy del módulo categorias-productos.
 */
class CategoriasProductoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'categorias-productos';
}
