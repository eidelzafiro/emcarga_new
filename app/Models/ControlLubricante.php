<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlLubricante extends Model
{
    protected $table = 'control_lubricantes';

    protected $fillable = [
        'id_tractivo', 'id_unidad', 'fecha_cambio', 'tipo_operacion',
        'litros_motor', 'litros_transmision', 'litros_direccion', 'litros_hidraulico',
        'liquido_freno', 'agua_refrigerada', 'grasa_rollete', 'grasa_copillas',
        'id_lub_motor', 'id_lub_transmision', 'id_lub_hidraulico', 'id_lub_direccion',
        'id_grasa_rollete', 'id_grasa_copillas', 'id_liquido_freno', 'id_agua',
        'id_entidad',
    ];

    protected function casts(): array
    {
        return [
            'fecha_cambio' => 'date',
            'litros_motor' => 'decimal:2',
            'litros_transmision' => 'decimal:2',
            'litros_direccion' => 'decimal:2',
            'litros_hidraulico' => 'decimal:2',
            'liquido_freno' => 'decimal:2',
            'agua_refrigerada' => 'decimal:2',
            'grasa_rollete' => 'decimal:2',
            'grasa_copillas' => 'decimal:2',
        ];
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_unidad');
    }

    public function lubMotor(): BelongsTo
    {
        return $this->belongsTo(Lubricante::class, 'id_lub_motor');
    }

    public function lubTransmision(): BelongsTo
    {
        return $this->belongsTo(Lubricante::class, 'id_lub_transmision');
    }

    public function lubHidraulico(): BelongsTo
    {
        return $this->belongsTo(Lubricante::class, 'id_lub_hidraulico');
    }

    public function lubDireccion(): BelongsTo
    {
        return $this->belongsTo(Lubricante::class, 'id_lub_direccion');
    }

    public function grasaRollete(): BelongsTo
    {
        return $this->belongsTo(Lubricante::class, 'id_grasa_rollete');
    }

    public function grasaCopillas(): BelongsTo
    {
        return $this->belongsTo(Lubricante::class, 'id_grasa_copillas');
    }

    public function liquidoFreno(): BelongsTo
    {
        return $this->belongsTo(Lubricante::class, 'id_liquido_freno');
    }

    public function agua(): BelongsTo
    {
        return $this->belongsTo(Lubricante::class, 'id_agua');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}
