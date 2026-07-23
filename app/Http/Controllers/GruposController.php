<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

class GruposController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Grupo::class;
    }

    protected function getRouteName(): string
    {
        return 'grupos';
    }

    protected function getTitle(): string
    {
        return 'Grupos';
    }
    
}
