<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\LocalesElectrico;

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
