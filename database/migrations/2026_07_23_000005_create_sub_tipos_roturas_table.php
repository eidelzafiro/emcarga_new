<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_tipos_roturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tipo_rotura')->constrained('tipos_roturas');
            $table->string('nombre', 250);
            $table->string('codigo', 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_tipos_roturas');
    }
};
