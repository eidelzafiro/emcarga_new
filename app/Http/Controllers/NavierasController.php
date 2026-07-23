<?php

namespace App\Http\Controllers;

use App\Models\Naviera;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

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
