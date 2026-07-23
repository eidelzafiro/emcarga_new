<?php

namespace App\Http\Controllers;

use App\Models\TipoCausasBaja;
use App\Http\Controllers\Traits\ManagesCatalog;

class TiposCausasBajaController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoCausasBaja::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-causas-baja.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Causas Baja';
    }
}
