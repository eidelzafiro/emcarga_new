<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bateria extends Model
{
    use SoftDeletes;

    protected $table = 'baterias';

    protected $fillable = [
        'folio', 'marca', 'modelo', 'id_tractivo',
        'fecha_instalacion', 'fecha_retiro', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_instalacion' => 'date',
            'fecha_retiro' => 'date',
        ];
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(BateriasMovimiento::class, 'id_bateria');
    }
}
