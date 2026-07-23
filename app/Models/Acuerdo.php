<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Acuerdo extends Model
{
    use SoftDeletes;

    protected $table = 'acuerdos';

    protected $fillable = ['id_cliente', 'codigo', 'descripcion', 'fecha_inicio', 'fecha_fin', 'tarifa_base', 'moneda', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'fecha_inicio' => 'date', 'fecha_fin' => 'date', 'tarifa_base' => 'decimal:2'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }
}
