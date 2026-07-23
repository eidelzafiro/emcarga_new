<?php

namespace App\Http\Controllers;

use App\Models\GrupoEscala;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

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
