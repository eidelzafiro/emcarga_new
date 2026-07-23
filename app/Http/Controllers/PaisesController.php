<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Pais;

class PaisesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Pais::class;
    }

    protected function getRouteName(): string
    {
        return 'paises';
    }

    protected function getTitle(): string
    {
        return 'Países';
    }
}
