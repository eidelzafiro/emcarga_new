<?php

namespace App\Http\Controllers;

use App\Models\ClientesMm;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ManagesCatalog;

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
