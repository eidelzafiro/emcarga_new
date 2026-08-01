<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Nave;
use App\Models\Taller;

class NavesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Nave::class;
    }

    protected function getRouteName(): string
    {
        return 'naves';
    }

    protected function getTitle(): string
    {
        return 'Naves';
    }

    protected function isEntityScoped(): bool
    {
        return true;
    }

    protected function getExtraFields(): array
    {
        $entidadId = (int) session('entidad_activa_id');

        $talleres = Taller::select('id', 'nombre')
            ->where('activo', true)
            ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->orderBy('nombre')
            ->get();

        return [
            'id_taller' => [
                'label' => 'Taller',
                'type' => 'select',
                'required' => true,
                'options' => $talleres->map(fn ($t) => ['value' => $t->id, 'label' => $t->nombre])->toArray(),
            ],
            'ubicacion' => ['label' => 'Ubicación', 'type' => 'text'],
        ];
    }
}
