<?php

namespace Tests\Feature;

use App\Models\TipoVehiculo;
use App\Models\Tractivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlotaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function usuarioTecnica(): User
    {
        $user = User::factory()->create();
        $user->assignRole('TECNICA');
        $user->password_temporal = false;
        $user->save();

        return $user;
    }

    private function tractivoValido(): Tractivo
    {
        $tipo = TipoVehiculo::create(['codigo' => 'TST', 'nombre' => 'Test']);

        return Tractivo::factory()->create(['id_tipo_vehiculo' => $tipo->id]);
    }

    public function test_motores_index()
    {
        $response = $this->actingAs($this->usuarioTecnica())->get(route('motores.index'));
        $response->assertOk();
    }

    public function test_cajas_index()
    {
        $response = $this->actingAs($this->usuarioTecnica())->get(route('cajas.index'));
        $response->assertOk();
    }

    public function test_diferenciales_index()
    {
        $response = $this->actingAs($this->usuarioTecnica())->get(route('diferenciales.index'));
        $response->assertOk();
    }

    public function test_baterias_index()
    {
        $response = $this->actingAs($this->usuarioTecnica())->get(route('baterias.index'));
        $response->assertOk();
    }

    public function test_neumaticos_index()
    {
        $response = $this->actingAs($this->usuarioTecnica())->get(route('neumaticos.index'));
        $response->assertOk();
    }

    public function test_lubricantes_index()
    {
        $response = $this->actingAs($this->usuarioTecnica())->get(route('lubricantes.index'));
        $response->assertOk();
    }

    public function test_otros_agregados_index()
    {
        $response = $this->actingAs($this->usuarioTecnica())->get(route('otros-agregados.index'));
        $response->assertOk();
    }

    public function test_energia_index()
    {
        $response = $this->actingAs($this->usuarioTecnica())->get(route('energia.index'));
        $response->assertOk();
    }

    public function test_motores_store()
    {
        $tractivo = $this->tractivoValido();

        $response = $this->actingAs($this->usuarioTecnica())->post(route('motores.store'), [
            'codigo' => 'MOT001',
            'descripcion' => 'Motor Test',
            'marca' => 'Cummins',
            'modelo' => 'ISX',
            'numero_serie' => 'NS123456',
            'id_tractivo' => $tractivo->id,
            'estado' => 'disponible',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('motores', ['codigo' => 'MOT001']);
    }

    public function test_baterias_store()
    {
        $tractivo = $this->tractivoValido();

        $response = $this->actingAs($this->usuarioTecnica())->post(route('baterias.store'), [
            'folio' => 'BAT001',
            'marca' => 'Optima',
            'id_tractivo' => $tractivo->id,
            'fecha_instalacion' => '2026-07-22',
            'estado' => 'activa',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('baterias', ['folio' => 'BAT001']);
    }

    public function test_neumaticos_store()
    {
        $tractivo = $this->tractivoValido();

        $response = $this->actingAs($this->usuarioTecnica())->post(route('neumaticos.store'), [
            'folio' => 'NEU001',
            'marca' => 'Michelin',
            'medida' => '12R22.5',
            'id_tractivo' => $tractivo->id,
            'kilometraje' => 0,
            'estado' => 'activo',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('neumaticos', ['folio' => 'NEU001']);
    }

    public function test_lubricantes_store()
    {
        $tractivo = $this->tractivoValido();

        $response = $this->actingAs($this->usuarioTecnica())->post(route('lubricantes.store'), [
            'folio' => 'LUB001',
            'id_tractivo' => $tractivo->id,
            'cantidad' => 10.5,
            'unidad' => 'litros',
            'fecha' => '2026-07-22',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('consumo_lubricantes', ['folio' => 'LUB001']);
    }
}
