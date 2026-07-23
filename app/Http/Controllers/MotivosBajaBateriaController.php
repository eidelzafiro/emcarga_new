<?php

namespace App\Http\Controllers;

use App\Models\MotivosBajaBaterium;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ManagesCatalog;

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
        return 'Motivos de Baja de Bater\xc3\xada';
    }
}
