<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perfil de usuario auto-gestionable (2026-08-23):
 *  - apellidos: segundo campo del nombre real (name = nombre de pila).
 *  - avatar: ruta relativa dentro del disco público
 *    (ej. "avatars/abc123.jpg", servida vía /storage/).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('apellidos', 191)->nullable()->after('name');
            $table->string('avatar', 255)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['apellidos', 'avatar']);
        });
    }
};
