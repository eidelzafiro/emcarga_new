<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = ['id_entidad', 'codigo', 'nombre', 'razon_social', 'nit', 'direccion', 'telefono', 'email', 'contacto', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}
