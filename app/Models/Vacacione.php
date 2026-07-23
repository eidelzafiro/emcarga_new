<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vacacione extends Model
{
    protected $table = 'vacaciones';

    protected $fillable = ['id_chofer', 'fecha', 'dias', 'observaciones'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
