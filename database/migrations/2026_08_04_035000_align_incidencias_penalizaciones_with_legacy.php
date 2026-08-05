<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // INCIDENCIAS: alinear con rh_incidencias del legacy
        Schema::table('incidencias', function (Blueprint $table) {
            if (Schema::hasColumn('incidencias', 'id_bolsa')) {
                $table->dropForeign(['id_bolsa']);
            }

            $columnsToDrop = ['tipo_incidencia', 'descripcion', 'documentos', 'estado', 'deleted_at'];
            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('incidencias', $col)) {
                    $table->dropColumn($col);
                }
            }

            $table->foreignId('id_bolsa')->nullable(false)->change();
            $table->foreign('id_bolsa')->references('id')->on('bolsa')->cascadeOnDelete();

            $table->foreignId('id_tipo_incidencia')->nullable(false);
            $table->foreign('id_tipo_incidencia')->references('id')->on('tipos_incidencias')->restrictOnDelete();

            $table->decimal('periodo_actual', 6, 2)->default(0);
            $table->decimal('importe', 10, 2)->default(0);
        });

        // PENALIZACIONES: alinear con rh_penalizaciones del legacy
        Schema::table('penalizaciones', function (Blueprint $table) {
            if (Schema::hasColumn('penalizaciones', 'id_bolsa')) {
                $table->dropForeign(['id_bolsa']);
            }

            $columnsToDrop = ['tipo_penalizacion', 'monto', 'descripcion', 'estado', 'deleted_at'];
            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('penalizaciones', $col)) {
                    $table->dropColumn($col);
                }
            }

            $table->foreignId('id_bolsa')->nullable(false)->change();
            $table->foreign('id_bolsa')->references('id')->on('bolsa')->cascadeOnDelete();

            $table->foreignId('id_tipo_penalizacion')->nullable(false);
            $table->foreign('id_tipo_penalizacion')->references('id')->on('tipos_penalizaciones')->restrictOnDelete();

            $table->decimal('importe', 6, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('incidencias', function (Blueprint $table) {
            $table->dropForeign(['id_bolsa']);
            $table->dropForeign(['id_tipo_incidencia']);
            $table->dropColumn(['id_tipo_incidencia', 'periodo_actual', 'importe']);
            $table->string('tipo_incidencia', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->text('documentos')->nullable();
            $table->string('estado', 50)->default('pendiente');
            $table->softDeletes();
        });

        Schema::table('penalizaciones', function (Blueprint $table) {
            $table->dropForeign(['id_bolsa']);
            $table->dropForeign(['id_tipo_penalizacion']);
            $table->dropColumn(['id_tipo_penalizacion', 'importe']);
            $table->string('tipo_penalizacion', 100)->nullable();
            $table->decimal('monto', 12, 2)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('estado', 50)->default('pendiente');
            $table->softDeletes();
        });
    }
};
