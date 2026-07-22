<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ConsumoPiezas extends Model {
    protected $table = 'consumo_piezas';
    protected $fillable = ['folio', 'id_tractivo', 'id_concepto', 'cantidad', 'importe_mn', 'importe_me', 'fecha'];
    protected function casts(): array { return ['fecha' => 'date', 'cantidad' => 'decimal:2', 'importe_mn' => 'decimal:2', 'importe_me' => 'decimal:2']; }
    public function tractivo(): BelongsTo { return $this->belongsTo(Tractivo::class, 'id_tractivo'); }
}
