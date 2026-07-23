<?php

namespace App\Http\Controllers;

use App\Models\TiposRama;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ManagesCatalog;

class TiposRamasController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TiposRama::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-ramas';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Ramas';
    }
}
