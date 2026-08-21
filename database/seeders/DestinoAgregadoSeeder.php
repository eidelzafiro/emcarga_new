<?php

namespace Database\Seeders;

use App\Models\DestinoAgregado;
use Illuminate\Database\Seeder;

/**
 * Catálogo base de destinos de agregados (baterías, neumáticos, etc.).
 *
 * El legacy `tec_destagregados` usa ids fijos (1=VEHICULO, 14=TALLER) que los
 * servicios referencian en código (p. ej. BateriaService::DESTINO_VEHICULO).
 * Este seeder garantiza que esos ids existan con idempotencia (upsert por id),
 * sin pisar los datos migrados por el ETL.
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
        foreach (self::BASE as $id => $nombre) {
            DestinoAgregado::updateOrCreate(['id' => $id], [
                'codigo' => (string) $id,
                'nombre' => $nombre,
                'activo' => true,
            ]);
        }
    }
}