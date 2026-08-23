<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    /**
     * PNG válido 1x1 embebido (el contenedor no tiene GD para generar imágenes).
     */
    private function imagen(string $nombre = 'foto.png'): UploadedFile
    {
        $contenido = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        );

        return UploadedFile::fake()->createWithContent($nombre, $contenido);
    }

    public function test_muestra_el_formulario_de_perfil(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Profile/Edit'));
    }

    public function test_invitado_no_accede_al_perfil(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }

    public function test_actualiza_nombre_apellidos_y_correo(): void
    {
        $usuario = User::factory()->create(['email' => 'viejo@nauta.cu']);

        $this->actingAs($usuario)
            ->put(route('profile.update'), [
                'name' => 'Eidel',
                'apellidos' => 'Miranda Pérez',
                'email' => 'eidel.miranda@gmail.com',
            ])
            ->assertRedirect();

        $this->assertSame('Eidel', $usuario->fresh()->name);
        $this->assertSame('Miranda Pérez', $usuario->fresh()->apellidos);
        $this->assertSame('eidel.miranda@gmail.com', $usuario->fresh()->email);
    }

    public function test_rechaza_correo_duplicado_de_otro_usuario(): void
    {
        User::factory()->create(['email' => 'ocupado@gmail.com']);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->put(route('profile.update'), [
                'name' => $usuario->name,
                'email' => 'ocupado@gmail.com',
            ])
            ->assertSessionHasErrors('email');

        $this->assertNotSame('ocupado@gmail.com', $usuario->fresh()->email);
    }

    public function test_permite_conservar_su_propio_correo(): void
    {
        $usuario = User::factory()->create(['email' => 'mio@gmail.com']);

        $this->actingAs($usuario)
            ->put(route('profile.update'), [
                'name' => $usuario->name,
                'email' => 'mio@gmail.com',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_subir_avatar_guarda_ruta_relativa(): void
    {
        Storage::fake('public');
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post(route('profile.avatar'), [
                'avatar' => $this->imagen('foto.png'),
            ])
            ->assertSessionHasNoErrors();

        $ruta = $usuario->fresh()->avatar;
        $this->assertNotNull($ruta);
        $this->assertStringStartsWith('avatars/', $ruta);
        Storage::disk('public')->assertExists($ruta);
    }

    public function test_reemplazar_avatar_elimina_el_anterior(): void
    {
        Storage::fake('public');
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->post(route('profile.avatar'), [
            'avatar' => $this->imagen('una.png'),
        ]);
        $primera = $usuario->fresh()->avatar;

        $this->actingAs($usuario)->post(route('profile.avatar'), [
            'avatar' => $this->imagen('dos.png'),
        ]);
        $segunda = $usuario->fresh()->avatar;

        Storage::disk('public')->assertMissing($primera);
        Storage::disk('public')->assertExists($segunda);
    }

    public function test_rechaza_archivos_que_no_son_imagen(): void
    {
        Storage::fake('public');
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post(route('profile.avatar'), [
                'avatar' => UploadedFile::fake()->create('mal.pdf', 100),
            ])
            ->assertSessionHasErrors('avatar');

        $this->assertNull($usuario->fresh()->avatar);
    }

    public function test_elimina_el_avatar(): void
    {
        Storage::fake('public');
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->post(route('profile.avatar'), [
            'avatar' => $this->imagen('x.png'),
        ]);
        $ruta = $usuario->fresh()->avatar;

        $this->actingAs($usuario)
            ->delete(route('profile.avatar.delete'))
            ->assertSessionHasNoErrors();

        $this->assertNull($usuario->fresh()->avatar);
        Storage::disk('public')->assertMissing($ruta);
    }
}
