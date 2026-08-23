<?php

namespace Tests\Feature\Catalogo;

use App\Support\TiposEquiposNormalizer;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regla de negocio documentada en App\Support\TiposEquiposNormalizer:
 * consolidación de tipos de equipos duplicados del legacy.
 */
class TiposEquiposNormalizerTest extends TestCase
{
    private int $secuencia = 9000;

    protected function tearDown(): void
    {
        // Limpieza defensiva (TestCase revierte con transacciones, esto es extra)
        DB::table('tipos_equipos')->where('id', '>=', 9000)->delete();
        DB::table('catalogo_items')->where('tipo', 'tipos_equipos')->where('origen_id', '>=', 9000)->delete();

        parent::tearDown();
    }

    private function crear(string $nombre, array $extra = []): int
    {
        $id = ++$this->secuencia;
        DB::table('tipos_equipos')->insert(array_merge([
            'id' => $id,
            'nombre' => $nombre,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $extra));

        return $id;
    }

    public function test_fusiona_auto_ligero_y_auto_especial_en_auto(): void
    {
        $ligero = $this->crear('AUTO LIGERO');
        $especial = $this->crear('AUTO ESPECIAL');

        TiposEquiposNormalizer::normalizar();

        $this->assertDatabaseHas('tipos_equipos', ['id' => $ligero, 'nombre' => 'AUTO']);
        $this->assertDatabaseMissing('tipos_equipos', ['id' => $especial]);
        $this->assertDatabaseHas('catalogo_items', [
            'tipo' => 'tipos_equipos',
            'origen_id' => $ligero,
            'nombre' => 'AUTO',
        ]);
        $this->assertDatabaseMissing('catalogo_items', [
            'tipo' => 'tipos_equipos',
            'origen_id' => $especial,
        ]);
    }

    public function test_elimina_duplicado_exacto_de_omnibus(): void
    {
        $primero = $this->crear('OMNIBUS');
        $segundo = $this->crear('OMNIBUS');

        TiposEquiposNormalizer::normalizar();

        $this->assertDatabaseHas('tipos_equipos', ['id' => $primero, 'nombre' => 'OMNIBUS']);
        $this->assertDatabaseMissing('tipos_equipos', ['id' => $segundo]);
    }

    public function test_reasigna_fichas_del_tipo_absorbido(): void
    {
        $cisterna = $this->crear('CISTERNA');
        $agua = $this->crear('CAMION CISTERNA AGUA');

        $fichaId = DB::table('tipos_arrastres')->insertGetId([
            'nombre' => 'FICHA PRUEBA NORMALIZER',
            'id_tipo_equipo' => $agua,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TiposEquiposNormalizer::normalizar();

        $this->assertSame($cisterna, (int) DB::table('tipos_arrastres')->where('id', $fichaId)->value('id_tipo_equipo'));
        $this->assertDatabaseMissing('tipos_equipos', ['id' => $agua]);

        DB::table('tipos_arrastres')->where('id', $fichaId)->delete();
    }

    public function test_renombra_volteo_conservando_su_imagen(): void
    {
        $volteo = $this->crear('CAMION VOLTEO', ['imagen' => 'tipos_equipos/camion_volteo.jpg']);
        $sr = $this->crear('S/R VOLTEO');

        TiposEquiposNormalizer::normalizar();

        $fila = DB::table('tipos_equipos')->where('id', $volteo)->first();
        $this->assertSame('VOLTEO', $fila->nombre);
        $this->assertSame('tipos_equipos/camion_volteo.jpg', $fila->imagen);
        $this->assertDatabaseMissing('tipos_equipos', ['id' => $sr]);
    }
}
