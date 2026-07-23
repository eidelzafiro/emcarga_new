<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracioneModelo extends Model
{
    protected $table = 'configuraciones_modelo';

    protected $fillable = ['nombre', 'id_tipo_modelo', 'set_x', 'set_y', 'letra', 'id_user'];
}
