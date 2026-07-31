<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_penalizaciones', function (Blueprint $table) {
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('tipo_pago_adicional_id')->nullable()->constrained('tipos_pagos_adicionales')->nullOnDelete();
            $table->decimal('porcentaje', 5, 2)->nullable()->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('tipos_penalizaciones', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropForeign(['tipo_pago_adicional_id']);
            $table->dropColumn(['area_id', 'tipo_pago_adicional_id', 'porcentaje']);
        });
    }
};
