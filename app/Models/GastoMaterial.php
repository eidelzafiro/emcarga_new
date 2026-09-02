<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GastoMaterial extends Model
{
    use HasFactory;

    protected $table = 'gasto_material';

    protected $fillable = [
        'fecha',
        'id_tractivo',
        'nombre',
        'elemento',
        'cantidad',
        'valor_mn',
        'el_gas_mn',
        'cup',
        'num_mov',
        'tipo_mov',
        'nodoc',
        'id_entidad',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'cantidad' => 'decimal:3',
            'valor_mn' => 'decimal:2',
        ];
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}
