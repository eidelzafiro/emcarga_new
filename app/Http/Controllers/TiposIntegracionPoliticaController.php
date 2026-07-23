<?php

namespace App\Http\Controllers;

use App\Models\TipoIntegracionPolitica;
use App\Http\Controllers\Traits\ManagesCatalog;

class TiposIntegracionPoliticaController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoIntegracionPolitica::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-integracion-politica.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Integración Política';
    }
}
