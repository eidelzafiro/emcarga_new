<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes_servicio', function (Blueprint $table) {
            $table->decimal('peso1', 12, 2)->nullable();
            $table->decimal('peso2', 12, 2)->nullable();
            $table->unsignedInteger('distancia')->nullable();
            $table->string('notas', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes_servicio', function (Blueprint $table) {
            $table->dropColumn(['peso1', 'peso2', 'distancia', 'notas']);
        });
    }
};
