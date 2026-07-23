<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Empleado;

class EmpleadosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Empleado::class;
    }

    protected function getRouteName(): string
    {
        return 'empleados';
    }

    protected function getMinRoutePart(): string
    {
        return 'empleado';
    }

    protected function getPeopleSkills(): bool
    {
        return true;
    }

    protected function getTitle(): string
    {
        return 'Empleados';
    }
}
