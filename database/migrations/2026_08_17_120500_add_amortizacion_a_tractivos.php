<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade a `tractivos` los campos de amortización y chapa del legacy
 * `tec_tractivos` (amortmn, amortme, vchapa), necesarios para el cálculo
 * de costos por tractivo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tractivos', function (Blueprint $table) {
            $table->decimal('amortmn', 10, 2)->default(0);
            $table->decimal('amortme', 10, 2)->default(0);
            $table->decimal('vchapa', 10, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('tractivos', function (Blueprint $table) {
            $table->dropColumn(['amortmn', 'amortme', 'vchapa']);
        });
    }
};
