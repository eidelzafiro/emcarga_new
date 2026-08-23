<?php

namespace Tests\Feature\Catalogo;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Vista de tarjetas y subida de imagen para tipos de equipos
 * (catálogo unificado, tipo tipos_equipos).
 */
class TiposEquiposImagenTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::whereHas('roles', fn ($q) => $q->where('name', 'SUPERADMIN'))->first()
            ?? User::factory()->create();
        $this->admin->assignRole('SUPERADMIN');
        // Evitar el redirect del middleware de password temporal
        $this->admin->forceFill(['password_temporal' => false])->save();
        Storage::fake('public');
    }

    /**
     * Crea un tipo de equipo legacy con su espejo en el catálogo unificado.
     */
    private function crearPar(string $nombre): object
    {
        $origenId = DB::table('tipos_equipos')->insertGetId([
            'nombre' => $nombre,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('catalogo_items')->insert([
            'tipo' => 'tipos_equipos',
            'origen_id' => $origenId,
            'nombre' => $nombre,
            'activo' => true,
            'extra' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('catalogo_items')
            ->where('tipo', 'tipos_equipos')->where('origen_id', $origenId)->first();
    }

    /**
     * PNG 1x1 válido (el contenedor no tiene GD).
     */
    private function imagen(string $nombre = 'equipo.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $nombre,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==')
        );
    }

    public function test_index_envia_imagen_como_url_storage(): void
    {
        DB_TABLE:
            DB::table('catalogo_items')->insert([
                'tipo' => 'tipos_equipos',
                'origen_id' => 999001,
                'nombre' => 'PRUEBA VISUAL',
                'activo' => true,
                'extra' => json_encode(['imagen' => 'tipos_equipos/prueba.jpg']),
            ]);

        $this->actingAs($this->admin)
            ->get(route('catalogo.index', ['tipo' => 'tipos_equipos']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Catalogo/Index')
                ->where('items.data.0.imagen', function ($valor) {
                    // La ruta relativa se convierte en URL servible (evita el 405)
                    return is_string($valor) && str_starts_with($valor, '/storage/tipos_equipos/');
                }));

        DB::table('catalogo_items')
            ->where('tipo', 'tipos_equipos')->where('origen_id', 999001)->delete();
    }

    public function test_crear_equipo_con_imagen_guarda_archivo_y_extra(): void
    {
        $this->actingAs($this->admin)
            ->post(route('catalogo.store', ['tipo' => 'tipos_equipos']), [
                'nombre' => 'EQUIPO IMAGEN TEST',
                'imagen_archivo' => $this->imagen(),
            ])
            ->assertSessionHasNoErrors();

        $item = DB::table('catalogo_items')
            ->where('tipo', 'tipos_equipos')
            ->where('nombre', 'EQUIPO IMAGEN TEST')
            ->first();

        $this->assertNotNull($item);
        $ruta = json_decode($item->extra, true)['imagen'] ?? null;
        $this->assertStringStartsWith('tipos_equipos/', $ruta);
        Storage::disk('public')->assertExists($ruta);

        // Limpieza
        Storage::disk('public')->delete($ruta);
        DB::table('catalogo_items')->where('id', $item->id)->delete();
    }

    public function test_editar_reemplaza_imagen_y_elimina_anterior(): void
    {
        $item = $this->crearPar('EQUIPO REEMPLAZO');
        $anterior = 'tipos_equipos/reemplazo-vieja.png';
        Storage::disk('public')->put($anterior, 'contenido-anterior');
        DB::table('catalogo_items')->where('id', $item->id)->update([
            'extra' => json_encode(['imagen' => $anterior]),
        ]);

        $this->actingAs($this->admin)
            ->post(route('catalogo.update', ['tipo' => 'tipos_equipos', 'id' => $item->id]), [
                '_method' => 'PUT',
                'nombre' => 'EQUIPO REEMPLAZO',
                'imagen_archivo' => $this->imagen('nuevo.png'),
            ])
            ->assertSessionHasNoErrors();

        $nueva = json_decode(
            DB::table('catalogo_items')->where('id', $item->id)->value('extra'),
            true
        )['imagen'];

        $this->assertNotSame($anterior, $nueva);
        Storage::disk('public')->assertMissing($anterior);
        Storage::disk('public')->assertExists($nueva);

        // Limpieza
        DB::table('catalogo_items')->where('id', $item->id)->delete();
        DB::table('tipos_equipos')->where('id', $item->origen_id)->delete();
    }

    public function test_rechaza_archivo_que_no_es_imagen(): void
    {
        $this->actingAs($this->admin)
            ->post(route('catalogo.store', ['tipo' => 'tipos_equipos']), [
                'nombre' => 'EQUIPO MAL ARCHIVO',
                'imagen_archivo' => UploadedFile::fake()->create('virus.exe', 50),
            ])
            ->assertSessionHasErrors('imagen_archivo');

        $this->assertDatabaseMissing('catalogo_items', [
            'tipo' => 'tipos_equipos',
            'nombre' => 'EQUIPO MAL ARCHIVO',
        ]);
    }

    public function test_actualiza_tabla_legacy_al_editar_con_origen(): void
    {
        $item = $this->crearPar('EQUIPO LEGACY SYNC');

        $this->actingAs($this->admin)
            ->post(route('catalogo.update', ['tipo' => 'tipos_equipos', 'id' => $item->id]), [
                '_method' => 'PUT',
                'nombre' => 'EQUIPO LEGACY SYNC',
                'imagen_archivo' => $this->imagen('legacy-sync.png'),
            ])
            ->assertSessionHasNoErrors();

        $nueva = json_decode(
            DB::table('catalogo_items')->where('id', $item->id)->value('extra'),
            true
        )['imagen'];
        $legacy = DB::table('tipos_equipos')
            ->where('id', $item->origen_id)->first();

        $this->assertNotNull($legacy);
        $this->assertSame($nueva, $legacy->imagen, 'La tabla legacy debe reflejar la nueva imagen');

        DB::table('catalogo_items')->where('id', $item->id)->delete();
        DB::table('tipos_equipos')->where('id', $item->origen_id)->delete();
    }
}
