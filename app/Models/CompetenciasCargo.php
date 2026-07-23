<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetenciasCargo extends Model
{
    protected $table = 'competencias_cargo';

    protected $fillable = ['id_cargo', 'competencia', 'nivel'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'id_cargo');
    }
}
