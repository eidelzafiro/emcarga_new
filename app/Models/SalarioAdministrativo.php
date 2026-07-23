<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalarioAdministrativo extends Model
{
    use SoftDeletes;

    protected $table = 'salarios_administrativos';

    protected $fillable = [
        'fecha', 'id_movimiento',
        'feriados', 'irregular', 'cpl', 'alimentos_extra',
        'dias_taller', 'h_extra', 'imp_h_extra',
        'observaciones', 'estado', 'id_user',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class, 'id_movimiento');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
