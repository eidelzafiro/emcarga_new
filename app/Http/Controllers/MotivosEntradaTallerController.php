<?php

namespace App\Http\Controllers;

use App\Models\MotivosEntradaTaller;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ManagesCatalog;

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
        return 'Motivos de Entrada a Taller';
    }
}
