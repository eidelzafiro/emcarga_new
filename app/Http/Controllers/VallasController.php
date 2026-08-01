<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Nave;
use App\Models\Valla;

class VallasController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string { return Valla::class; }
    protected function getRouteName(): string { return 'vallas'; }
    protected function getTitle(): string { return 'Vallas'; }

    protected function isEntityScoped(): bool
    {
        return true;
    }

    protected function applyEntityScope($query, int $entidadId): void
    {
        $query->whereHas('nave', fn ($q) => $q->where('naves.id_entidad', $entidadId));
    }

    protected function getScopingData(): array
    {
        return [];
    }

    protected function getExtraFields(): array
    {
        $entidadId = (int) session('entidad_activa_id');

        $naves = Nave::select('naves.id', 'naves.nombre', 'talleres.nombre as taller_nombre')
            ->leftJoin('talleres', 'talleres.id', '=', 'naves.id_taller')
            ->where('naves.activo', true)
            ->when($entidadId, fn ($q) => $q->where('naves.id_entidad', $entidadId))
            ->orderBy('naves.nombre')
            ->get();

        return [
            'id_nave' => [
                'label' => 'Nave',
                'type' => 'select',
                'required' => true,
                'options' => $naves->map(fn ($n) => [
                    'value' => $n->id,
                    'label' => $n->taller_nombre ? "{$n->nombre} ({$n->taller_nombre})" : $n->nombre,
                ])->toArray(),
            ],
        ];
    }
}
