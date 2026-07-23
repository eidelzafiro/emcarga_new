<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePrefactura extends Model
{
    protected $table = 'detalle_prefacturas';

    protected $fillable = ['id_prefactura', 'id_moneda', 'id_origen', 'id_destino', 'id_tipo_carga', 'importe', 'descripcion'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
