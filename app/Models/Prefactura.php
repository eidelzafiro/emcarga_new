<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prefactura extends Model
{
    protected $fillable = [
        'numero',
        'id_cliente',
        'fecha',
        'flete_mt',
        'flete_mlc',
        'flete_demora',
        'otros_mt',
        'ingreso_mt',
        'notas',
        'estado',
        'id_user',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'flete_mt' => 'decimal:2',
            'flete_mlc' => 'decimal:2',
            'flete_demora' => 'decimal:2',
            'otros_mt' => 'decimal:2',
            'ingreso_mt' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function aforos(): HasMany
    {
        return $this->hasMany(Aforo::class, 'id_prefactura');
    }
}
