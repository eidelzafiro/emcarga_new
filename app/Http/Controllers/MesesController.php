<?php

namespace App\Http\Controllers;

use App\Models\Mese;
use App\Http\Controllers\Traits\ManagesCatalog;

class MesesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Mese::class;
    }

    protected function getRouteName(): string
    {
        return 'meses.index';
    }

    protected function getTitle(): string
    {
        return 'Meses';
    }

    protected function getSearchFields(): array
    {
        return ['nombre', 'codigo'];
    }

    protected function getExtraFields(): array
    {
        return [
            'dias' => ['label' => 'Días', 'type' => 'number', 'required' => false],
            'dias_laborables' => ['label' => 'Días Laborables', 'type' => 'number', 'required' => false, 'step' => '0.01'],
        ];
    }
}
