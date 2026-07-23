<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReporteCosto extends Model
{
    use SoftDeletes;

    protected $table = 'reportes_costos';

    protected $fillable = [
        'fecha_reporte',
        'id_tractivo',
        'combustible_mn',
        'lubricante_mn',
        'piezas_mn',
        'salario',
        'vacaciones',
        'impuesto1',
        'impuesto2',
        'salario_total',
        'dietas',
        'amortizacion_mn',
        'chapa',
        'otros_gastos_mn',
        'indirectos_admin_mn',
        'indirectos_taller_mn',
        'indirectos_mn',
        'gastos_mn',
        'ingresos_mn',
        'kms_total',
        'toneladas',
        'trafico',
        'horas_taller',
        'utilidad_mn',
        'utilidad_mlc',
        'costo_mn',
        'costo_mlc',
        'costo_tn_kms',
        'observaciones',
        'estado',
        'id_user',
    ];

    protected function casts(): array
    {
        return [
            'fecha_reporte' => 'date',
            'combustible_mn' => 'decimal:2',
            'lubricante_mn' => 'decimal:2',
            'piezas_mn' => 'decimal:2',
            'costo_mn' => 'decimal:4',
            'costo_mlc' => 'decimal:4',
            'costo_tn_kms' => 'decimal:4',
        ];
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
