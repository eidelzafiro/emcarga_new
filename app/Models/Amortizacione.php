<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Amortizacione extends Model {
    protected $table = 'amortizaciones';
    protected $fillable = ['id_tractivo', 'amortizacion_mn', 'fecha'];
    protected function casts(): array { return ['fecha' => 'date', 'amortizacion_mn' => 'decimal:2']; }
    public function tractivo(): BelongsTo { return $this->belongsTo(Tractivo::class, 'id_tractivo'); }
}
