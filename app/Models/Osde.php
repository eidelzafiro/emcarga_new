<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Osde extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'siglas',
        'id_organismo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function organismo(): BelongsTo
    {
        return $this->belongsTo(Organismo::class, 'id_organismo');
    }
}
