<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meses', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('dias_laborables_sin_sabado');
        });
    }

    public function down(): void
    {
        Schema::table('meses', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
