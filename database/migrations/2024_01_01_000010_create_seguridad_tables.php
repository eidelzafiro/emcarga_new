<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Perfiles de usuario (legacy: rh_perfiles)
        Schema::create('perfiles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        // Histórico de contraseñas (legacy: cod_usuariosh)
        // Guarda los hashes anteriores para impedir su reutilización.
        Schema::create('password_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('password'); // hash bcrypt de la contraseña anterior
            $table->timestamp('fecha_cambio')->useCurrent();

            $table->index('user_id');
        });

        // Bitácora de auditoría (legacy: user_bitacora)
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('accion', 100);
            $table->string('tabla', 100)->nullable();
            $table->unsignedBigInteger('id_registro')->nullable();
            $table->text('detalles')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('fecha_accion')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'fecha_accion']);
            $table->index(['tabla', 'id_registro']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacora');
        Schema::dropIfExists('password_histories');
        Schema::dropIfExists('perfiles');
    }
};
