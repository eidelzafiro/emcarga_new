<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantilla', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cargo')->nullable()->constrained('cargos')->nullOnDelete();
            $table->foreignId('id_area')->nullable()->constrained('areas')->nullOnDelete();
            $table->unsignedInteger('aprobada')->default(0);
            $table->unsignedInteger('cubierta')->default(0);
            $table->integer('cubierta2')->default(0);
            $table->unsignedInteger('propuesta')->default(0);
            $table->unsignedInteger('v_necesidad')->default(0);
            $table->unsignedInteger('necesidad')->default(0);
            $table->foreignId('id_entidad')->nullable()->constrained('entidades')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantilla');
    }
};
