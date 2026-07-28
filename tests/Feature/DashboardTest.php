<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_usuario_autenticado_puede_ver_dashboard()
    {
        $user = User::whereHas('roles', fn ($q) => $q->where('name', 'SUPERADMIN'))->first();
        $user->password_temporal = false;
        $user->save();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertOk();
    }

    public function test_api_kpis_devuelve_indicadores()
    {
        $user = User::first();
        $user->password_temporal = false;
        $user->save();

        $response = $this->actingAs($user)->getJson(route('api.kpis'));

        $response->assertOk();
        $response->assertJsonStructure(['kpis']);
        $this->assertGreaterThan(0, count($response->json('kpis')));
    }
}
