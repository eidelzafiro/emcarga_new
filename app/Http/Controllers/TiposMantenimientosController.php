<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TiposMantenimiento;

class TiposMantenimientosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TiposMantenimiento::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-mantenimientos';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Mantenimiento';
    }

    protected function getExtraFields(): array
    {
        return [
            'descripcion' => $this->textoLargo('Descripción'),
            'kms_max' => ['label' => 'KMS Máximo del Ciclo', 'type' => 'number', 'required' => false, 'grid' => false],
            'frecuencia' => ['label' => 'Frecuencia', 'type' => 'number', 'required' => false, 'grid' => false],
            'mtto_base' => ['label' => 'Mantenimiento Base', 'type' => 'number', 'required' => false, 'grid' => false],
            'holgura' => ['label' => 'Holgura', 'type' => 'number', 'required' => false, 'grid' => false],
            'mttos' => ['label' => 'Mantenimientos por Km', 'type' => 'textarea', 'required' => false, 'grid' => false],
        ];
    }

    private function textoLargo(string $etiqueta): array
    {
        return ['label' => $etiqueta, 'type' => 'textarea', 'required' => false, 'grid' => false];
    }

    protected function afterStore($item, array $data): void
    {
        if ($item instanceof TiposMantenimiento) {
            $item->refresh();
            $item->regenerarLineas();
        }
    }

    protected function afterUpdate($item, array $data): void
    {
        if ($item instanceof TiposMantenimiento) {
            $item->refresh();
            $item->regenerarLineas();
        }
    }
}