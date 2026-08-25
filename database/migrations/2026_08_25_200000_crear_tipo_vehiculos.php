<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase A: tabla unificada tipo_vehiculos.
     *
     * Consolida los campos comunes de tipos_tractivos y tipos_arrastres
     * (id_tipo_equipo, id_marca, id_modelo, id_tipo_mantenimiento) y Vincula
     * cada fila a su subtabla de origen (id_tipo_tractivo o id_tipo_arrastre).
     * La clase se deriva del equipo: REMOLQUES(26)/SEMI-REMOLQUES(21) => arrastre.
     * Luego repunta tractivos.id_tipo_vehiculo -> tipo_vehiculos.
     *
     * id_marca/id_modelo/id_tipo_equipo/id_tipo_mantenimiento se dejan como
     * bigint sin FK (el legacy no las enforce y hay ids huerfanos en datos).
     */
    public function up(): void
    {
        // Autolimpieza de residuos de corridas parciales (DDL no rollback).
        $fkTv = DB::select("SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE constraint_schema = DATABASE()
              AND table_name = 'tractivos'
              AND constraint_name = 'tractivos_id_tipo_vehiculo_foreign'
              AND referenced_table_name = 'tipo_vehiculos' LIMIT 1");
        if ($fkTv) {
            Schema::table('tractivos', function (Blueprint $table) {
                $table->dropForeign('tractivos_id_tipo_vehiculo_foreign');
            });
        }
        if (Schema::hasTable('tipo_vehiculos')) {
            Schema::drop('tipo_vehiculos');
        }

        Schema::create('tipo_vehiculos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_tipo_equipo')->nullable();
            $table->unsignedBigInteger('id_marca')->nullable();
            $table->unsignedBigInteger('id_modelo')->nullable();
            $table->unsignedBigInteger('id_tipo_mantenimiento')->nullable();
            $table->foreignId('id_tipo_tractivo')->nullable()->constrained('tipos_tractivos')->nullOnDelete();
            $table->foreignId('id_tipo_arrastre')->nullable()->constrained('tipos_arrastres')->nullOnDelete();
            $table->enum('clase', ['tractivo', 'arrastre'])->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['clase']);
        });

        // Poblar desde tipos_tractivos (clase según equipo).
        DB::statement("INSERT INTO tipo_vehiculos
            (id_tipo_equipo, id_marca, id_modelo, id_tipo_mantenimiento, id_tipo_tractivo, clase, activo, created_at, updated_at)
            SELECT id_tipo_equipo, id_marca, id_modelo, id_tipo_mantenimiento, id,
                   CASE WHEN id_tipo_equipo IN (21, 26) THEN 'arrastre' ELSE 'tractivo' END,
                   activo, NOW(), NOW()
            FROM tipos_tractivos");

        // Poblar desde tipos_arrastres (siempre arrastre).
        DB::statement("INSERT INTO tipo_vehiculos
            (id_tipo_equipo, id_marca, id_modelo, id_tipo_mantenimiento, id_tipo_arrastre, clase, activo, created_at, updated_at)
            SELECT id_tipo_equipo, id_marca, id_modelo, id_tipo_mantenimiento, id,
                   'arrastre', activo, NOW(), NOW()
            FROM tipos_arrastres");

        // Soltar la FK antigua (tipos_tractivos) SOLO si existe, para repuntar libremente.
        $fkExiste = DB::select("SELECT 1 FROM information_schema.table_constraints
            WHERE constraint_schema = DATABASE()
              AND table_name = 'tractivos'
              AND constraint_name = 'tractivos_id_tipo_vehiculo_foreign' LIMIT 1");
        if ($fkExiste) {
            Schema::table('tractivos', function (Blueprint $table) {
                $table->dropForeign('tractivos_id_tipo_vehiculo_foreign');
            });
        }

        $grupoArrastre = "(SELECT id FROM catalogo_items WHERE origen_id = 8)";

        // Tractores: por id_tipo_tractivo.
        DB::statement("UPDATE tractivos t
            JOIN tipo_vehiculos tv ON tv.id_tipo_tractivo = t.id_tipo_vehiculo
            SET t.id_tipo_vehiculo = tv.id
            WHERE t.id_tipo_vehiculo IS NOT NULL
              AND t.id_grupo NOT IN $grupoArrastre");

        // Arrastres: por id_tipo_arrastre (ficha real del remolque).
        DB::statement("UPDATE tractivos t
            JOIN tipo_vehiculos tv ON tv.id_tipo_arrastre = t.id_tipo_vehiculo
            SET t.id_tipo_vehiculo = tv.id
            WHERE t.id_tipo_vehiculo IS NOT NULL
              AND t.id_grupo IN $grupoArrastre");

        // Arrastres fallback: ids que apuntan directo a tipos_tractivos.
        DB::statement("UPDATE tractivos t
            JOIN tipo_vehiculos tv ON tv.id_tipo_tractivo = t.id_tipo_vehiculo
            SET t.id_tipo_vehiculo = tv.id
            WHERE t.id_tipo_vehiculo IS NOT NULL
              AND t.id_grupo IN $grupoArrastre");

        // Añadir la nueva FK hacia tipo_vehiculos (valores ya repuntados).
        Schema::table('tractivos', function (Blueprint $table) {
            $table->foreign('id_tipo_vehiculo')
                ->references('id')->on('tipo_vehiculos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Revertir el repunte es complejo; se recomienda restaurar desde salva.
        // Se revierte al menos el esquema.
        Schema::table('tractivos', function (Blueprint $table) {
            $table->dropForeign('tractivos_id_tipo_vehiculo_foreign');
            $table->foreign('id_tipo_vehiculo')
                ->references('id')->on('tipos_tractivos')
                ->nullOnDelete();
        });

        Schema::dropIfExists('tipo_vehiculos');
    }
};
