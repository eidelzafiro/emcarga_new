<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_tipos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 100)->unique();
            $table->string('titulo');
            $table->string('agrupacion', 100)->default('');
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->string('tabla_legacy', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_tipos');
    }
};
