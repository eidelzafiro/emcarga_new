<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otros_gastos', function (Blueprint $table) {
            $table->string('numero', 50)->nullable()->after('id')->index('otros_gastos_numero_index');
        });
    }

    public function down(): void
    {
        Schema::table('otros_gastos', function (Blueprint $table) {
            $table->dropIndex('otros_gastos_numero_index');
            $table->dropColumn('numero');
        });
    }
};
