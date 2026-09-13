<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alerta extends Model
{
    protected $fillable = ['mensaje', 'fecha_emision', 'fecha_vencimiento', 'id_user', 'vencida'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
