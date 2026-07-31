<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Cargo;

class CargosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Cargo::class;
    }

    protected function getRouteName(): string
    {
        return 'cargos';
    }

    protected function getTitle(): string
    {
        return 'Cargos';
    }

    protected function getExtraFields(): array
    {
        return [
            'funciones' => ['label' => 'Funciones', 'type' => 'textarea', 'required' => false],
            'medios_requeridos' => ['label' => 'Medios Requeridos', 'type' => 'textarea', 'required' => false],
            'competencias' => ['label' => 'Competencias', 'type' => 'textarea', 'required' => false],
            'es_chofer' => ['label' => 'Es Chofer', 'type' => 'boolean', 'required' => false],
        ];
    }
}
