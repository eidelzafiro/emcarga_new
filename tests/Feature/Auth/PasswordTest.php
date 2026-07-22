<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        return User::factory()->create(['password' => 'Actual*123']);
    }

    public function test_muestra_el_formulario_de_cambio(): void
    {
        $this->actingAs($this->usuario())
            ->get('/perfil/cambiar-password')
            ->assertOk();
    }

    public function test_rechaza_password_actual_incorrecta(): void
    {
        $response = $this->actingAs($this->usuario())->put('/perfil/cambiar-password', [
            'password_actual' => 'incorrecta',
            'password' => 'Nueva*123',
            'password_confirmation' => 'Nueva*123',
        ]);

        $response->assertSessionHasErrors('password_actual');
    }

    public function test_rechaza_password_debil(): void
    {
        $response = $this->actingAs($this->usuario())->put('/perfil/cambiar-password', [
            'password_actual' => 'Actual*123',
            'password' => 'debil',
            'password_confirmation' => 'debil',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_rechaza_confirmacion_que_no_coincide(): void
    {
        $response = $this->actingAs($this->usuario())->put('/perfil/cambiar-password', [
            'password_actual' => 'Actual*123',
            'password' => 'Nueva*123',
            'password_confirmation' => 'Otra*123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_rechaza_reutilizar_la_password_actual(): void
    {
        $response = $this->actingAs($this->usuario())->put('/perfil/cambiar-password', [
            'password_actual' => 'Actual*123',
            'password' => 'Actual*123',
            'password_confirmation' => 'Actual*123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_rechaza_reutilizar_password_del_historico(): void
    {
        $user = $this->usuario();
        $user->passwordHistories()->create([
            'password' => bcrypt('Vieja*123'),
            'fecha_cambio' => now()->subMonth(),
        ]);

        $response = $this->actingAs($user)->put('/perfil/cambiar-password', [
            'password_actual' => 'Actual*123',
            'password' => 'Vieja*123',
            'password_confirmation' => 'Vieja*123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_cambio_exitoso_actualiza_hash_historico_y_bitacora(): void
    {
        $user = User::factory()->conPasswordTemporal()->create(['password' => 'Actual*123']);

        $response = $this->actingAs($user)->put('/perfil/cambiar-password', [
            'password_actual' => 'Actual*123',
            'password' => 'Nueva*123',
            'password_confirmation' => 'Nueva*123',
        ]);

        $response->assertRedirect('/dashboard');

        $user->refresh();
        $this->assertTrue(Hash::check('Nueva*123', $user->password));
        $this->assertFalse($user->password_temporal);
        $this->assertNotNull($user->fecha_cambio_password);
        $this->assertDatabaseHas('bitacora', ['user_id' => $user->id, 'accion' => 'cambio_password']);

        // El histórico conserva la contraseña anterior
        $this->assertTrue(
            $user->passwordHistories()->get()
                ->contains(fn ($h) => Hash::check('Actual*123', $h->password))
        );
    }
}
