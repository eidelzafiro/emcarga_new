<?php

namespace Tests\Feature;

use App\Models\TipoVehiculo;
use App\Models\Tractivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TallerTest extends TestCase
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

    public function test_taller_index()
    {
        $response = $this->actingAs($this->usuarioTecnica())->get(route('taller.index'));
        $response->assertOk();
    }
}
