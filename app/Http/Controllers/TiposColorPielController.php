<?php

namespace App\Http\Controllers;

use App\Models\TipoColorPiel;
use App\Http\Controllers\Traits\ManagesCatalog;

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
