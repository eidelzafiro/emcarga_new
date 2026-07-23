<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoDocumento;

class TiposDocumentosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoDocumento::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-documentos.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Documentos';
    }
}
