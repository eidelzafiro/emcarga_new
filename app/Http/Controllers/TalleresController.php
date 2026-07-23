<?php

namespace App\Http\Controllers;

use App\Models\Taller;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

class TalleresController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Taller::class;
    }

    protected function getRouteName(): string
    {
        return 'talleres';
    }

    protected function getTitle(): string
    {
        return 'Talleres';
    }
    
}
