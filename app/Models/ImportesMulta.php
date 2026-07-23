<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportesMulta extends Model
{
    protected $table = 'importes_multas';

    protected $fillable = ['id_chofer', 'id_causa_multa', 'fecha', 'importe', 'observaciones'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
