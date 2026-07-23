<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Embalaje;

class EmbalajesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Embalaje::class;
    }

    protected function getRouteName(): string
    {
        return 'embalajes';
    }

    protected function getTitle(): string
    {
        return 'Embalajes';
    }
}
