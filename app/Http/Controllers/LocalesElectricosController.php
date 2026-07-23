<?php

namespace App\Http\Controllers;

use App\Models\LocalesElectrico;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ManagesCatalog;

class LocalesElectricosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return LocalesElectrico::class;
    }

    protected function getRouteName(): string
    {
        return 'locales-electricos';
    }

    protected function getTitle(): string
    {
        return 'Locales Eléctricos';
    }
}
