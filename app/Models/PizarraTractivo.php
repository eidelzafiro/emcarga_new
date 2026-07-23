<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PizarraTractivo extends Model
{
    protected $fillable = ['mes', 'ano', 'id_tractivo', 'dias'];

    protected function casts(): array
    {
        return [
            'dias' => 'array',
        ];
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }
}
