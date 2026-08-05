<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'id_entidad',
        'codigo',
        'nombre',
        'razon_social',
        'nit',
        'direccion',
        'telefono',
        'email',
        'contacto',
        'nrocontrato',
        'falta',
        'fvencimiento',
        'codreup',
        'agenciamn',
        'ctamn',
        'idorganismos',
        'idosdes',
        'idmonedas',
        'idclientesel',
        'emailfacturacion',
        'notas',
        'cancelado',
        'descuento',
        'plan',
        'mora',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'falta' => 'date',
            'fvencimiento' => 'date',
        ];
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }

    public function organismo(): BelongsTo
    {
        return $this->belongsTo(Organismo::class, 'idorganismos');
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'idmonedas');
    }
}
