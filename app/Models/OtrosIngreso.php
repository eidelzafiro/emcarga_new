<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtrosIngreso extends Model
{
    protected $table = 'otros_ingresos';

    protected $fillable = ['id_giro', 'concepto', 'monto', 'fecha'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }
}
