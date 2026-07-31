<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_incidencias', function (Blueprint $table) {
            $table->foreignId('id_tipo_deducciones')->nullable()->constrained('tipos_deducciones')->nullOnDelete();
            $table->boolean('tsuma')->default(false);
            $table->boolean('impsuma')->default(false);
            $table->boolean('penalizacuc')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('tipos_incidencias', function (Blueprint $table) {
            $table->dropForeign(['id_tipo_deducciones']);
            $table->dropColumn(['id_tipo_deducciones', 'tsuma', 'impsuma', 'penalizacuc']);
        });
    }
};
