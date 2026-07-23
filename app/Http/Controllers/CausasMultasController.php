<?php

namespace App\Http\Controllers;

use App\Models\CausasMulta;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ManagesCatalog;

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
