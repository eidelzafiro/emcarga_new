<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entidad extends Model
{
    use SoftDeletes;

    protected $table = 'entidades';

    protected $fillable = [
        'codigo',
        'nombre',
        'abreviatura',
        'id_area',
        'activo',
        'direccion',
        'id_provincia',
        'id_municipio',
        'email',
        'nit',
        'licencia',
        'cta_unica',
        'cta_mn',
        'cta_me',
        'agencia',
        'minutos',
        'folio_fact',
        'almacenaje',
        'interruptos',
        'lugares',
        'pass_dias',
        'pass_cant_h',
        'notas_fact',
        'mora_dias',
        'mora_porciento',
        'cliente_fincimex_mn',
        'talon_versat',
        'vida_bateria',
        'vida_neum_nuevo',
        'vida_neum_rec',
        'vida_neum_admin',
        'disponible',
        'desactivar_disp',
        'alertas_mtto',
        'tipo_planificacion',
        'matriz',
        'tasas_aforo',
        'requisitos',
        'oper_carga',
        'descargas',
        'id_frecuencia',
        'id_sistema',
        'id_cajera',
        'id_parqueo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'disponible' => 'boolean',
            'desactivar_disp' => 'boolean',
            'alertas_mtto' => 'boolean',
            'almacenaje' => 'decimal:4',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'id_area');
    }

    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Provincia::class, 'id_provincia');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'id_municipio');
    }

    public function usuarios(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'id_entidad');
    }

    public function usuariosConAcceso(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'entidad_user')->withTimestamps();
    }
}
