<?php

namespace Database\Seeders;

use App\Models\CatalogoItem;
use Illuminate\Database\Seeder;

/**
 * Catálogo base de destinos de agregados (baterías, neumáticos, etc.).
 *
 * Desde la unificación (2026-08-23) viven en catalogo_items; el legacy
 * tec_destagregados usaba ids fijos (1=VEHICULO, 14=TALLER) que los
 * servicios referencian por origen_id (p. ej. BateriaService). Este seeder
 * garantiza que esos orígenes existan en installs frescos.
 */
class DestinoAgregadoSeeder extends Seeder
{
    private const BASE = [
        1 => 'VEHICULO',
        14 => 'TALLER',
        16 => 'NORMA 20',
        17 => 'BANCO CARGA',
        18 => 'MAT PRIMAS',
    ];

    public function run(): void
    {
        foreach (self::BASE as $origenId => $nombre) {
            CatalogoItem::updateOrCreate(
                ['tipo' => 'destinos_agregados', 'origen_id' => $origenId],
                [
                    'codigo' => (string) $origenId,
                    'nombre' => $nombre,
                    'activo' => true,
                ]
            );
        }
    }
}
