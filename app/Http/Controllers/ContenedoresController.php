<?php

namespace App\Http\Controllers;

use App\Models\Contenedore;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

class ContenedoresController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Contenedore::class;
    }

    protected function getRouteName(): string
    {
        return 'contenedores';
    }

    protected function getTitle(): string
    {
        return 'Contenedores';
    }
}
