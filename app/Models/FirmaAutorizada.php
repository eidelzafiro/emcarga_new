<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FirmaAutorizada extends Model
{
    use SoftDeletes;

    protected $table = 'firmas_autorizadas';

    protected $fillable = [
        'nombre',
        'cargo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
