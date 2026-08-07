<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Renombra f_uraconstruccion → f_reconstruccion (typo en migración 033000).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tractivos', function (Blueprint $table) {
            if (Schema::hasColumn('tractivos', 'f_uraconstruccion') && ! Schema::hasColumn('tractivos', 'f_reconstruccion')) {
                $table->renameColumn('f_uraconstruccion', 'f_reconstruccion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tractivos', function (Blueprint $table) {
            if (Schema::hasColumn('tractivos', 'f_reconstruccion') && ! Schema::hasColumn('tractivos', 'f_uraconstruccion')) {
                $table->renameColumn('f_reconstruccion', 'f_uraconstruccion');
            }
        });
    }
};
