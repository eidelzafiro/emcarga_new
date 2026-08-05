<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calificador extends Model
{
    protected $table = 'calificadores';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function cargos()
    {
        return $this->hasMany(Cargo::class, 'id_calificador');
    }
}
