<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCargasReporte extends Model
{
    protected $table = 'tipos_cargas_reporte';

    protected $fillable = ['km1', 'km2', 'km3', 'km4'];
}
