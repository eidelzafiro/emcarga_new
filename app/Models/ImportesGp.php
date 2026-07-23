<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportesGp extends Model
{
    protected $table = 'importes_gps';

    protected $fillable = ['id_chofer', 'id_causa_gps', 'fecha', 'importe', 'observaciones'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
