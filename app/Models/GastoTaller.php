<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GastoTaller extends Model
{
    protected $table = 'gastos_taller';

    protected $fillable = ['id_orden_taller', 'concepto', 'monto', 'fecha', 'descripcion'];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'monto' => 'decimal:2'];
    }
}
