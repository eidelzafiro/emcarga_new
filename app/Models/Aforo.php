<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aforo extends Model
{
    protected $fillable = [
        'id_carta_porte',
        'id_factura',
        'id_prefactura',
        'fecha_parte',
        'flete_mt',
        'flete_mlc',
        'flete_demora',
        'otros_mt',
        'ingreso_mt',
        'descuento',
        'refactura',
        'id_user',
    ];

    protected function casts(): array
    {
        return [
            'fecha_parte' => 'date',
            'refactura' => 'boolean',
            'flete_mt' => 'decimal:2',
            'flete_mlc' => 'decimal:2',
            'flete_demora' => 'decimal:2',
            'otros_mt' => 'decimal:2',
            'ingreso_mt' => 'decimal:2',
            'descuento' => 'decimal:2',
        ];
    }

    public function cartaPorte(): BelongsTo
    {
        return $this->belongsTo(CartaPorte::class, 'id_carta_porte');
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'id_factura');
    }

    public function prefactura(): BelongsTo
    {
        return $this->belongsTo(Prefactura::class, 'id_prefactura');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
