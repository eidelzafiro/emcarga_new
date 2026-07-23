<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Devolucione extends Model
{
    protected $table = 'devoluciones';

    protected $fillable = ['id_carta_porte', 'id_cliente', 'id_cliente_mm', 'id_tractivo', 'id_empleado', 'fecha', 'aumento_flete_mn', 'aumento_flete_me', 'aumento_demora', 'aumento_salario', 'aumento_alquiler', 'aumento_izaje', 'disminucion_flete_mn', 'disminucion_flete_me', 'disminucion_demora', 'disminucion_salario', 'disminucion_alquiler', 'disminucion_izaje', 'observaciones'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
