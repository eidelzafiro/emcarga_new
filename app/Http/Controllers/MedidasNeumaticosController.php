<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\MedidaNeumatico;

class MedidasNeumaticosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return MedidaNeumatico::class;
    }

    protected function getRouteName(): string
    {
        return 'medidas-neumaticos';
    }

    protected function getTitle(): string
    {
        return 'Medidas Neumáticos';
    }
}
