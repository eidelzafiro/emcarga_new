<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade abreviatura a entidades (paridad con rh_entidades legacy)
 * y hace id_area nullable (el legacy no tiene áreas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entidades', function (Blueprint $table) {
            $table->string('abreviatura', 150)->nullable()->after('nombre');
            $table->foreignId('id_area')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('entidades', function (Blueprint $table) {
            $table->dropColumn('abreviatura');
            $table->foreignId('id_area')->nullable(false)->change();
        });
    }
};
