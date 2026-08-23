<?php

namespace Tests\Feature\Auth;

use App\Mail\RestablecerPassword;
use App\Models\User;
use App\Support\MailRouter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    public function test_muestra_el_formulario_de_solicitud(): void
    {
        $this->get('/olvide-password')->assertOk();
    }

    public function test_muestra_el_formulario_de_reset_con_token(): void
    {
        $this->get('/restablecer-password/token-de-prueba?email=a@b.cu')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/ResetPassword')
                ->where('token', 'token-de-prueba')
                ->where('email', 'a@b.cu'));
    }

    public function test_correo_cu_se_enruta_por_nacional_y_otros_por_gmail(): void
    {
        // Con ambos transportes configurados (Gmail exige user + app password)
        config([
            'mail.mailers.nacional.host' => 'smtp.nacional.cu',
            'mail.mailers.gmail.username' => 'app@correo.com',
            'mail.mailers.gmail.password' => 'secret-app-password',
        ]);
        $this->assertSame('nacional', MailRouter::paraEmail('usuario@emcarga.cu'));
        $this->assertSame('gmail', MailRouter::paraEmail('usuario@gmail.com'));

        // Sin host del transporte correspondiente: cae al mailer por defecto
        config(['mail.mailers.nacional.host' => null]);
        $this->assertSame(config('mail.default'), MailRouter::paraEmail(null));
        $this->assertSame(config('mail.default'), MailRouter::paraEmail('usuario@emcarga.cu'));

        // Host de Gmail por defecto SIN credenciales: también cae al default
        // (evita el 530 Authentication Required en producción sin configurar).
        config(['mail.mailers.gmail.username' => null]);
        $this->assertSame(config('mail.default'), MailRouter::paraEmail('usuario@gmail.com'));
    }

    public function test_envia_enlace_a_usuario_con_email_guardado(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'chofer@emcarga.cu',
            'password' => 'Secreto*1',
            'password_temporal' => true,
        ]);

        $response = $this->post('/olvide-password', ['email' => 'chofer@emcarga.cu']);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'chofer@emcarga.cu']);
        Mail::assertSent(RestablecerPassword::class, fn ($mail) => $mail->hasTo('chofer@emcarga.cu'));
    }

    public function test_email_desconocido_responde_generico_y_no_crea_token(): void
    {
        Mail::fake();

        $response = $this->from('/olvide-password')
            ->post('/olvide-password', ['email' => 'nadie@example.com']);

        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'nadie@example.com']);
        Mail::assertNothingSent();
    }

    public function test_usuario_sin_email_no_recibe_nada(): void
    {
        Mail::fake();

        User::factory()->create(['username' => 'SINEMAIL', 'email' => null]);

        $response = $this->from('/olvide-password')
            ->post('/olvide-password', ['email' => 'x@example.com']);

        $response->assertSessionHas('status');
        Mail::assertNothingSent();
    }

    public function test_reset_exitoso_cambia_password_y_quitara_temporal(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'chofer2@nauta.cu',
            'password' => 'Vieja*123',
            'password_temporal' => true,
            'intentos_fallidos' => 3,
        ]);

        $this->post('/olvide-password', ['email' => 'chofer2@nauta.cu']);

        // El token plano viaja en el PATH de la URL del mailable (en BD se guarda hasheado).
        $capturado = null;
        Mail::assertSent(RestablecerPassword::class, function ($m) use (&$capturado) {
            $capturado = $m;

            return true;
        });
        $path = trim((string) parse_url($capturado?->urlRestablecer ?? '', PHP_URL_PATH), '/');
        $token = collect(explode('/', $path))->last();

        $response = $this->post('/restablecer-password', [
            'token' => $token,
            'email' => 'chofer2@nauta.cu',
            'password' => 'Nueva*Clave9',
            'password_confirmation' => 'Nueva*Clave9',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $fresh = $user->fresh();
        $this->assertTrue(Hash::check('Nueva*Clave9', $fresh->password));
        $this->assertFalse((bool) $fresh->password_temporal);
        $this->assertEquals(0, $fresh->intentos_fallidos);
        // Token consumido
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'chofer2@nauta.cu']);
        Mail::assertSentCount(1);
    }

    public function test_reset_con_token_invalido_falla(): void
    {
        $user = User::factory()->create(['email' => 'otro@emcarga.cu', 'password' => 'Vieja*123']);

        $response = $this->from('/restablecer-password/xxx?email=otro@emcarga.cu')
            ->post('/restablecer-password', [
                'token' => 'token-invalido',
                'email' => 'otro@emcarga.cu',
                'password' => 'Nueva*Clave9',
                'password_confirmation' => 'Nueva*Clave9',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('Vieja*123', $user->fresh()->password));
    }
}
