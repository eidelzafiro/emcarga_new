<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\NotificacionSistema;
use App\Services\Reports\ReportesDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * R-3: las exportaciones pesadas de tablas maestras (grupo EXPORTAR TABLAS,
 * ids 1075-1082) se generan fuera de la petición web para no bloquear la UI ni
 * exceder timeouts. El job produce el CSV, lo persiste en storage y avisa al
 * usuario vía NotificacionSistema con un enlace de descarga.
 */
class ProcesarExportacionTabla implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $token;

    /**
     * @param  int    $reporteId  id del reporte en ReportesDispatcher::MAPA
     * @param  array  $filtros    filtros del formulario de reporte
     * @param  int    $userId     usuario que solicitó la exportación
     */
    public function __construct(
        public int $reporteId,
        public array $filtros,
        public int $userId,
    ) {
        $this->token = Str::uuid()->toString();
    }

    public function handle(): void
    {
        $response = app(ReportesDispatcher::class)->generar($this->reporteId, $this->filtros);

        $content = $response->getContent();
        $nombre = $this->extraerNombre($response->headers->get('Content-Disposition'), 'exportacion.csv');

        $ruta = "exports/{$this->token}.csv";
        Storage::disk('local')->put($ruta, $content);

        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $user->notify(new NotificacionSistema(
            titulo: 'Exportación lista',
            cuerpo: "La exportación de tabla está disponible para descargar: {$nombre}.",
            tipo: 'success',
            url: route('exportaciones.descargar', $this->token),
            icono: 'pi pi-download',
        ));
    }

    private function extraerNombre(?string $disposition, string $defecto): string
    {
        if (! $disposition) {
            return $defecto;
        }

        if (preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";]+)"?/i', $disposition, $m)) {
            return basename(trim($m[1], '"'));
        }

        return $defecto;
    }
}
