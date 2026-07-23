<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\ClientesMm;

class ClientesMmController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return ClientesMm::class;
    }

    protected function getRouteName(): string
    {
        return 'clientes-mm';
    }

    protected function getTitle(): string
    {
        return 'Clientes MM';
    }
}
