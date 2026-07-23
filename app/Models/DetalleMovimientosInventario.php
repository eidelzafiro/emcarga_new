<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleMovimientosInventario extends Model
{
    protected $table = 'detalle_movimientos_inventario';

    protected $fillable = ['id_movimiento', 'id_tarjetero', 'cantidad', 'precio_mn', 'precio_me', 'valor_mn', 'valor_me', 'renglon'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
