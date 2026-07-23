<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Arrastre extends Model
{
    use SoftDeletes;

    protected $table = 'arrastres';

    protected $fillable = ['codigo', 'chapa', 'id_marca', 'id_tipo_equipo', 'capacidad', 'lot', 'circulacion', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'deleted_at' => 'datetime'];
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'id_marca');
    }

    public function tipoEquipo(): BelongsTo
    {
        return $this->belongsTo(TipoEquipo::class, 'id_tipo_equipo');
    }
}
