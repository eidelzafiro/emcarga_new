<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Modelo;

class ModelosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Modelo::class;
    }

    protected function getRouteName(): string
    {
        return 'modelos';
    }

    protected function getTitle(): string
    {
        return 'Modelos';
    }
}
