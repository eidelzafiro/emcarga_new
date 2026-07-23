<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Valla;

class VallasController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Valla::class;
    }

    protected function getRouteName(): string
    {
        return 'vallas';
    }

    protected function getTitle(): string
    {
        return 'Vallas';
    }

    protected function getExtraFields(): array
    {
        return [
            'id_nave' => ['label' => 'ID Nave', 'type' => 'text'],
        ];
    }
}
