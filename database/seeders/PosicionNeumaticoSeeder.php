<?php

namespace Database\Seeders;

use App\Models\CatalogoItem;
use Illuminate\Database\Seeder;

/**
 * Posiciones de neumáticos (viven en catalogo_items desde la unificación).
 * El legacy usaba ids fijos (5=REPUESTO) que NeumaticoService referencia
 * por origen_id; este seeder garantiza su existencia en installs frescos.
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
        foreach (self::BASE as $origenId => [$siglas, $descripcion]) {
            CatalogoItem::updateOrCreate(
                ['tipo' => 'posiciones_neumaticos', 'origen_id' => $origenId],
                [
                    'codigo' => $siglas,
                    'nombre' => $descripcion,
                    'activo' => true,
                    'extra' => json_encode(['descripcion' => $descripcion], JSON_UNESCAPED_UNICODE),
                ]
            );
        }
    }
}
