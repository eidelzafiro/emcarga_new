<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\MotivosEntradaTaller;

class MotivosEntradaTallerController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return MotivosEntradaTaller::class;
    }

    protected function getRouteName(): string
    {
        return 'motivos-entrada-taller';
    }

    protected function getTitle(): string
    {
        return 'Motivos Entrada Taller';
    }
}
