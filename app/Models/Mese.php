<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mese extends Model
{
    protected $table = 'meses';

    protected $fillable = [
        'nombre',
        'codigo',
        'dias',
        'dias_laborables',
        'dias_laborables_sin_sabado',
        'activo',
    ];
}
