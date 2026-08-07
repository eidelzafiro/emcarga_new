<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hojas_ruta', function (Blueprint $table) {
            if (Schema::hasColumn('hojas_ruta', 'id_tractivo')) {
                $table->unsignedBigInteger('id_tractivo')->nullable()->change();
            }

            if (Schema::hasColumn('hojas_ruta', 'id_entidad')) {
                $table->unsignedBigInteger('id_entidad')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('hojas_ruta', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tractivo')->nullable(false)->change();
        });
    }
};