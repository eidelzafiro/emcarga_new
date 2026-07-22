<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NeumaticosRotura extends Model
{
    protected $table = 'neumaticos_roturas';

    protected $fillable = ['id_neumatico', 'id_tipo_causa', 'fecha', 'descripcion'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }

    public function neumatico(): BelongsTo
    {
        return $this->belongsTo(Neumatico::class, 'id_neumatico');
    }

    public function tipoCausa(): BelongsTo
    {
        return $this->belongsTo(TipoCausa::class, 'id_tipo_causa');
    }
}
