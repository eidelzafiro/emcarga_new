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
        // Tabla de perfiles de usuario
        Schema::create('perfiles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        // Tabla principal de usuarios
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('login', 100)->unique();
            $table->string('password', 255);
            $table->string('nombre', 255)->nullable();
            $table->string('email', 255)->nullable()->unique();
            $table->foreignId('idperfil')->constrained('perfiles')->nullable();
            $table->foreignId('idunidad')->nullable();
            $table->foreignId('idgrupo')->nullable();
            $table->boolean('bloqueado')->default(false);
            $table->integer('intentos_fallidos')->default(0);
            $table->timestamp('ultimo_login')->nullable();
            $table->timestamp('fecha_cambio_password')->nullable();
            $table->boolean('password_temporal')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('login');
            $table->index('idperfil');
        });

        // Histórico de contraseñas
        Schema::create('usuarios_historico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idusuario')->constrained('usuarios');
            $table->string('password', 255);
            $table->timestamp('fecha_cambio');
            $table->timestamps();
        });

        // Bitácora de auditoría
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idusuario')->constrained('usuarios');
            $table->string('accion', 100);
            $table->string('tabla', 100)->nullable();
            $table->integer('id_registro')->nullable();
            $table->text('detalles')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('fecha_accion');
            $table->timestamps();

            $table->index(['idusuario', 'fecha_accion']);
            $table->index(['tabla', 'id_registro']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacora');
        Schema::dropIfExists('usuarios_historico');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('perfiles');
    }
};
