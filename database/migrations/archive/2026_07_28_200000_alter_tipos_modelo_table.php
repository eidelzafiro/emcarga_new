<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tipos_modelo', 'id_tipo_modelo')) {
            Schema::table('tipos_modelo', function ($table) {
                $table->dropForeign(['id_tipo_modelo']);
            });
            Schema::table('tipos_modelo', function ($table) {
                $table->dropColumn('id_tipo_modelo');
            });
        }

        if (Schema::hasColumn('tipos_modelo', 'modelo') && ! Schema::hasColumn('tipos_modelo', 'nombre')) {
            Schema::table('tipos_modelo', function ($table) {
                $table->renameColumn('modelo', 'nombre');
            });
        }

        if (! Schema::hasColumn('tipos_modelo', 'codigo')) {
            Schema::table('tipos_modelo', function ($table) {
                $table->unsignedBigInteger('codigo')->nullable()->after('id');
            });
            DB::update('UPDATE tipos_modelo SET codigo = id');
            Schema::table('tipos_modelo', function ($table) {
                $table->unsignedBigInteger('codigo')->nullable(false)->unique()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('tipos_modelo', function ($table) {
            $table->dropColumn('codigo');
        });

        Schema::table('tipos_modelo', function ($table) {
            $table->renameColumn('nombre', 'modelo');
            $table->string('codigo', 50)->nullable()->after('id');
            $table->unsignedBigInteger('id_tipo_modelo')->nullable()->after('codigo');
        });
    }
};
