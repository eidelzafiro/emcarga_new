<?php

namespace Tests\Feature;

use App\Models\TipoContrato;
use App\Models\User;
use Tests\TestCase;

/**
 * Módulo RRHH (Fase 5.5): bolsa, plantilla, movimientos, salarios
 * y catálogos de tipos asociados.
 */
class RrhhTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function usuarioRechum(): User
    {
        $user = User::factory()->create();
        $user->assignRole('RECHUM');
        $user->password_temporal = false;
        $user->save();

        return $user;
    }

    public function test_bolsa_index(): void
    {
        $this->actingAs($this->usuarioRechum())
            ->get(route('bolsa.index'))
            ->assertOk();
    }

    public function test_historial_movimientos_index(): void
    {
        $this->actingAs($this->usuarioRechum())
            ->get(route('historial-movimientos.index'))
            ->assertOk();
    }

    public function test_catalogos_rrhh_index(): void
    {
        $rutas = [
            ['catalogo.index', ['tipo' => 'tipos_incidencias']],
            ['catalogo.index', ['tipo' => 'tipos_penalizaciones']],
            ['catalogo.index', ['tipo' => 'tipos_sistemas_pago']],
            ['catalogo.index', ['tipo' => 'tipos_pagos_adicionales']],
            'salarios-administrativos.index',
            'provincias.index',
            'municipios.index',
        ];

        $user = $this->usuarioRechum();

        foreach ($rutas as $ruta) {
            $nombre = is_array($ruta) ? $ruta[0] : $ruta;
            $params = is_array($ruta) ? ($ruta[1] ?? []) : [];
            $this->actingAs($user)
                ->get(route($nombre, $params))
                ->assertOk("La ruta {$nombre} debería ser accesible para RECHUM");
        }
    }

    public function test_modulo_cerrado_para_otros_perfiles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('CONTABILIDAD');
        $user->password_temporal = false;
        $user->save();

        $this->actingAs($user)->get(route('bolsa.index'))->assertForbidden();
    }
}
