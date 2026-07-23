<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\ElementosGasto;

class ElementosGastoController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return ElementosGasto::class;
    }

    protected function getRouteName(): string
    {
        return 'elementos-gasto';
    }

    protected function getMinRoutePart(): string
    {
        return 'elementos-gasto';
    }

    protected function getTitle(): string
    {
        return 'Elementos de Gasto';
    }
}
