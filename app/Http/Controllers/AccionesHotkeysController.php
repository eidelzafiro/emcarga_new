<?php

namespace App\Http\Controllers;

use App\Models\AccioneHotkey;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ManagesCatalog;

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
