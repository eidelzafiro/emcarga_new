<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jerarquía taller → nave → valla:
 * - talleres se asocian a la entidad activa (id_entidad).
 * - naves se asocian a un taller (id_taller) y a la entidad (id_entidad ya existe).
 * - vallas ya se asocian a la nave (id_nave).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talleres', function (Blueprint $table) {
            $table->foreignId('id_entidad')->nullable()->after('nombre')->constrained('entidades');
        });

        Schema::table('naves', function (Blueprint $table) {
            $table->foreignId('id_taller')->nullable()->after('id_entidad')->constrained('talleres');
        });
    }

    public function down(): void
    {
        Schema::table('naves', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_taller');
        });

        Schema::table('talleres', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_entidad');
        });
    }
};
