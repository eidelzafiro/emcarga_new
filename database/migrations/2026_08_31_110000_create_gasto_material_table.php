<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gasto_material', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('id_tractivo')->nullable()->constrained('tractivos')->nullOnDelete();
            $table->string('nombre', 50)->comment('Nombre del material');
            $table->string('elemento', 70)->comment('Elemento/descripcion');
            $table->decimal('cantidad', 10, 3);
            $table->decimal('valor_mn', 12, 2)->default(0);
            $table->string('el_gas_mn', 10)->default('');
            $table->string('cup', 15)->default('');
            $table->unsignedInteger('num_mov')->default(0);
            $table->string('tipo_mov', 2)->default('E')->comment('E=entrada, S=salida');
            $table->unsignedInteger('nodoc')->default(0)->comment('Numero de documento');
            $table->foreignId('id_entidad')->nullable()->constrained('entidades')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gasto_material');
    }
};
