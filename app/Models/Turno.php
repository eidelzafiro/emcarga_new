<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Turno de nómina (réplica del legacy rh_turnos): registro transaccional
 * de turno vinculado a un movimiento (idmovimientos). NO es un catálogo.
 */
class Turno extends Model
{
    protected $table = 'turnos';

    protected $fillable = [
        'inicio',
        'final',
        'idmovimientos',
        'tiempo',
        'noct1',
        'noct2',
        'doblaje',
    ];

    protected function casts(): array
    {
        return [
            'inicio' => 'date',
            'final' => 'date',
            'tiempo' => 'decimal:2',
            'noct1' => 'decimal:2',
            'noct2' => 'decimal:2',
            'doblaje' => 'decimal:2',
        ];
    }
}
