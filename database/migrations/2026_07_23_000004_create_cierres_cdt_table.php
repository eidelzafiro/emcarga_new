<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierres_cdt', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->nullable();
            $table->float('tiempo_general', 10, 0)->nullable();
            $table->float('tiempo_taller', 10, 0)->nullable();
            $table->float('porcentaje', 10, 0)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres_cdt');
    }
};
