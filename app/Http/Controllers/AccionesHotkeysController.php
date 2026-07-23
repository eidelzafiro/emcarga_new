<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\AccioneHotkey;

class AccionesHotkeysController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return AccioneHotkey::class;
    }

    protected function getRouteName(): string
    {
        return 'acciones-hotkeys';
    }

    protected function getTitle(): string
    {
        return 'Acciones de Hotkeys';
    }
}
