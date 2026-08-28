<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * En tractivos la marca/modelo se derivan del tipo de vehículo
     * (id_tipo_vehiculo → tipo_vehiculos.id_marca/id_modelo), por lo que no
     * se almacenan como columnas propias. Se revierte la adición de
     * 2026_08_27_200000_vehicle_schema_normalization para tractivos.
     */
    public function up(): void
    {
        Schema::table('tractivos', function (Blueprint $table) {
            $table->dropForeign(['id_marca']);
            $table->dropForeign(['id_modelo']);
            $table->dropColumn(['id_marca', 'id_modelo']);
        });
    }

    public function down(): void
    {
        Schema::table('tractivos', function (Blueprint $table) {
            $table->foreignId('id_marca')->nullable()->constrained('catalogo_items')->nullOnDelete();
            $table->foreignId('id_modelo')->nullable()->constrained('catalogo_items')->nullOnDelete();
        });
    }
};
