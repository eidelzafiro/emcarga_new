<?php

namespace Tests\Feature;

use App\Models\Pizarra;
use App\Models\TipoVehiculo;
use App\Models\Tractivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PizarraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function tractivoValido(): Tractivo
    {
        $tipo = TipoVehiculo::create([
            'codigo' => 'TEST',
            'nombre' => 'Test',
        ]);

        return Tractivo::factory()->create([
            'id_tipo_vehiculo' => $tipo->id,
        ]);
    }

    private function usuarioValido(): User
    {
        $user = User::first();
        $user->password_temporal = false;
        $user->save();

        return $user;
    }

    public function test_pizarra_muestra_tablero_para_usuario_autenticado()
    {
        $user = $this->usuarioValido();

        $response = $this->actingAs($user)->get(route('pizarra.index'));

        $response->assertOk();
    }

    public function test_pizarra_devuelve_registros_json()
    {
        $user = $this->usuarioValido();

        $tractivo = $this->tractivoValido();
        Pizarra::create([
            'tractivo_id' => $tractivo->id,
            'conductor_id' => $user->id,
            'estado' => 'disponible',
            'ubicacion' => 'Terminal Central',
        ]);

        $response = $this->actingAs($user)->getJson(route('api.pizarra'));

        $response->assertOk();
        $response->assertJsonStructure(['registros']);
        $this->assertCount(1, $response->json('registros'));
        $this->assertEquals('disponible', $response->json('registros.0.estado'));
    }
}
