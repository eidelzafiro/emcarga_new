<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tarjeta extends Model
{
    use SoftDeletes;

    protected $table = 'tarjetas';

    protected $fillable = [
        'numero',
        'descripcion',
        'id_cliente',
        'saldo_actual',
        'limite_credito',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'saldo_actual' => 'decimal:2',
            'limite_credito' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }
}
