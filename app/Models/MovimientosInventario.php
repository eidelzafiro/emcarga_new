<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientosInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $fillable = ['folio', 'id_almacen', 'id_suministrador', 'fecha_movimiento', 'factura', 'fecha_factura', 'importe_mn', 'importe_me', 'observaciones'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
