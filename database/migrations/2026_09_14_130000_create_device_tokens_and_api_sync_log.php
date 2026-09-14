<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 8 (API móvil): tokens de dispositivos para push y bitácora de sync.
 *
 * - `device_tokens`: un token Expo/FCM por dispositivo, ligado a un usuario.
 *   El envío real es best-effort (Cuba bloquea FCM); el fallback es la
 *   notificación `database` que ya consume el cliente vía /notificaciones.
 * - `api_sync_log`: registra las operaciones de sincronización offline-first
 *   (pull) para auditoría y diagnóstico.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('device_tokens')) {
            Schema::create('device_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('token', 255)->unique();
                $table->string('platform', 20)->default('android');
                $table->string('device_name', 100)->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index('user_id');
            });
        }

        if (! Schema::hasTable('api_sync_log')) {
            Schema::create('api_sync_log', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('direction', 10)->default('pull');
                $table->string('endpoint', 150);
                $table->unsignedBigInteger('id_entidad')->nullable();
                $table->date('fecha_operaciones')->nullable();
                $table->unsignedInteger('items')->default(0);
                $table->string('payload_hash', 64)->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('api_sync_log');
        Schema::dropIfExists('device_tokens');
    }
};
