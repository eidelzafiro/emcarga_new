<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\PerfilesRh;

class PerfilesRhController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return PerfilesRh::class;
    }

    protected function getRouteName(): string
    {
        return 'perfiles-rh.index';
    }

    protected function getTitle(): string
    {
        return 'Perfiles RH';
    }
}
