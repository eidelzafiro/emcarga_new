<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoCausasMovimiento;

class TiposCausasMovimientoController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoCausasMovimiento::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-causas-movimiento.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Causas Movimiento';
    }
}
