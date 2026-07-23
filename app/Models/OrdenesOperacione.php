<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenesOperacione extends Model
{
    protected $table = 'ordenes_operaciones';

    protected $fillable = ['id_orden_taller', 'id_tipo_operacion', 'id_subsistema', 'descripcion', 'costo_mano_obra', 'costo_repuestos', 'costo_total', 'estado'];

    public function orden()
    {
        return $this->belongsTo(OrdenesTaller::class, 'id_orden_taller');
    }

    public function tipoOperacion()
    {
        return $this->belongsTo(TiposOperacione::class, 'id_tipo_operacion');
    }
}
