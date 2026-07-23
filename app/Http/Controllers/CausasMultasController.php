<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\CausasMulta;

class CausasMultasController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return CausasMulta::class;
    }

    protected function getRouteName(): string
    {
        return 'causas-multas';
    }

    protected function getMinRoutePart(): string
    {
        return 'causas-multa';
    }

    protected function getTitle(): string
    {
        return 'Causas de Multas';
    }
}
