<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TurnosComerciale;

class TurnosComercialesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TurnosComerciale::class;
    }

    protected function getRouteName(): string
    {
        return 'turnos-comerciales.index';
    }

    protected function getTitle(): string
    {
        return 'Turnos Comerciales';
    }

    protected function getSearchFields(): array
    {
        return ['nombre'];
    }
}
