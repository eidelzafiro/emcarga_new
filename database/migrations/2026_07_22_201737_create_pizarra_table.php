<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pizarra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tractivo_id')->constrained('tractivos')->cascadeOnDelete();
            $table->foreignId('conductor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('estado', 50)->default('disponible');
            $table->string('ubicacion', 255)->nullable();
            $table->string('origen', 255)->nullable();
            $table->string('destino', 255)->nullable();
            $table->dateTime('salida')->nullable();
            $table->dateTime('llegada_estimada')->nullable();
            $table->dateTime('llegada_real')->nullable();
            $table->text('carga')->nullable();
            $table->decimal('tonelaje', 10, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pizarra');
    }
};
