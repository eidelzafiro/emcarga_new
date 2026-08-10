<?php

namespace App\Services;

use App\Models\CartaPorte;
use App\Models\HojasRuta;
use App\Models\Tractivo;
use Illuminate\Support\Facades\DB;

class HojasRutaService
{
    /**
     * Apertura de Hoja de Ruta: crea el registro y aplica efectos
     * asociados (asociación tractivo-arrastre, kms disponibles del tractor).
     */
    public function abrir(array $datos, int $userId): HojasRuta
    {
        $this->asociar($datos['id_tractivo'] ?? null, $datos['id_arrastre'] ?? null);

        $hr = HojasRuta::create([
            'numero' => $datos['numero'],
            'id_tractivo' => $datos['id_tractivo'] ?? null,
            'id_arrastre' => $datos['id_arrastre'] ?? null,
            'id_chofer' => $datos['id_chofer'] ?? null,
            'id_chofer2' => $datos['id_chofer2'] ?? null,
            'id_parqueo' => $datos['id_parqueo'] ?? null,
            'id_grupo' => $datos['id_grupo'] ?? null,
            'id_hr_anterior' => $datos['id_hr_anterior'] ?? null,
            'kms_disponible' => $datos['kms_disponible'] ?? 0,
            'kms_disponibles_adicionales' => $datos['kms_disponibles_adicionales'] ?? 0,
            'fecha_emision' => $datos['fecha_emision'],
            'hora_emision' => $datos['hora_emision'] ?? null,
            'fecha_salida' => $datos['fecha_emision'],
            'id_entidad' => $this->entidadDeTractivo($datos['id_tractivo'] ?? null),
            'id_user' => $userId,
            'cancelada' => false,
            'estado' => 'abierta',
        ]);

        $this->actualizarKmsTractor($datos['id_tractivo'] ?? null, $datos['kms_disponible'] ?? 0);

        return $hr;
    }

    /**
     * Cierra la HR actual y crea la siguiente, reutilizando el equipo.
     * Replica actualizar_submit_cierre del legacy.
     */
    public function cerrarYCrearSiguiente(int $idHoja, array $datos, int $userId): HojasRuta
    {
        $hr = HojasRuta::findOrFail($idHoja);
        $this->aplicarCierre($hr, $datos);

        // Nueva HR del mismo equipo
        $nueva = HojasRuta::create([
            'numero' => $datos['numero_nueva'],
            'id_tractivo' => $hr->id_tractivo,
            'id_arrastre' => $datos['id_arrastre'] ?? $hr->id_arrastre,
            'id_chofer' => $datos['id_chofer'] ?? $hr->id_chofer,
            'id_chofer2' => $hr->id_chofer2,
            'id_parqueo' => $datos['id_parqueo'] ?? $hr->id_parqueo,
            'id_grupo' => $hr->id_grupo,
            'kms_disponible' => $datos['kms_disponible'] ?? 0,
            'kms_disponibles_adicionales' => $datos['kms_disponibles_adicionales'] ?? 0,
            'fecha_emision' => $datos['fecha_cierre'] ?? $hr->fecha_cierre,
            'hora_emision' => $datos['hora_cierre'] ?? $hr->hora_cierre,
            'fecha_salida' => $datos['fecha_cierre'] ?? $hr->fecha_cierre,
            'id_hr_anterior' => $hr->id,
            'id_entidad' => $hr->id_entidad,
            'id_user' => $userId,
            'cancelada' => false,
            'estado' => 'abierta',
        ]);

        $this->asociar($hr->id_tractivo, $datos['id_arrastre'] ?? $hr->id_arrastre);
        $this->actualizarKmsTractor($hr->id_tractivo, $datos['kms_disponible'] ?? 0);

        return $nueva;
    }

    /**
     * Cierra la HR sin crear otra (replica actualizar_submit_cierre1).
     */
    public function cerrar(int $idHoja, array $datos): HojasRuta
    {
        $hr = HojasRuta::findOrFail($idHoja);
        $this->aplicarCierre($hr, $datos);

        return $hr;
    }

    /**
     * Edición completa de una HR (replica actualizar_submit_todo).
     */
    public function modificar(int $idHoja, array $datos, int $userId): void
    {
        $hr = HojasRuta::findOrFail($idHoja);

        if ($hr->cancelada) {
            abort(422, 'No se puede editar una Hoja de Ruta cancelada.');
        }

        $campos = [
            'id_tractivo', 'id_arrastre', 'id_chofer', 'id_chofer2',
            'id_parqueo', 'id_grupo', 'fecha_emision', 'hora_emision',
            'fecha_cierre', 'hora_cierre', 'kms_disponible', 'kms_disponibles_adicionales',
            'kms_totales', 'combustible_habilitado', 'combustible_consumido',
            'combustible_tecnico', 'notas', 'analisis', 'tiempo_mov', 'tiempo_espera',
            'tiempo_carga', 'tiempo_taller', 'tiempo_inactivo', 'tiempo_otras_actividades',
            'tiempo_total', 'dias_trabajados',
        ];

        $hr->fill(collect($campos)->mapWithKeys(fn ($c) => [$c => $datos[$c] ?? null])->all());
        $hr->numero = $hr->getOriginal('numero');
        $hr->indice_hr = $this->calcularIndice($datos['combustible_habilitado'] ?? 0, $datos['kms_totales'] ?? 0);
        $hr->cancelada = (bool) ($datos['cancelada'] ?? false);
        $hr->estado = $hr->cancelada
            ? 'cancelada'
            : ($hr->fecha_cierre ? 'cerrada' : 'abierta');
        $hr->save();

        $this->asociar($hr->id_tractivo, $hr->id_arrastre);
    }

    /**
     * Cancela la HR (replica actualizar_cancelado).
     */
    public function cancelar(int $idHoja, int $userId, ?string $fechaOperaciones = null): void
    {
        $this->assertSinCartasPorte($idHoja);

        $hr = HojasRuta::findOrFail($idHoja);
        $hr->update([
            'fecha_cierre' => $fechaOperaciones ?: now()->toDateString(),
            'kms_totales' => 0,
            'combustible_habilitado' => 0,
            'combustible_consumido' => 0,
            'combustible_tecnico' => 0,
            'indice_hr' => 0,
            'cancelada' => true,
            'notas' => ($hr->notas ? $hr->notas."\n" : '').'CANCELADA EL DIA '.($fechaOperaciones ?: now()->toDateString()),
            'estado' => 'cancelada',
            'id_user' => $userId,
        ]);
    }

    /**
     * Elimina una HR solo si no tiene Cartas de Porte asociadas.
     */
    public function eliminar(int $id): void
    {
        $this->assertSinCartasPorte($id);

        HojasRuta::where('id', $id)->delete();
    }

    /**
     * Rechaza la operación si la HR tiene Cartas de Porte sin cancelar.
     * Las canceladas se ignoran (estado = 'cancelada').
     */
    private function assertSinCartasPorte(int $id): void
    {
        $sinCancelar = CartaPorte::where('id_hoja_ruta', $id)
            ->where('estado', '!=', 'cancelada')
            ->exists();

        if ($sinCancelar) {
            abort(422, 'La Hoja de Ruta tiene Cartas de Porte sin cancelar asociadas.');
        }
    }

    private function aplicarCierre(HojasRuta $hr, array $datos): void
    {
        $hr->update([
            'fecha_cierre' => $datos['fecha_cierre'] ?? null,
            'hora_cierre' => $datos['hora_cierre'] ?? null,
            'kms_totales' => $datos['kms_totales'] ?? 0,
            'combustible_habilitado' => $datos['combustible_habilitado'] ?? 0,
            'combustible_consumido' => $datos['combustible_consumido'] ?? 0,
            'combustible_tecnico' => $datos['combustible_tecnico'] ?? 0,
            'dias_trabajados' => $datos['dias_trabajados'] ?? null,
            'indice_hr' => $this->calcularIndice(
                $datos['combustible_habilitado'] ?? 0,
                $datos['kms_totales'] ?? 0
            ),
            'estado' => 'cerrada',
        ]);

        if ($hr->id_tractivo) {
            $this->actualizarKmsTractor($hr->id_tractivo, max(0, (float) ($hr->kms_disponible ?? 0) - (float) ($datos['kms_totales'] ?? 0)));
        }
    }

    private function calcularIndice(mixed $combHab, mixed $kmsTotal): float
    {
        $comb = (float) $combHab;
        $kms = (float) $kmsTotal;

        return $kms > 0 ? round($comb / $kms, 8) : 0;
    }

    /**
     * Mantiene la asociación tractivo-arrastre en la tabla pivote.
     */
    private function asociar(?int $idTractivo, ?int $idArraster): void
    {
        if (! $idTractivo) {
            return;
        }

        DB::table('arrastre_tractivo')->where('id_tractivo', $idTractivo)->delete();
        if ($idArraster) {
            DB::table('arrastre_tractivo')->insert([
                'id_tractivo' => $idTractivo,
                'id_arrastre' => $idArraster,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function actualizarKmsTractor(?int $idTractivo, mixed $kms): void
    {
        if (! $idTractivo) {
            return;
        }

        Tractivo::where('id', $idTractivo)->update(['kms_disp' => (float) $kms]);
    }

    private function entidadDeTractivo(?int $idTractivo): ?int
    {
        if (! $idTractivo) {
            return null;
        }

        return Tractivo::where('id', $idTractivo)->value('id_entidad');
    }
}