<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conciliacione extends Model
{
    use SoftDeletes;

    protected $table = 'conciliaciones';

    protected $fillable = [
        'numero',
        'id_factura',
        'fecha_conciliacion',
        'monto',
        'tipo',
        'observaciones',
        'estado',
        'id_user',
    ];

    protected function casts(): array
    {
        return [
            'fecha_conciliacion' => 'date',
            'monto' => 'decimal:2',
        ];
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'id_factura');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
