<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_tractivos', function (Blueprint $table) {
            if (Schema::hasColumn('tipos_tractivos', 'tipo_equipo')) {
                $table->dropColumn('tipo_equipo');
            }
            if (Schema::hasColumn('tipos_tractivos', 'fabricacion')) {
                $table->dropColumn('fabricacion');
            }
        });

        Schema::table('tipos_arrastres', function (Blueprint $table) {
            if (Schema::hasColumn('tipos_arrastres', 'fabricacion')) {
                $table->dropColumn('fabricacion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tipos_tractivos', function (Blueprint $table) {
            if (! Schema::hasColumn('tipos_tractivos', 'tipo_equipo')) {
                $table->string('tipo_equipo')->nullable();
            }
            if (! Schema::hasColumn('tipos_tractivos', 'fabricacion')) {
                $table->integer('fabricacion')->nullable();
            }
        });

        Schema::table('tipos_arrastres', function (Blueprint $table) {
            if (! Schema::hasColumn('tipos_arrastres', 'fabricacion')) {
                $table->integer('fabricacion')->nullable();
            }
        });
    }
};
