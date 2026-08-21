<?php

namespace Database\Seeders;

use App\Models\PosicionNeumatico;
use Illuminate\Database\Seeder;

/**
 * Catálogo base de posiciones de neumáticos.
 *
 * El legacy `tec_neumaticosposicion` usa ids fijos (5=REPUESTO) que el servicio
 * NeumaticoService referencia en código (POSICION_REPUESTO). Este seeder
 * garantiza que esos ids existan con idempotencia (upsert por id).
 */
class PosicionNeumaticoSeeder extends Seeder
{
    private const BASE = [
        1 => ['D.D.', 'EJE DELANTERO DERECHA'],
        2 => ['I.D.', 'EJE DELANTERO IZQUIERDA'],
        5 => ['REPUESTO', 'REPUESTO'],
    ];

    public function run(): void
    {
        foreach (self::BASE as $id => [$nombre, $descripcion]) {
            PosicionNeumatico::updateOrCreate(['id' => $id], [
                'codigo' => (string) $id,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'activo' => true,
            ]);
        }
    }
}