<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Valla extends Model {
    protected $fillable = ['codigo', 'nombre', 'id_nave', 'activo'];
    protected function casts(): array { return ['activo' => 'boolean']; }
    public function nave(): BelongsTo { return $this->belongsTo(Nave::class, 'id_nave'); }
}
