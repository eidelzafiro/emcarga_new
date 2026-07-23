<?php

namespace App\Http\Controllers;

use App\Models\CausasGp;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ManagesCatalog;

class CausasGpsController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return CausasGp::class;
    }

    protected function getRouteName(): string
    {
        return 'causas-gps';
    }

    protected function getTitle(): string
    {
        return 'Causas GPS';
    }
}
