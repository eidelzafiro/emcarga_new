<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_orden_taller')->constrained('ordenes_taller');
            $table->date('fecha_mantenimiento');
            $table->foreignId('id_tipo_mantenimiento')->constrained('tipos_mantenimiento');
            $table->bigInteger('kms_mantenimiento');
            $table->integer('kms_disponible');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes_mantenimiento');
    }
};
