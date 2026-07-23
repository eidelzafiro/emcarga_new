<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostosTaller extends Model
{
    protected $table = 'costos_taller';

    protected $fillable = ['id_tractivo', 'horas_taller', 'fecha'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }
}
