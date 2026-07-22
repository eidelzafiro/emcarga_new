<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ConceptoCosto extends Model {
    protected $table = 'conceptos_costos';
    protected $fillable = ['codigo', 'nombre', 'id_tipo_gasto', 'activo'];
    protected function casts(): array { return ['activo' => 'boolean']; }
    public function tipoGasto(): BelongsTo { return $this->belongsTo(TipoGasto::class, 'id_tipo_gasto'); }
}
