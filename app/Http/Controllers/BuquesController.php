<?php

namespace App\Http\Controllers;

use App\Models\Buque;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

class BuquesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Buque::class;
    }

    protected function getRouteName(): string
    {
        return 'buques';
    }

    protected function getTitle(): string
    {
        return 'Buques';
    }
    
}
