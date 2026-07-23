<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoColorPiel;

class TiposColorPielController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoColorPiel::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-color-piel.index';
    }

    protected function getTitle(): string
    {
        return 'Tipos Color Piel';
    }
}
