<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_items', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 100);
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->json('extra')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['tipo', 'codigo']);
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_items');
    }
};
