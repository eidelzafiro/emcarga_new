<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('grupos_escala', 'id_entidad')) {
            Schema::table('grupos_escala', function (Blueprint $table) {
                $table->foreignId('id_entidad')->nullable()->constrained('entidades')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('grupos_escala', 'id_entidad')) {
            Schema::table('grupos_escala', function (Blueprint $table) {
                $table->dropForeign(['id_entidad']);
                $table->dropColumn('id_entidad');
            });
        }
    }
};
