<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * D5/D6 (auditoría): normaliza los campos de hora de `aforos` a formato HH:MM
 * y elimina `facturas.id_unidad` (100% redundante con id_entidad).
 *
 * Los campos hora_carga_1/2 y hora_descarga_1/2 mantienen tipo varchar (la UI
 * los edita como texto y difHoras usa strtotime), pero se normalizan los
 * valores sucios heredados del legacy ("14.30"→"14:30", "9:00"→"09:00",
 * "00:000"→null).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Normaliza un valor de hora suelto a HH:MM (o null si no es válido).
        foreach (['hora_carga_1', 'hora_carga_2', 'hora_descarga_1', 'hora_descarga_2'] as $columna) {
            if (! Schema::hasColumn('aforos', $columna)) {
                continue;
            }

            $filas = DB::table('aforos')->whereNotNull($columna)->pluck($columna, 'id');

            foreach ($filas as $id => $valor) {
                $normalizado = $this->normalizarHora($valor);
                DB::table('aforos')->where('id', $id)->update([$columna => $normalizado]);
            }
        }

        // D6: id_unidad es idéntico a id_entidad en facturas → se elimina.
        if (Schema::hasColumn('facturas', 'id_unidad')) {
            try {
                Schema::table('facturas', function ($table) {
                    $table->dropColumn('id_unidad');
                });
            } catch (\Throwable $e) {
                // FK/index presente: se deja la columna.
            }
        }
    }

    public function down(): void
    {
        // No se puede reconstruir los valores originales de hora de forma fiable.
        if (! Schema::hasColumn('facturas', 'id_unidad')) {
            Schema::table('facturas', function ($table) {
                $table->unsignedBigInteger('id_unidad')->nullable();
            });
        }
    }

    /**
     * Convierte un valor de hora suelto a "HH:MM" (formato legible para strtotime).
     */
    private function normalizarHora(mixed $valor): ?string
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return null;
        }

        // Ya en formato HH:MM válido
        if (preg_match('/^([0-9]{2}):([0-9]{2})$/', $valor, $m)) {
            $h = (int) $m[1];
            $mm = (int) $m[2];
            if ($h >= 0 && $h <= 23 && $mm >= 0 && $mm <= 59) {
                return $valor;
            }
        }

        // "00:000" → horas:minutos con 3 dígitos → solo horas si min=0
        if (preg_match('/^([0-9]{2}):([0-9]{3})$/', $valor, $m)) {
            $h = (int) $m[1];
            $mm = (int) $m[2];
            if ($mm === 0 && $h >= 0 && $h <= 23) {
                return sprintf('%02d:00', $h);
            }
        }

        // "9:00" / "7:40" → hora sin cero a la izquierda
        if (preg_match('/^([0-9]{1,2}):([0-9]{2})$/', $valor, $m)) {
            $h = (int) $m[1];
            $mm = (int) $m[2];
            if ($h >= 0 && $h <= 23 && $mm >= 0 && $mm <= 59) {
                return sprintf('%02d:%02d', $h, $mm);
            }
        }

        // "14.30" → separador de punto (legacy)
        if (preg_match('/^([0-9]{1,2})\.([0-9]{2})$/', $valor, $m)) {
            $h = (int) $m[1];
            $mm = (int) $m[2];
            if ($h >= 0 && $h <= 23 && $mm >= 0 && $mm <= 59) {
                return sprintf('%02d:%02d', $h, $mm);
            }
        }

        // "14:30:00" con segundos → HH:MM
        if (preg_match('/^([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $valor, $m)) {
            $h = (int) $m[1];
            $mm = (int) $m[2];
            if ($h >= 0 && $h <= 23 && $mm >= 0 && $mm <= 59) {
                return sprintf('%02d:%02d', $h, $mm);
            }
        }

        // No se pudo interpretar → null (evita romper difHoras/strtotime)
        return null;
    }
};
