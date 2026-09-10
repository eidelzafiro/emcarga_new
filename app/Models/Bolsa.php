<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bolsa extends Model
{
    use SoftDeletes;

    protected $table = 'bolsa';

    protected $fillable = [
        'ci',
        'nombre',
        'apellidos',
        'sexo',
        'color_piel',
        'nivel_educacional',
        'estado_civil',
        'ubicacion_defensa',
        'fecha_nacimiento',
        'direccion',
        'telefono',
        'email',
        'id_cargo',
        'id_area',
        'id_entidad',
        'activo',
        'versat',
        'garantia',
        'falta',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'falta' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'id_cargo');
    }

    /** Documentos del chofer (licencia, chequeo médico, recalificación, psicométrico). */
    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoChofer::class, 'id_bolsa');
    }

    /** Documento vigente de un tipo (LICENCIA, CHEQUEO_MEDICO, ...). */
    public function documento(string $tipo): ?DocumentoChofer
    {
        return $this->documentos()->where('tipo', $tipo)->orderByDesc('id')->first();
    }

    /** Categorías de licencia del chofer (A, B, C, D, E...). */
    public function licenciaCategorias(): HasMany
    {
        return $this->hasMany(LicenciaCategoria::class, 'id_bolsa');
    }

    /** ¿El chofer tiene licencia de conducción (documento LICENCIA vigente)? */
    public function getTieneLicenciaAttribute(): bool
    {
        return $this->documentos()->where('tipo', 'LICENCIA')->exists();
    }

    /**
     * Movimiento VIGENTE del trabajador (origen 'mov', sin fbaja). El cargo y
     * el área del trabajador salen del movimiento (plantilla), NO de la bolsa
     * (paridad legacy rh_movimientos → rh_plantilla).
     */
    public function movimientoVigente()
    {
        return $this->hasOne(MovimientoRrhh::class, 'id_bolsa')
            ->where('origen', 'mov')
            ->whereNull('fbaja')
            ->with('plantilla.cargo', 'plantilla.area');
    }

    /** Cargo del trabajador vía movimiento vigente (plantilla). Cacheado por instancia. */
    public function cargoActual()
    {
        if (! array_key_exists('cargoActual', $this->relations)) {
            $this->relations['cargoActual'] = $this->movimientoVigente()->first()?->plantilla?->cargo
                ?? $this->belongsTo(Cargo::class, 'id_cargo')->getResults();
        }

        return $this->relations['cargoActual'];
    }

    /** Área del trabajador vía movimiento vigente (plantilla). Cacheado por instancia. */
    public function areaActual()
    {
        if (! array_key_exists('areaActual', $this->relations)) {
            $this->relations['areaActual'] = $this->movimientoVigente()->first()?->plantilla?->area
                ?? $this->belongsTo(Area::class, 'id_area')->getResults();
        }

        return $this->relations['areaActual'];
    }

    public function sexoCatalogo(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'sexo');
    }

    public function colorPiel(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'color_piel');
    }

    public function nivelEducacional(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'nivel_educacional');
    }

    public function estadoCivil(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'estado_civil');
    }

    public function ubicacionDefensa(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'ubicacion_defensa');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'id_area');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }

    public function movimientosRrhh(): HasMany
    {
        return $this->hasMany(MovimientoRrhh::class, 'id_bolsa');
    }

    public function hojasRuta(): HasMany
    {
        return $this->hasMany(HojasRuta::class, 'id_chofer');
    }

    public function cartasPorte(): HasMany
    {
        return $this->hasMany(CartaPorte::class, 'id_chofer');
    }

    public function cartasPorteChofer2(): HasMany
    {
        return $this->hasMany(CartaPorte::class, 'id_chofer2');
    }

    protected $appends = ['nombrecompleto'];

    public function getNombrecompletoAttribute(): string
    {
        return trim($this->nombre.' '.$this->apellidos);
    }
}
