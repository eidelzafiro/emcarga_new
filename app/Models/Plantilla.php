<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plantilla extends Model
{
    use SoftDeletes;

    protected $table = 'plantilla';

    protected $fillable = [
        'codigo',
        'nombre',
        'id_cargo',
        'id_entidad',
        'id_bolsa',
        'id_turno',
        'id_tipo_contrato',
        'id_tipo_sistema_pago',
        'plazas',
        'cubiertas',
        'salario_base_mn',
        'salario_base_mlc',
        'categoria',
        'aseo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'aseo' => 'boolean',
            'activo' => 'boolean',
            'plazas' => 'integer',
            'cubiertas' => 'integer',
            'salario_base_mn' => 'decimal:2',
            'salario_base_mlc' => 'decimal:2',
        ];
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'id_cargo');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidade::class, 'id_entidad');
    }

    public function bolsa(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_bolsa');
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'id_turno');
    }

    public function tipoContrato(): BelongsTo
    {
        return $this->belongsTo(TipoContrato::class, 'id_tipo_contrato');
    }

    public function tipoSistemaPago(): BelongsTo
    {
        return $this->belongsTo(TipoSistemaPago::class, 'id_tipo_sistema_pago');
    }
}
