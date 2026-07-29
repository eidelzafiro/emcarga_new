<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entidad extends Model
{
    use SoftDeletes;

    protected $table = 'entidades';

    protected $fillable = [
        'parent_id',
        'es_matriz',
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
        'licencia_vencimiento',
        'licencia_activa',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'es_matriz' => 'boolean',
            'licencia_activa' => 'boolean',
            'licencia_vencimiento' => 'date',
            'disponible' => 'boolean',
            'desactivar_disp' => 'boolean',
            'alertas_mtto' => 'boolean',
            'almacenaje' => 'decimal:4',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
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

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'id_entidad');
    }

    public function usuariosConAcceso(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'entidad_user')->withTimestamps();
    }

    public function licenciaExpirada(): bool
    {
        return ! $this->licencia_activa || ($this->licencia_vencimiento && $this->licencia_vencimiento->isPast());
    }

    public function scopeParaEntidad(Builder $q, ?int $entidadId = null): Builder
    {
        $entidadId ??= auth()->user()?->id_entidad;
        if (! $entidadId) {
            return $q;
        }

        $ids = collect(self::subEntidadesIds($entidadId))->push($entidadId)->unique()->values()->all();

        return $q->whereIn('id', $ids);
    }

    public static function subEntidadesIds(int $entidadId): array
    {
        $ids = [];
        $children = self::where('parent_id', $entidadId)->get(['id']);

        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, self::subEntidadesIds($child->id));
        }

        return $ids;
    }
}
