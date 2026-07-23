<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagosAdicionalesCargo extends Model
{
    protected $table = 'pagos_adicionales_cargo';

    protected $fillable = ['id_cargo', 'id_tipo_pago_adicional', 'monto'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
