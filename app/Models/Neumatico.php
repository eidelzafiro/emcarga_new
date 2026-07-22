<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Neumatico extends Model
{
    use SoftDeletes;

    protected $table = 'neumaticos';

    protected $fillable = [
        'folio', 'marca', 'modelo', 'medida', 'id_tractivo',
        'fecha_instalacion', 'fecha_retiro', 'kilometraje', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_instalacion' => 'date',
            'fecha_retiro' => 'date',
            'kilometraje' => 'decimal:2',
        ];
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(NeumaticosMovimiento::class, 'id_neumatico');
    }

    public function roturas(): HasMany
    {
        return $this->hasMany(NeumaticosRotura::class, 'id_neumatico');
    }
}
