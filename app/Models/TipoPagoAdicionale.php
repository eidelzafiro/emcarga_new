<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoPagoAdicionale extends Model
{
    protected $table = 'tipos_pagos_adicionales';

    protected $fillable = [
        'codigo',
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
