<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoIntegracionPolitica;

class TiposIntegracionPoliticaController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoIntegracionPolitica::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-integracion-politica';
    }

    protected function getTitle(): string
    {
        return 'Tipos Integración Política';
    }
}
