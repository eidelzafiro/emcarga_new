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
        Schema::table('tasas', function (Blueprint $table) {
            $table->integer('version')->default(1)->after('nombre');
            $table->date('fecha_inicio')->nullable()->after('version');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            $table->boolean('activo')->default(true)->after('fecha_fin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasas', function (Blueprint $table) {
            $table->dropColumn(['version', 'fecha_inicio', 'fecha_fin', 'activo']);
        });
    }
};
