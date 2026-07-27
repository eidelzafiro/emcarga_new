<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reemplaza users.idunidad por id_entidad (FK a entidades),
 * añade fecha_operaciones (paridad con cod_usuarios.foperaciones)
 * y crea la pivote entidad_user para acceso multi-entidad.
 *
 * Idempotente: una ejecución anterior pudo quedar a medias
 * (rename hecho, FK fallida), así que cada paso se verifica.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'idunidad') && ! Schema::hasColumn('users', 'id_entidad')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('idunidad', 'id_entidad');
            });
        }

        if (! Schema::hasColumn('users', 'fecha_operaciones')) {
            Schema::table('users', function (Blueprint $table) {
                $table->date('fecha_operaciones')->nullable()->after('id_entidad');
            });
        }

        if (! $this->existeFk('users', 'users_id_entidad_foreign')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('id_entidad')->references('id')->on('entidades')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('entidad_user')) {
            Schema::create('entidad_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('entidad_id')->constrained('entidades')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'entidad_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('entidad_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_entidad']);
            $table->dropColumn('fecha_operaciones');
            $table->renameColumn('id_entidad', 'idunidad');
        });
    }

    private function existeFk(string $tabla, string $nombre): bool
    {
        $bd = DB::getDatabaseName();

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', $bd)
            ->where('TABLE_NAME', $tabla)
            ->where('CONSTRAINT_NAME', $nombre)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
