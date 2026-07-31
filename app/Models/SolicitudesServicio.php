<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudesServicio extends Model
{
    protected $table = 'solicitudes_servicio';

    protected $fillable = ['id_entidad', 'numero', 'id_cliente', 'id_lugar_origen', 'id_lugar_destino', 'id_producto', 'id_producto2', 'id_tipo_carga', 'id_tipo_carga2', 'id_moneda', 'id_user', 'fecha_solicitud', 'fecha_planificada', 'fecha_ejecutada', 'valor_mt', 'valor_total', 'estado'];

    protected function casts(): array
    {
        return ['fecha_solicitud' => 'date', 'fecha_planificada' => 'date', 'fecha_ejecutada' => 'date', 'valor_mt' => 'decimal:2', 'valor_total' => 'decimal:2'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}
