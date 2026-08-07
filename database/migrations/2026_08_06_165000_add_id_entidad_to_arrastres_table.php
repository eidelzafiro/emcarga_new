<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arrastres', function (Blueprint $table) {
            $table->unsignedBigInteger('id_entidad')->nullable()->after('id_tipo_equipo');

            $table->foreign('id_entidad')
                ->references('id')
                ->on('entidades')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('arrastres', function (Blueprint $table) {
            $table->dropForeign(['id_entidad']);
            $table->dropColumn('id_entidad');
        });
    }
};
