<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hotkey extends Model
{
    protected $table = 'hotkeys';

    protected $fillable = ['combinacion', 'id_accion', 'id_usuario', 'tipo', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function accion(): BelongsTo
    {
        return $this->belongsTo(AccioneHotkey::class, 'id_accion');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
