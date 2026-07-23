<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\ClientesSeleccion;

class ClientesSeleccionController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return ClientesSeleccion::class;
    }

    protected function getRouteName(): string
    {
        return 'clientes-seleccion.index';
    }

    protected function getTitle(): string
    {
        return 'Clientes Selección';
    }

    protected function getSearchFields(): array
    {
        return ['nombre'];
    }
}
