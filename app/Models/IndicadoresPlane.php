<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class IndicadoresPlane extends Model {
    protected $table = 'indicadores_planes';
    protected $fillable = ['id_tipo_indicador', 'periodo', 'valores_mensuales', 'plan_periodo', 'ajuste_periodo', 'real_periodo_anterior'];
    protected function casts(): array { return ['valores_mensuales' => 'json']; }
}
