<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Representa un arrastre. Físicamente sigue siendo una fila de `tractivos`
 * (los arrastres son tractivos con id_grupo = grupo ARRASTRES), pero se
 * registra en el morph map como 'arrastre' para que las tablas polimórficas
 * (vehiculos_amortizacion, vehiculos_planes, vehiculos_documentacion) puedan
 * identificarlo con vehiculo_type='arrastre' sin necesidad de una tabla propia.
 *
 * A diferencia de neumáticos/baterías, el arrastre NO almacena id_marca/
 * id_modelo propios: se derivan del tipo de vehículo (id_tipo_vehiculo →
 * tipo_vehiculos.id_marca/id_modelo), igual que en los tractivos.
 */
class Arrastre extends Tractivo
{
    protected $table = 'arrastres';

    protected $fillable = [
        'id_entidad', 'codigo', 'placa', 'id_tipo_vehiculo',
        'id_tipo_estado',
        'tara', 'indice_aceite',
        'fecha_alta', 'fecha_baja', 'deleted_at',
    ];

    /**
     * Marca derivada del tipo de vehículo (no se almacena en arrastres).
     */
    public function getMarcaAttribute()
    {
        return optional($this->tipoVehiculo)->marca;
    }

    /**
     * Modelo derivado del tipo de vehículo (no se almacena en arrastres).
     */
    public function getModeloAttribute()
    {
        return optional($this->tipoVehiculo)->modelo;
    }

    public function tipoVehiculo(): BelongsTo
    {
        return $this->belongsTo(TipoVehiculo::class, 'id_tipo_vehiculo');
    }
}
