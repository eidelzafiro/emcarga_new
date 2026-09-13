<?php

namespace Tests\Feature\Api;

use App\Models\Entidad;
use App\Models\Tractivo;
use App\Models\User;
use Tests\TestCase;

class TractivoApiTest extends TestCase
{
    private function tokenEntidad(User $user, int $entidadId): string
    {
        return $user->createToken('test', ["entidad:{$entidadId}"])->plainTextToken;
    }

    private function entidad(string $sufijo): Entidad
    {
        return Entidad::create([
            'nombre' => 'ENTIDAD '.$sufijo,
            'abreviatura' => 'E'.$sufijo,
            'activo' => true,
            'es_matriz' => false,
        ]);
    }

    public function test_index_solo_devuelve_la_entidad_del_token(): void
    {
        $entA = $this->entidad('A');
        $entB = $this->entidad('B');

        $tA = Tractivo::factory()->create(['id_entidad' => $entA->id, 'codigo' => 'AAA1', 'placa' => 'AAA001']);
        $tB = Tractivo::factory()->create(['id_entidad' => $entB->id, 'codigo' => 'BBB1', 'placa' => 'BBB001']);

        $user = User::factory()->create(['id_entidad' => $entA->id]);
        $token = $this->tokenEntidad($user, $entA->id);

        $response = $this->withToken($token)->getJson('/api/v1/tractivos');
        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($tA->id), 'Debe incluir el tractivo de su entidad.');
        $this->assertFalse($ids->contains($tB->id), 'NO debe incluir el de otra entidad.');
    }

    public function test_show_de_otra_entidad_devuelve_403(): void
    {
        $entA = $this->entidad('A');
        $entB = $this->entidad('B');

        $tB = Tractivo::factory()->create(['id_entidad' => $entB->id, 'codigo' => 'ZZZ1', 'placa' => 'ZZZ001']);

        $user = User::factory()->create(['id_entidad' => $entA->id]);
        $token = $this->tokenEntidad($user, $entA->id);

        $this->withToken($token)->getJson("/api/v1/tractivos/{$tB->id}")->assertForbidden();
    }

    public function test_index_requiere_token(): void
    {
        $this->getJson('/api/v1/tractivos')->assertUnauthorized();
    }
}
