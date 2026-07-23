<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TiposMantenimiento extends Model
{
    use SoftDeletes;

    protected $table = 'tipos_mantenimiento';

    protected $fillable = ['codigo', 'nombre', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function lineas()
    {
        return $this->hasMany(LineasMantenimiento::class, 'id_tipo_mantenimiento');
    }
}
