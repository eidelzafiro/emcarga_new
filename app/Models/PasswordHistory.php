<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordHistory extends Model
{
    protected $table = 'password_histories';

    public $timestamps = false;

    protected $fillable = ['user_id', 'password', 'fecha_cambio'];

    protected $casts = ['fecha_cambio' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
