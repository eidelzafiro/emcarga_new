<?php

namespace App\Http\Controllers;

use App\Models\TiposArticulosBolsa;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ManagesCatalog;

class TiposArticulosBolsaController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TiposArticulosBolsa::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-articulos-bolsa';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Artículos de Bolsa';
    }
}
