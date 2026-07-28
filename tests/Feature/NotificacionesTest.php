<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\NotificacionSistema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificacionesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['SUPERADMIN', 'CONFIGURACIONES']))->first();
        $this->actingAs($this->admin);
    }

    public function test_usuario_puede_listar_sus_notificaciones()
    {
        $this->admin->notifications()->delete();
        $this->admin->notify(new NotificacionSistema('Test', 'Cuerpo'));

        $response = $this->getJson(route('notificaciones.index'));

        $response->assertOk();
        $response->assertJsonStructure(['items', 'pendientes']);
        $this->assertCount(1, $response->json('items'));
        $this->assertEquals(1, $response->json('pendientes'));
    }

    public function test_usuario_puede_marcar_notificacion_como_leida()
    {
        $this->admin->notify(new NotificacionSistema('Test', 'Cuerpo'));
        $notification = $this->admin->notifications()->first();

        $response = $this->postJson(route('notificaciones.leer', $notification->id));

        $response->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_usuario_puede_marcar_todas_como_leidas()
    {
        $this->admin->notify(new NotificacionSistema('Test 1', 'Cuerpo'));
        $this->admin->notify(new NotificacionSistema('Test 2', 'Cuerpo'));

        $response = $this->postJson(route('notificaciones.leer-todas'));

        $response->assertOk();
        $this->assertEquals(0, $this->admin->fresh()->unreadNotifications->count());
    }
}
