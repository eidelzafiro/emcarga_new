<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Tarjetero;

class TarjeteroController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Tarjetero::class;
    }

    protected function getRouteName(): string
    {
        return 'tarjetero';
    }

    protected function getTitle(): string
    {
        return 'Tarjetero';
    }
}
