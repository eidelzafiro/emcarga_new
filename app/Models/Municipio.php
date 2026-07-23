<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Municipio extends Model
{
    protected $fillable = [
        'nombre',
        'id_provincia',
    ];

    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Provincia::class, 'id_provincia');
    }
}
