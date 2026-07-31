<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_penalizaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('id_entidad')->nullable()->after('porcentaje');
            $table->index('id_entidad');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_penalizaciones', function (Blueprint $table) {
            $table->dropIndex(['id_entidad']);
            $table->dropColumn('id_entidad');
        });
    }
};
