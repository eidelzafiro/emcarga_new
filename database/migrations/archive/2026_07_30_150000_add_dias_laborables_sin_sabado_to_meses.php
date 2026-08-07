<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meses', function (Blueprint $table) {
            $table->decimal('dias_laborables_sin_sabado', 6, 2)->nullable()->after('dias_laborables');
        });
    }

    public function down(): void
    {
        Schema::table('meses', function (Blueprint $table) {
            $table->dropColumn('dias_laborables_sin_sabado');
        });
    }
};
