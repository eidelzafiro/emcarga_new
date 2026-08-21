<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de reportes legacy (tabla `reportes` de la BD `emcarga`).
 * Es la fuente de verdad de los reportes que se usan, su agrupación (`tipo`),
 * la variable de configuración (`variable`) y los perfiles a los que se muestran.
 */
class ReporteLegacy extends Model
{
    protected $connection = 'legacy';

    protected $table = 'reportes';

    protected $primaryKey = 'idreporte';

    public $timestamps = false;

    protected $fillable = [
        'nombreporte', 'controlador', 'tipo', 'variable',
        'rechum', 'com', 'cont', 'conte', 'tec', 'teccom',
    ];
}
