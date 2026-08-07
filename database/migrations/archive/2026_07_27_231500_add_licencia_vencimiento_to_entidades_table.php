<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entidades', function (Blueprint $table) {
            $table->date('licencia_vencimiento')->nullable()->after('licencia');
            $table->boolean('licencia_activa')->default(true)->after('licencia_vencimiento');
        });
    }

    public function down(): void
    {
        Schema::table('entidades', function (Blueprint $table) {
            $table->dropColumn(['licencia_vencimiento', 'licencia_activa']);
        });
    }
};
