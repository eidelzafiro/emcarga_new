<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bolsa', function (Blueprint $table) {
            $table->date('licencia_emision')->nullable()->after('categorias_licencia');
            $table->date('licencia_vencimiento')->nullable()->after('licencia_emision');
        });
    }

    public function down(): void
    {
        Schema::table('bolsa', function (Blueprint $table) {
            $table->dropColumn(['licencia_emision', 'licencia_vencimiento']);
        });
    }
};
