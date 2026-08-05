<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía `hojas_ruta` con el esquema legacy (com_hojaruta): apertura y cierre.
 * Idempotente sobre columnas/FKs ya presentes (MySQL DDL no es transaccional).
 */
return new class extends Migration
{
    public function up(): void
    {
        $fl = fn (string $column): bool => Schema::hasColumn('hojas_ruta', $column);
        $fk = function (string $col): bool {
            return DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'hojas_ruta')
                ->where('COLUMN_NAME', $col)
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->exists();
        };

        // id_solicitud: la HR legacy no la tiene → nullable
        if ($fl('id_solicitud')) {
            DB::statement('ALTER TABLE hojas_ruta MODIFY id_solicitud BIGINT UNSIGNED NULL');
            if (! $fk('id_solicitud')) {
                DB::statement('ALTER TABLE hojas_ruta ADD CONSTRAINT hojas_ruta_id_solicitud_foreign FOREIGN KEY (id_solicitud) REFERENCES solicitudes(id) ON DELETE SET NULL');
            }
        }

        // id_cliente: legacy HR no lo usa → nullable
        if ($fl('id_cliente')) {
            if ($fk('id_cliente')) {
                DB::statement('ALTER TABLE hojas_ruta DROP FOREIGN KEY hojas_ruta_id_cliente_foreign');
            }
            DB::statement('ALTER TABLE hojas_ruta MODIFY id_cliente BIGINT UNSIGNED NULL');
            if (! $fk('id_cliente')) {
                DB::statement('ALTER TABLE hojas_ruta ADD CONSTRAINT hojas_ruta_id_cliente_foreign FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE SET NULL');
            }
        }

        // Añade el bloque completo de columnas legacy si aún no existe
        if (! $fl('fecha_emision')) {
            Schema::table('hojas_ruta', function ($t) {
                $t->date('fecha_emision')->nullable();
                $t->string('hora_emision', 15)->nullable();
                $t->foreignId('id_arrastre')->nullable()->constrained('arrastres');
                $t->foreignId('id_chofer')->nullable()->constrained('bolsa');
                $t->foreignId('id_chofer2')->nullable()->constrained('bolsa');
                $t->decimal('kms_disponible', 10, 2)->nullable();
                $t->decimal('kms_disponibles_adicionales', 10, 2)->nullable();
                $t->foreignId('id_hr_anterior')->nullable()->constrained('hojas_ruta');
                $t->foreignId('id_parqueo')->nullable()->constrained('lugares');
                $t->foreignId('id_grupo')->nullable()->constrained('grupos');
                $t->foreignId('id_entidad')->nullable()->constrained('entidades');
                $t->foreignId('id_user')->nullable()->constrained('users');
                $t->date('fecha_cierre')->nullable();
                $t->string('hora_cierre', 15)->nullable();
                $t->decimal('kms_totales', 6, 2)->nullable();
                $t->decimal('combustible_habilitado', 10, 2)->nullable();
                $t->decimal('combustible_consumido', 10, 2)->nullable();
                $t->decimal('combustible_tecnico', 10, 2)->nullable();
                $t->decimal('indice_hr', 10, 8)->nullable();
                $t->decimal('tiempo_mov', 10, 2)->nullable();
                $t->decimal('tiempo_espera', 10, 2)->nullable();
                $t->decimal('tiempo_carga', 10, 2)->nullable();
                $t->decimal('tiempo_taller', 10, 2)->nullable();
                $t->decimal('tiempo_inactivo', 10, 2)->nullable();
                $t->decimal('tiempo_otras_actividades', 10, 2)->nullable();
                $t->decimal('tiempo_total', 10, 2)->nullable();
                $t->text('notas')->nullable();
                $t->text('analisis')->nullable();
                $t->string('dias_trabajados', 70)->nullable();
                $t->boolean('cancelada')->default(false);
                $t->index('fecha_emision');
                $t->index('id_chofer');
                $t->index('id_entidad');
            });
        }

        // Garantiza las FKs del bloque añadido (si la columna existe sin constraint)
        $refs = [
            'id_arrastre' => ['arrastres', 'id'],
            'id_chofer' => ['bolsa', 'id'],
            'id_chofer2' => ['bolsa', 'id'],
            'id_hr_anterior' => ['hojas_ruta', 'id'],
            'id_parqueo' => ['lugares', 'id'],
            'id_grupo' => ['grupos', 'id'],
            'id_entidad' => ['entidades', 'id'],
            'id_user' => ['users', 'id'],
        ];
        foreach ($refs as $col => [$tabla, $ref]) {
            if ($fl($col) && ! $fk($col)) {
                DB::statement("ALTER TABLE hojas_ruta ADD CONSTRAINT hojas_ruta_{$col}_foreign FOREIGN KEY ({$col}) REFERENCES {$tabla}({$ref}) ON DELETE SET NULL");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('hojas_ruta', 'fecha_emision')) {
            Schema::dropColumns('hojas_ruta', [
                'fecha_emision', 'hora_emision', 'id_arrastre', 'id_chofer', 'id_chofer2',
                'kms_disponible', 'kms_disponibles_adicionales', 'id_hr_anterior', 'id_parqueo',
                'id_grupo', 'id_entidad', 'id_user', 'fecha_cierre', 'hora_cierre', 'kms_totales',
                'combustible_habilitado', 'combustible_consumido', 'combustible_tecnico', 'indice_hr',
                'tiempo_mov', 'tiempo_espera', 'tiempo_carga', 'tiempo_taller', 'tiempo_inactivo',
                'tiempo_otras_actividades', 'tiempo_total', 'notas', 'analisis', 'dias_trabajados', 'cancelada',
            ]);
        }
    }
};