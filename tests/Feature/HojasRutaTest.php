<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HojasRutaTest extends TestCase
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

    public function test_hojas_ruta_index_ok()
    {
        $response = $this->actingAs($this->usuarioTecnica())->get(route('hojas-ruta.index'));
        $response->assertOk();
    }

    public function test_hojas_ruta_index_filtra_estado()
    {
        $response = $this->actingAs($this->usuarioTecnica())
            ->get(route('hojas-ruta.index', ['estado' => 'abiertas']));
        $response->assertOk();
    }
}
