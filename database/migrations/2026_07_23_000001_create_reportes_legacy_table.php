<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_legacy', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255)->nullable();
            $table->string('controlador', 150)->nullable();
            $table->string('tipo', 50);
            $table->string('variable', 50)->nullable();
            $table->boolean('rechum')->default(false);
            $table->boolean('com')->default(false);
            $table->boolean('cont')->default(false);
            $table->boolean('conte')->default(false);
            $table->boolean('tec')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_legacy');
    }
};
