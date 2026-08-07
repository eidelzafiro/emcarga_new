<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Firmas autorizadas se asocian a la entidad (idunidad → id_entidad, 1:1
 * como en bolsa/talleres) para filtrar el grid por la entidad activa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firmas', function (Blueprint $table) {
            $table->foreignId('id_entidad')->nullable()->after('nombre')->constrained('entidades');
        });
    }

    public function down(): void
    {
        Schema::table('firmas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_entidad');
        });
    }
};
