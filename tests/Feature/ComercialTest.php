<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComercialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function usuarioAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('SUPERADMIN');
        $user->password_temporal = false;
        $user->save();

        return $user;
    }

    public function test_clientes_index()
    {
        $this->actingAs($this->usuarioAdmin())->get(route('clientes.index'))->assertOk();
    }

    public function test_lugares_index()
    {
        $this->actingAs($this->usuarioAdmin())->get(route('lugares.index'))->assertOk();
    }

    public function test_distancias_index()
    {
        $this->actingAs($this->usuarioAdmin())->get(route('distancias.index'))->assertOk();
    }

    public function test_acuerdos_index()
    {
        $this->actingAs($this->usuarioAdmin())->get(route('acuerdos.index'))->assertOk();
    }

    public function test_solicitudes_index()
    {
        $this->actingAs($this->usuarioAdmin())->get(route('solicitudes.index'))->assertOk();
    }

    public function test_giros_index()
    {
        $this->actingAs($this->usuarioAdmin())->get(route('giros.index'))->assertOk();
    }

    public function test_cliente_store()
    {
        $response = $this->actingAs($this->usuarioAdmin())->post(route('clientes.store'), [
            'codigo' => 'CLI001',
            'nombre' => 'Cliente Test',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clientes', ['codigo' => 'CLI001']);
    }
}
