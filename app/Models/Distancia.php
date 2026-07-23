<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Distancia extends Model {
    protected $table = 'distancias';
    protected $fillable = ['id_lugar_origen', 'id_lugar_destino', 'distancia_km', 'tiempo_horas', 'activo'];
    protected function casts(): array { return ['activo' => 'boolean', 'distancia_km' => 'decimal:2']; }
    public function origen(): BelongsTo { return $this->belongsTo(Lugare::class, 'id_lugar_origen'); }
    public function destino(): BelongsTo { return $this->belongsTo(Lugare::class, 'id_lugar_destino'); }
}
