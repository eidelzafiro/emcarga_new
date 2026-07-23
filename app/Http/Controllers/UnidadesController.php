<?php

namespace App\Http\Controllers;

use App\Models\Unidade;
use App\Http\Controllers\Traits\ManagesCatalog;
use Illuminate\Http\Request;

class UnidadesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Unidade::class;
    }

    protected function getRouteName(): string
    {
        return 'unidades';
    }

    protected function getTitle(): string
    {
        return 'Unidades';
    }
}
