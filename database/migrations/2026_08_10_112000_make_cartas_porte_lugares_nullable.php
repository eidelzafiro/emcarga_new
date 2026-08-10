<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cartas_porte', function (Blueprint $table) {
            $table->unsignedBigInteger('id_lugar_origen')->nullable()->change();
            $table->unsignedBigInteger('id_lugar_destino')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cartas_porte', function (Blueprint $table) {
            $table->unsignedBigInteger('id_lugar_origen')->nullable(false)->change();
            $table->unsignedBigInteger('id_lugar_destino')->nullable(false)->change();
        });
    }
};