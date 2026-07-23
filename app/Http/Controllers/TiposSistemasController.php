<?php

namespace App\Http\Controllers;

use App\Models\TipoSistema;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ManagesCatalog;

class TiposSistemasController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoSistema::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-sistemas';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Sistemas';
    }
}
