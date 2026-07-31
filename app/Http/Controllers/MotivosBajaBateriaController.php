<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\MotivosBajaBaterium;

class MotivosBajaBateriaController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return MotivosBajaBaterium::class;
    }

    protected function getRouteName(): string
    {
        return 'motivos-baja-bateria';
    }

    protected function getTitle(): string
    {
        return 'Motivos Baja Batería';
    }
}
