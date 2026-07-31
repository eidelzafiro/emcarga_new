<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\ClasificacionOrdenTaller;

class ClasificacionesOrdenesTallerController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return ClasificacionOrdenTaller::class;
    }

    protected function getRouteName(): string
    {
        return 'clasificaciones-ordenes-taller';
    }

    protected function getMinRoutePart(): string
    {
        return 'clasificacion-orden-taller';
    }

    protected function getTitle(): string
    {
        return 'Clasif. OT';
    }
}
