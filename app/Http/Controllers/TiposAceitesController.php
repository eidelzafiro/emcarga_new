<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TiposAceite;

class TiposAceitesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TiposAceite::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-aceites';
    }

    protected function getMinRoutePart(): string
    {
        return 'tipo-aceite';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Aceites';
    }
}
