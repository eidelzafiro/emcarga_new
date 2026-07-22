<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tractivo extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'tractivos';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'codigo', 'descripcion', 'placa', 'id_tipo_vehiculo',
        'marca', 'modelo', 'anno', 'color',
        'numero_motor', 'numero_chasis', 'numero_caja',
        'capacidad_toneladas', 'capacidad_m3',
        'estado', 'fecha_alta', 'fecha_baja', 'kilometraje_actual',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'anno' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
