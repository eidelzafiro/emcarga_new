<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class CartaPorte extends Model
{
    use SoftDeletes;

    protected $table = 'cartas_porte';

    protected $fillable = [
        'numero',
        'id_hoja_ruta',
        'id_solicitud',
        'id_tractivo',
        'id_arrastre',
        'id_cliente',
        'id_producto',
        'id_producto2',
        'id_tipo_carga',
        'id_tipo_carga2',
        'id_chofer',
        'id_chofer2',
        'id_lugar_origen',
        'id_lugar_destino',
        'fecha_emision',
        'fecha_parte',
        'fecha_recepcion',
        'toneladas',
        'peso1',
        'peso2',
        'distancia',
        'kms1',
        'kms2',
        'tarifa_km',
        'total_flete',
        'ingreso_mt',
        'flete_mt',
        'conduce',
        'estado',
        'cancelada',
        'fecha_cancelacion',
        'imprimir',
        'notas',
        'id_user',
        'id_user_recepcion',
        'id_user_cancelacion',
        'id_buque',
        'id_turno',
        'id_moneda',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'fecha_parte' => 'date',
            'fecha_recepcion' => 'date',
            'fecha_cancelacion' => 'datetime',
            'toneladas' => 'decimal:2',
            'peso1' => 'decimal:2',
            'peso2' => 'decimal:2',
            'distancia' => 'integer',
            'kms1' => 'integer',
            'kms2' => 'integer',
            'ingreso_mt' => 'decimal:2',
            'flete_mt' => 'decimal:2',
            'cancelada' => 'boolean',
            'imprimir' => 'boolean',
        ];
    }

    public function hojaRuta(): BelongsTo
    {
        return $this->belongsTo(HojasRuta::class, 'id_hoja_ruta');
    }

    public function aforos(): HasMany
    {
        return $this->hasMany(Aforo::class, 'id_carta_porte');
    }

    public function facturas(): HasManyThrough
    {
        return $this->hasManyThrough(
            Factura::class,
            Aforo::class,
            'id_carta_porte',
            'id',
            'id',
            'id_factura'
        );
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudesServicio::class, 'id_solicitud');
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function arrastre(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_arrastre');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }

    public function producto2(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto2');
    }

    public function tipoCarga(): BelongsTo
    {
        return $this->belongsTo(TipoCarga::class, 'id_tipo_carga');
    }

    public function tipoCarga2(): BelongsTo
    {
        return $this->belongsTo(TipoCarga::class, 'id_tipo_carga2');
    }

    public function chofer(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_chofer');
    }

    public function chofer2(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_chofer2');
    }

    public function lugarOrigen(): BelongsTo
    {
        return $this->belongsTo(Lugare::class, 'id_lugar_origen');
    }

    public function lugarDestino(): BelongsTo
    {
        return $this->belongsTo(Lugare::class, 'id_lugar_destino');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function userRecepcion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_recepcion');
    }

    public function userCancelacion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_cancelacion');
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'id_moneda');
    }

    public function buque(): BelongsTo
    {
        return $this->belongsTo(Buque::class, 'id_buque');
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'id_turno');
    }
}