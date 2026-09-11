<?php

namespace App\Policies;

/**
 * Policy del módulo productos.
 */
class ProductoPolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'productos';
}
