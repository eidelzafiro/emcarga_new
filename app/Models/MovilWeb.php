<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovilWeb extends Model
{
    protected $table = 'movil_web';

    protected $fillable = ['fecha', 'hoja_ruta', 'km', 'combustible'];
}
