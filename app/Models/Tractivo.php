<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tractivo extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'tractivos';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'descripcion',
        'placa',
        'marca',
        'modelo',
        'anno',
        'estado',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'anno' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
