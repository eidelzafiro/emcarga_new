<?php

namespace Tests\Feature;

use App\Jobs\ProcesarExportacionTabla;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportacionTablaTest extends TestCase
{
    private function usuarioConAcceso(): User
    {
        $user = User::factory()->create();
        $user->assignRole('SUPERADMIN');

        return $user;
    }

    public function test_generar_export_despacha_job_en_cola(): void
    {
        Queue::fake();

        $user = $this->usuarioConAcceso();

        $this->actingAs($user)
            ->post(route('reportes.generar', 1075), ['filtros' => ['x' => 1]])
            ->assertRedirect();

        Queue::assertPushed(ProcesarExportacionTabla::class, function (ProcesarExportacionTabla $job) use ($user) {
            return $job->reporteId === 1075
                && $job->userId === $user->id
                && $job->filtros === ['x' => 1];
        });
    }

    public function test_job_genera_csv_y_notifica_usuario(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $job = new ProcesarExportacionTabla(1075, [], $user->id);
        $job->handle();

        Storage::disk('local')->assertExists("exports/{$job->token}.csv");
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $user->id]);

        $notificacion = $user->notifications()->first();
        $this->assertStringContainsString($job->token, $notificacion->data['url']);
        $this->assertSame('success', $notificacion->data['tipo']);
    }

    public function test_ruta_descarga_sirve_csv(): void
    {
        Storage::fake('local');
        $token = 'token-prueba-123';
        Storage::disk('local')->put("exports/{$token}.csv", "col1,col2\nval1,val2\n");

        $user = $this->usuarioConAcceso();

        $this->actingAs($user)
            ->get(route('exportaciones.descargar', $token))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8')
            ->assertSee('col1,col2');
    }

    public function test_ruta_descarga_404_si_no_existe(): void
    {
        Storage::fake('local');
        $user = $this->usuarioConAcceso();

        $this->actingAs($user)
            ->get(route('exportaciones.descargar', 'inexistente'))
            ->assertNotFound();
    }
}
