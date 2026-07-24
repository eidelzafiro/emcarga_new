<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidades', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('contenedores', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->foreignId('id_carta_porte')->nullable()->constrained('cartas_porte');
            $table->foreignId('id_carta_porte_retorno')->nullable()->constrained('cartas_porte');
            $table->date('fecha_salida')->nullable();
            $table->date('fecha_retorno')->nullable();
            $table->string('tipo', 50)->nullable();
            $table->string('tara', 50)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('categorias_productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_subcta_unidad', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_subcta_unidad');
        Schema::dropIfExists('categorias_productos');
        Schema::dropIfExists('contenedores');
        Schema::dropIfExists('unidades');
    }
};
