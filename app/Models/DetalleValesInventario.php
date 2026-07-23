<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleValesInventario extends Model
{
    protected $table = 'detalle_vales_inventario';

    protected $fillable = ['id_vale', 'id_tarjetero', 'cantidad', 'precio_mn', 'precio_me', 'valor_mn', 'valor_me', 'renglon'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
