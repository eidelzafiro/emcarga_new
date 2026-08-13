<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifas_acuerdos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cliente')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('id_origen')->nullable()->constrained('lugares')->nullOnDelete();
            $table->foreignId('id_destino')->nullable()->constrained('lugares')->nullOnDelete();
            $table->foreignId('id_producto')->nullable()->constrained('productos')->nullOnDelete();
            $table->decimal('tarifa_mt', 12, 2)->nullable();
            $table->decimal('flete_mt', 12, 2)->nullable();
            $table->unsignedBigInteger('id_entidad')->nullable();
            $table->unsignedBigInteger('origen_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifas_acuerdos');
    }
};
