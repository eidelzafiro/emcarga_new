<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tractivos', function (Blueprint $table) {
            $table->dropUnique('tractivos_placa_unique');
            $table->dropUnique('tractivos_codigo_unique');
        });

        Schema::table('arrastres', function (Blueprint $table) {
            $table->dropUnique('tractivos_placa_unique');
            $table->dropUnique('tractivos_codigo_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tractivos', function (Blueprint $table) {
            $table->unique('placa', 'tractivos_placa_unique');
            $table->unique('codigo', 'tractivos_codigo_unique');
        });

        Schema::table('arrastres', function (Blueprint $table) {
            $table->unique('placa', 'tractivos_placa_unique');
            $table->unique('codigo', 'tractivos_codigo_unique');
        });
    }
};
