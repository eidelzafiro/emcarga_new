<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Color;

class ColoresController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Color::class;
    }

    protected function getRouteName(): string
    {
        return 'colores';
    }

    protected function getTitle(): string
    {
        return 'Colores';
    }
}
