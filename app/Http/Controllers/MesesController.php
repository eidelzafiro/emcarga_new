<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Mese;

class MesesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Mese::class;
    }

    protected function getRouteName(): string
    {
        return 'meses';
    }

    protected function getTitle(): string
    {
        return 'Meses';
    }

    protected function getSearchFields(): array
    {
        return ['nombre', 'codigo'];
    }

    protected function getSortField(): string
    {
        return 'codigo';
    }

    protected function getExtraFields(): array
    {
        return [
            'dias' => ['label' => 'Días', 'type' => 'text', 'required' => false],
            'dias_laborables' => ['label' => 'Días Laborables', 'type' => 'number', 'required' => false, 'step' => '0.01'],
            'dias_laborables_sin_sabado' => ['label' => 'Días Lab. sin Sábado', 'type' => 'number', 'required' => false, 'step' => '0.01'],
        ];
    }
}
