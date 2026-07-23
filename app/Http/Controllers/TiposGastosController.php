<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoGasto;

class TiposGastosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoGasto::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-gastos';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Gasto';
    }

    protected function getExtraFields(): array
    {
        return [
            'tipo' => ['label' => 'Tipo', 'type' => 'text'],
        ];
    }
}
