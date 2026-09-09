<?php

namespace App\Services;

use App\Models\Bolsa;
use App\Models\User;
use App\Notifications\NotificacionSistema;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Emite notificaciones a COMERCIAL / DIRECTIVOS / RECHUM de la entidad del
 * chofer cuando alguno de sus documentos (licencia, chequeo médico,
 * recalificación, psicométrico) está próximo a vencer o ya está vencido.
 */
class NotificarDocumentosChofer
{
    /** Días de antelación para considerar un documento "próximo a vencer". */
    public const VENTANA_DIAS = 30;

    /** Roles que reciben las notificaciones. */
    public const ROLES_DESTINO = ['COMERCIAL', 'DIRECTIVOS', 'RECHUM'];

    /**
     * Documentos de un chofer: clave => [etiqueta, tipo en documentos_chofer].
     * Los documentos viven en la tabla documentos_chofer (uno por tipo).
     */
    private const DOCUMENTOS = [
        'licencia' => ['etiqueta' => 'licencia de conducción', 'tipo' => 'LICENCIA'],
        'chequeo_medico' => ['etiqueta' => 'chequeo médico', 'tipo' => 'CHEQUEO_MEDICO'],
        'recalificacion' => ['etiqueta' => 'recalificación', 'tipo' => 'RECALIFICACION'],
        'psicometrico' => ['etiqueta' => 'psicométrico', 'tipo' => 'PSICOMETRICO'],
    ];

    /**
     * Recorre los choferes activos y notifica los documentos por vencer/vencidos.
     * Si se pasa un $choferId, limita el análisis a ese chofer.
     * Devuelve el número total de notificaciones enviadas.
     */
    public function ejecutar(?int $choferId = null): int
    {
        $query = Bolsa::with('cargo', 'entidad');

        if ($choferId) {
            $choferes = $query->whereKey($choferId)->get()->filter(fn ($b) => $this->esChofer($b));
        } else {
            $choferes = $query->get()->filter(fn ($b) => $this->esChofer($b));
        }

        $total = 0;
        foreach ($choferes as $chofer) {
            $total += $this->paraChofer($chofer);
        }

        return $total;
    }

    public function esChofer(Bolsa $bolsa): bool
    {
        if (! $bolsa->cargo) {
            return false;
        }

        return str_contains(strtoupper((string) $bolsa->cargo->nombre), 'CHOFER');
    }

    /**
     * Determina si todos los documentos están presentes y vigentes.
     */
    public function habilitado(Bolsa $bolsa): bool
    {
        if (! $this->esChofer($bolsa)) {
            return false;
        }

        $hoy = Carbon::today();
        $documentos = $bolsa->documentos()->get()->keyBy('tipo');

        foreach (self::DOCUMENTOS as $clave => $doc) {
            $documento = $documentos->get($doc['tipo']);
            if (! $documento || $documento->vencimiento === null) {
                return false;
            }
            if (Carbon::parse($documento->vencimiento)->lt($hoy)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Emite las notificaciones pertinentes para un chofer por entidad.
     * Devuelve el número de notificaciones enviadas (una por documento afectado).
     */
    private function paraChofer(Bolsa $bolsa): int
    {
        if (! $this->esChofer($bolsa)) {
            return 0;
        }

        $alertas = $this->alertasDocumentos($bolsa);
        if ($alertas->isEmpty()) {
            return 0;
        }

        $destinos = $this->destinosEntidad($bolsa->id_entidad);
        if ($destinos->isEmpty()) {
            return 0;
        }

        $nombreCompleto = $bolsa->nombrecompleto;

        $enviadas = 0;
        foreach ($alertas as $alerta) {
            $estado = $alerta['estado'] === 'vencida' ? 'VENCIDA' : 'PRÓXIMA A VENCER';
            $titulo = 'Documento de chofer por vencer';
            $cuerpo = "{$nombreCompleto} — {$alerta['etiqueta']} {$estado} el {$alerta['fecha_html']}.";

            foreach ($destinos as $user) {
                if ($this->yaNotificado($user, $titulo, $cuerpo)) {
                    continue;
                }

                $user->notify(new NotificacionSistema(
                    titulo: $titulo,
                    cuerpo: $cuerpo,
                    tipo: $alerta['estado'] === 'vencida' ? 'error' : 'warning',
                    url: route('bolsa.index'),
                    icono: $alerta['estado'] === 'vencida' ? 'pi pi-times-circle' : 'pi pi-exclamation-triangle',
                ));
                $enviadas++;
            }
        }

        return $enviadas;
    }

    /** Evita notificaciones duplicadas sin leer del mismo documento. */
    private function yaNotificado(User $user, string $titulo, string $cuerpo): bool
    {
        return $user->notifications()
            ->whereNull('read_at')
            ->where('data->titulo', $titulo)
            ->where('data->cuerpo', $cuerpo)
            ->exists();
    }

    /** Detecta documentos próximos a vencer o vencidos con sus fechas. */
    private function alertasDocumentos(Bolsa $bolsa): Collection
    {
        $hoy = Carbon::today();
        $limite = Carbon::today()->addDays(self::VENTANA_DIAS);
        $alertas = collect();

        $documentos = $bolsa->documentos()->get()->keyBy('tipo');

        foreach (self::DOCUMENTOS as $clave => $doc) {
            $documento = $documentos->get($doc['tipo']);
            if (! $documento || $documento->vencimiento === null) {
                continue;
            }
            $fecha = Carbon::parse($documento->vencimiento);

            if ($fecha->lt($hoy)) {
                $alertas->push(['etiqueta' => $doc['etiqueta'], 'fecha' => $fecha, 'estado' => 'vencida']);
            } elseif ($fecha->gt($hoy) && $fecha->lte($limite)) {
                $alertas->push(['etiqueta' => $doc['etiqueta'], 'fecha' => $fecha, 'estado' => 'proxima']);
            }
        }

        return $alertas->map(fn ($a) => $a + ['fecha_html' => $a['fecha']->format('d/m/Y')]);
    }

    /** Usuarios CON COMERCIAL/DIRECTIVOS/RECHUM de la entidad del chofer. */
    private function destinosEntidad(?int $entidadId): Collection
    {
        return User::where('id_entidad', $entidadId)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', self::ROLES_DESTINO))
            ->get();
    }
}
