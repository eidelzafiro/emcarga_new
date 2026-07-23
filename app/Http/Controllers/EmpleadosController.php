<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

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
