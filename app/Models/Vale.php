<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vale extends Model
{
    use SoftDeletes;

    protected $table = 'vales';

    protected $fillable = [
        'numero',
        'id_bolsa',
        'id_tractivo',
        'fecha_emision',
        'tipo',
        'concepto',
        'estado',
        'id_user',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
        ];
    }

    public function bolsa(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_bolsa');
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVale::class, 'id_vale');
    }
}
