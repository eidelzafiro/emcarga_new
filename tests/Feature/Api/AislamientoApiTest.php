<?php

namespace Tests\Feature\Api;

use App\Models\Cargo;
use App\Models\Cliente;
use App\Models\ControlLubricante;
use App\Models\Entidad;
use App\Models\OrdenesTaller;
use App\Models\Tarjeta;
use App\Models\User;
use Tests\TestCase;

/**
 * Aislamiento por entidad de los endpoints de negocio de la API v1: un token
 * con `entidad:{id}` solo debe ver los registros de esa entidad (o sus hijas
 * si es matriz).
 */
class AislamientoApiTest extends TestCase
{
    private function entidad(string $sufijo): Entidad
    {
        return Entidad::create([
            'nombre' => 'ENTIDAD '.$sufijo,
            'abreviatura' => 'E'.$sufijo,
            'activo' => true,
            'es_matriz' => false,
        ]);
    }

    private function token(User $user, int $entidadId): string
    {
        return $user->createToken('test', ["entidad:{$entidadId}"])->plainTextToken;
    }

    public function test_clientes_solo_de_la_entidad_del_token(): void
    {
        $entA = $this->entidad('A');
        $entB = $this->entidad('B');

        $cA = Cliente::create(['nombre' => 'CLIENTE A', 'id_entidad' => $entA->id]);
        $cB = Cliente::create(['nombre' => 'CLIENTE B', 'id_entidad' => $entB->id]);

        $user = User::factory()->create(['id_entidad' => $entA->id]);
        $token = $this->token($user, $entA->id);

        $ids = collect($this->withToken($token)->getJson('/api/v1/clientes')->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($cA->id));
        $this->assertFalse($ids->contains($cB->id));
    }

    public function test_tarjetas_solo_de_la_entidad_del_token(): void
    {
        $entA = $this->entidad('A');
        $entB = $this->entidad('B');

        $tA = Tarjeta::forceCreate(['numero' => 'T-A', 'descripcion' => 'A', 'id_entidad' => $entA->id]);
        $tB = Tarjeta::forceCreate(['numero' => 'T-B', 'descripcion' => 'B', 'id_entidad' => $entB->id]);

        $user = User::factory()->create(['id_entidad' => $entA->id]);
        $token = $this->token($user, $entA->id);

        $ids = collect($this->withToken($token)->getJson('/api/v1/tarjetas')->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($tA->id));
        $this->assertFalse($ids->contains($tB->id));
    }

    public function test_show_de_otra_entidad_devuelve_403(): void
    {
        $entA = $this->entidad('A');
        $entB = $this->entidad('B');

        $tB = Tarjeta::forceCreate(['numero' => 'T-B', 'descripcion' => 'B', 'id_entidad' => $entB->id]);

        $user = User::factory()->create(['id_entidad' => $entA->id]);
        $token = $this->token($user, $entA->id);

        $this->withToken($token)->getJson("/api/v1/tarjetas/{$tB->id}")->assertForbidden();
    }

    public function test_cargos_solo_de_la_entidad_del_token(): void
    {
        $entA = $this->entidad('A');
        $entB = $this->entidad('B');

        $cA = Cargo::create(['nombre' => 'CARGO A', 'id_entidad' => $entA->id]);
        $cB = Cargo::create(['nombre' => 'CARGO B', 'id_entidad' => $entB->id]);

        $user = User::factory()->create(['id_entidad' => $entA->id]);
        $token = $this->token($user, $entA->id);

        $ids = collect($this->withToken($token)->getJson('/api/v1/cargos')->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($cA->id));
        $this->assertFalse($ids->contains($cB->id));
    }

    public function test_control_lubricantes_solo_de_la_entidad_del_token(): void
    {
        $entA = $this->entidad('A');
        $entB = $this->entidad('B');

        $rA = ControlLubricante::create(['id_entidad' => $entA->id, 'fecha_cambio' => now()->toDateString()]);
        $rB = ControlLubricante::create(['id_entidad' => $entB->id, 'fecha_cambio' => now()->toDateString()]);

        $user = User::factory()->create(['id_entidad' => $entA->id]);
        $token = $this->token($user, $entA->id);

        $ids = collect($this->withToken($token)->getJson('/api/v1/taller/control-lubricantes')->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($rA->id));
        $this->assertFalse($ids->contains($rB->id));
    }

    public function test_ordenes_taller_solo_de_la_entidad_del_token(): void
    {
        $entA = $this->entidad('A');
        $entB = $this->entidad('B');

        $oA = OrdenesTaller::create(['numero' => 'OT-A', 'estado' => 'abierta', 'id_entidad' => $entA->id, 'fecha_ingreso' => now()->toDateString()]);
        $oB = OrdenesTaller::create(['numero' => 'OT-B', 'estado' => 'abierta', 'id_entidad' => $entB->id, 'fecha_ingreso' => now()->toDateString()]);

        $user = User::factory()->create(['id_entidad' => $entA->id]);
        $token = $this->token($user, $entA->id);

        $ids = collect($this->withToken($token)->getJson('/api/v1/taller/ordenes')->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($oA->id));
        $this->assertFalse($ids->contains($oB->id));
    }

    public function test_contexto_entidad_rechaza_entidad_no_permitida(): void
    {
        $entA = $this->entidad('A');
        $entB = $this->entidad('B');

        $user = User::factory()->create(['id_entidad' => $entA->id]);
        $token = $this->token($user, $entA->id);

        $this->withToken($token)
            ->postJson('/api/v1/contexto/entidad', ['id_entidad' => $entB->id])
            ->assertForbidden();
    }

    public function test_contexto_fecha_cambia_el_mes_del_token(): void
    {
        $entA = $this->entidad('A');
        $user = User::factory()->create(['id_entidad' => $entA->id]);
        $token = $this->token($user, $entA->id);

        $this->withToken($token)
            ->postJson('/api/v1/contexto/fecha', ['fecha' => '2026-01'])
            ->assertOk()
            ->assertJsonPath('fecha_operaciones', '2026-01-01');
    }
}
