<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_tractivos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tipo_mantenimiento')->nullable()->after('id_pais');

            $table->foreign('id_tipo_mantenimiento')
                ->references('id')
                ->on('tipos_mantenimiento')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_tractivos', function (Blueprint $table) {
            $table->dropForeign(['id_tipo_mantenimiento']);
            $table->dropColumn('id_tipo_mantenimiento');
        });
    }
};