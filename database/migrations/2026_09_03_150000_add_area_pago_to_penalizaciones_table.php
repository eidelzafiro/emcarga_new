<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penalizaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('id_area_penalizada')->nullable()->after('id_tipo_penalizacion');
            $table->unsignedBigInteger('id_pago_adicional')->nullable()->after('id_area_penalizada');
        });
    }

    public function down(): void
    {
        Schema::table('penalizaciones', function (Blueprint $table) {
            $table->dropColumn(['id_area_penalizada', 'id_pago_adicional']);
        });
    }
};
