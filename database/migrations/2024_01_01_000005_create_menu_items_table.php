<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ítems del menú lateral, filtrados por permisos (decisión D7).
        // Jerárquicos: un ítem padre sin ruta agrupa a sus hijos.
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('label', 100);
            $table->string('icon', 50)->nullable();      // nombre de ícono (se renderiza en Fase 4.6)
            $table->string('route', 150)->nullable();    // nombre de ruta Laravel; null = agrupador
            $table->string('permission', 150)->nullable(); // permiso requerido; null = visible para todos
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'orden']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
