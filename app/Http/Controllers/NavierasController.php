<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Naviera;

class NavierasController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Naviera::class;
    }

    protected function getRouteName(): string
    {
        return 'navieras';
    }

    protected function getTitle(): string
    {
        return 'Navieras';
    }
}
