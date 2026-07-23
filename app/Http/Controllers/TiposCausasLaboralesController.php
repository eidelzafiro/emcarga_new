<?php

namespace App\Http\Controllers;

use App\Models\TipoCausaLaboral;
use App\Http\Controllers\Traits\ManagesCatalog;

class TiposCausasLaboralesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoCausaLaboral::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-causas-laborales.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Causas Laborales';
    }
}
