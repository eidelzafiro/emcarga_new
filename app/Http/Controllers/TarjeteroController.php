<?php

namespace App\Http\Controllers;

use App\Models\Tarjetero;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ManagesCatalog;

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
