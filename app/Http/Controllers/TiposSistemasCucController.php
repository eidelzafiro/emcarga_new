<?php

namespace App\Http\Controllers;

use App\Models\TiposSistemasCuc;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ManagesCatalog;

class TiposSistemasCucController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TiposSistemasCuc::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-sistemas-cuc';
    }

    protected function getMinRoutePart(): string
    {
        return 'tipo-sistema-cuc';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Sistemas CUC';
    }
}
