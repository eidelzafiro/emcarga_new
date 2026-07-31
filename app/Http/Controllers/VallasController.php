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

    protected function getExtraFields(): array
    {
        $naves = Nave::select('id', 'nombre')->where('activo', true)->orderBy('nombre')->get();
        return [
            'id_nave' => [
                'label' => 'Nave',
                'type' => 'select',
                'options' => $naves->map(fn ($n) => ['value' => $n->id, 'label' => $n->nombre])->toArray(),
            ],
        ];
    }
}
