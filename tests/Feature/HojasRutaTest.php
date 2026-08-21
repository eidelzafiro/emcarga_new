<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class HojasRutaTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function usuarioComercial(): User
    {
        $user = User::factory()->create();
        $user->assignRole('COMERCIAL');
        $user->password_temporal = false;
        $user->save();

        return $user;
    }

    public function test_hojas_ruta_index_ok()
    {
        $response = $this->actingAs($this->usuarioComercial())->get(route('hojas-ruta.index'));
        $response->assertOk();
    }

    public function test_hojas_ruta_index_filtra_estado()
    {
        $response = $this->actingAs($this->usuarioComercial())
            ->get(route('hojas-ruta.index', ['estado' => 'abiertas']));
        $response->assertOk();
    }
}
