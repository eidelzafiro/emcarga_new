<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Taller;

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

    protected function isEntityScoped(): bool
    {
        return true;
    }
}
