<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CombustibleDescarga extends Model
{
    use SoftDeletes;

    protected $table = 'combustible_descargas';

    protected $fillable = [
        'id_carga',
        'id_tractivo',
        'fecha_descarga',
        'cantidad_litros',
        'kilometraje',
        'tipo_combustible',
        'observaciones',
        'estado',
        'id_user',
    ];

    protected function casts(): array
    {
        return [
            'fecha_descarga' => 'date',
            'cantidad_litros' => 'decimal:2',
            'kilometraje' => 'decimal:2',
        ];
    }

    public function carga(): BelongsTo
    {
        return $this->belongsTo(CombustibleCarga::class, 'id_carga');
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
