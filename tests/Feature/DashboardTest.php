<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class DashboardTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
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

    public function test_usuario_operativos_ve_dashboard_con_datos()
    {
        $user = User::factory()->create();
        $user->assignRole('OPERATIVOS');
        $user->password_temporal = false;
        $user->save();

        $response = $this
            ->withSession([
                'entidad_activa_id' => $user->id_entidad,
                'fecha_operaciones' => '2026-08-01',
            ])
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Operativos/Dashboard')
            ->has('totales')
            ->has('hrPorEstado')
            ->has('serie')
        );
    }

    public function test_usuario_comercial_ve_dashboard_con_datos()
    {
        $user = User::factory()->create();
        $user->assignRole('COMERCIAL');
        $user->password_temporal = false;
        $user->save();

        $response = $this
            ->withSession([
                'entidad_activa_id' => $user->id_entidad,
                'fecha_operaciones' => '2026-08-01',
            ])
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Comercial/Dashboard')
            ->has('facturacionPorConcepto')
            ->has('facturacionPorCliente')
            ->has('totales')
            ->has('serie')
        );
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
