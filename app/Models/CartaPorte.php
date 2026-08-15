<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class CartaPorte extends Model
{
    use SoftDeletes;

    protected $table = 'cartas_porte';

    protected $fillable = [
        'numero',
        'id_hoja_ruta',
        'id_solicitud',
        'fecha_emision',
        'fecha_parte',
        'fecha_recepcion',
        'toneladas',
        'peso1',
        'peso2',
        'distancia',
        'conduce',
        'estado',
        'cancelada',
        'fecha_cancelacion',
        'imprimir',
        'notas',
        'id_user',
        'id_user_recepcion',
        'id_user_cancelacion',
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

    /**
     * Equipo y choferes se derivan de la hoja de ruta (Fase 4d).
     * Carta → HR → Tractivo/Bolsa.
     */
    public function tractivo(): HasOneThrough
    {
        return $this->hasOneThrough(Tractivo::class, HojasRuta::class, 'id', 'id', 'id_hoja_ruta', 'id_tractivo');
    }

    public function arrastre(): HasOneThrough
    {
        return $this->hasOneThrough(Tractivo::class, HojasRuta::class, 'id', 'id', 'id_hoja_ruta', 'id_arrastre');
    }

    public function chofer(): HasOneThrough
    {
        return $this->hasOneThrough(Bolsa::class, HojasRuta::class, 'id', 'id', 'id_hoja_ruta', 'id_chofer');
    }

    public function chofer2(): HasOneThrough
    {
        return $this->hasOneThrough(Bolsa::class, HojasRuta::class, 'id', 'id', 'id_hoja_ruta', 'id_chofer2');
    }

    /**
     * Cliente, productos y tipos de carga se derivan de la solicitud (Fase 4d).
     * Carta → Solicitud → Cliente/Producto/TipoCarga.
     */
    public function cliente(): HasOneThrough
    {
        return $this->hasOneThrough(Cliente::class, SolicitudesServicio::class, 'id', 'id', 'id_solicitud', 'id_cliente');
    }

    public function producto(): HasOneThrough
    {
        return $this->hasOneThrough(Producto::class, SolicitudesServicio::class, 'id', 'id', 'id_solicitud', 'id_producto');
    }

    public function producto2(): HasOneThrough
    {
        return $this->hasOneThrough(Producto::class, SolicitudesServicio::class, 'id', 'id', 'id_solicitud', 'id_producto2');
    }

    public function tipoCarga(): HasOneThrough
    {
        return $this->hasOneThrough(TipoCarga::class, SolicitudesServicio::class, 'id', 'id', 'id_solicitud', 'id_tipo_carga');
    }

    public function tipoCarga2(): HasOneThrough
    {
        return $this->hasOneThrough(TipoCarga::class, SolicitudesServicio::class, 'id', 'id', 'id_solicitud', 'id_tipo_carga2');
    }

    public function lugarOrigen(): HasOneThrough
    {
        return $this->hasOneThrough(Lugare::class, SolicitudesServicio::class, 'id', 'id', 'id_solicitud', 'id_lugar_origen');
    }

    public function lugarDestino(): HasOneThrough
    {
        return $this->hasOneThrough(Lugare::class, SolicitudesServicio::class, 'id', 'id', 'id_solicitud', 'id_lugar_destino');
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

    public function moneda(): HasOneThrough
    {
        return $this->hasOneThrough(Moneda::class, SolicitudesServicio::class, 'id', 'id', 'id_solicitud', 'id_moneda');
    }
}
