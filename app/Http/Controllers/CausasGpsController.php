<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\CausasGp;

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
