<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Factura extends Model
{
    protected $fillable = [
        'numero',
        'fecha_emision',
        'id_cliente',
        'id_unidad',
        'id_user',
        'flete_mt',
        'flete_mlc',
        'flete_demora',
        'otros_mt',
        'ingreso_mt',
        'cancelada',
        'refacturada',
        'oventas',
        'id_tipo_ingreso',
        'notas',
        'fecha_firma',
        'fecha_cobro_mn',
        'fecha_cobro_mlc',
        'fecha_conciliacion',
        'factura_cliente',
        'doc_pago_mn',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'fecha_firma' => 'date',
            'fecha_cobro_mn' => 'date',
            'fecha_cobro_mlc' => 'date',
            'fecha_conciliacion' => 'date',
            'cancelada' => 'boolean',
            'refacturada' => 'boolean',
            'oventas' => 'boolean',
            'flete_mt' => 'decimal:2',
            'flete_mlc' => 'decimal:2',
            'flete_demora' => 'decimal:2',
            'otros_mt' => 'decimal:2',
            'ingreso_mt' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function tipoIngreso(): BelongsTo
    {
        return $this->belongsTo(TipoIngreso::class, 'id_tipo_ingreso');
    }

    public function aforos(): HasMany
    {
        return $this->hasMany(Aforo::class, 'id_factura');
    }
}
