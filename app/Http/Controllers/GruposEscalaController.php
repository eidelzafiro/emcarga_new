<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\GrupoEscala;

class GruposEscalaController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return GrupoEscala::class;
    }

    protected function getRouteName(): string
    {
        return 'grupos-escala';
    }

    protected function getTitle(): string
    {
        return 'Grupos de Escala';
    }
}
