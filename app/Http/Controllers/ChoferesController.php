<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Choferes;

class ChoferesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Choferes::class;
    }

    protected function getRouteName(): string
    {
        return 'choferes';
    }

    protected function getMinRoutePart(): string
    {
        return 'chofer';
    }

    protected function getTitle(): string
    {
        return 'Choferes';
    }
}
