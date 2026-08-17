<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CombustibleDescarga extends Model
{
    use SoftDeletes;

    protected $table = 'combustible_descargas';

    protected $fillable = [
        'id_tarjeta',
        'fdescarga',
        'folio',
        'saldo_mon',
        'saldo_lts',
        'id_hoja_ruta',
        'id_comprobante',
        'hora_descarga',
        'id_servicentro',
        'f_chip',
        'kms',
        'id_entidad',
        'estado',
        'id_user',
    ];

    protected function casts(): array
    {
        return [
            'fdescarga' => 'date',
            'saldo_mon' => 'decimal:2',
            'saldo_lts' => 'decimal:2',
            'f_chip' => 'date',
            'kms' => 'decimal:2',
        ];
    }

    public function tarjeta(): BelongsTo
    {
        return $this->belongsTo(Tarjeta::class, 'id_tarjeta');
    }

    public function hojaRuta(): BelongsTo
    {
        return $this->belongsTo(HojasRuta::class, 'id_hoja_ruta');
    }

    public function servicentro(): BelongsTo
    {
        return $this->belongsTo(Servicentro::class, 'id_servicentro');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}