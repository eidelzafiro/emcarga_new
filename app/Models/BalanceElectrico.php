<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceElectrico extends Model
{
    protected $table = 'balances_electricos';

    protected $fillable = ['id_local', 'id_equipo', 'fecha', 'lectura_inicial', 'lectura_final', 'consumo', 'observaciones'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
