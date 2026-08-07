<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consecutivos', function (Blueprint $table) {
            $table->dropUnique('consecutivos_codigo_unique');
            $table->foreignId('id_entidad')->nullable()->constrained('entidades')->nullOnDelete()->after('id');
            $table->unique(['codigo', 'id_entidad']);
        });
    }

    public function down(): void
    {
        Schema::table('consecutivos', function (Blueprint $table) {
            $table->dropForeign(['id_entidad']);
            $table->dropColumn('id_entidad');
            $table->unique('codigo');
        });
    }
};
