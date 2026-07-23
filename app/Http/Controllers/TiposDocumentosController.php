<?php

namespace App\Http\Controllers;

use App\Models\TipoDocumento;
use App\Http\Controllers\Traits\ManagesCatalog;

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
