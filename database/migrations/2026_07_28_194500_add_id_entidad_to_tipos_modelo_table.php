<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_modelo', function (Blueprint $table) {
            $table->foreignId('id_entidad')->nullable()->constrained('entidades')->nullOnDelete()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_modelo', function (Blueprint $table) {
            $table->dropForeign(['id_entidad']);
            $table->dropColumn('id_entidad');
        });
    }
};
