<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alerta extends Model
{
    protected $fillable = ['mensaje', 'fecha_emision', 'fecha_vencimiento', 'id_user', 'id_perfil', 'vencida'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function perfilesRh(): BelongsTo
    {
        return $this->belongsTo(PerfilesRh::class, 'id_perfil');
    }
}
