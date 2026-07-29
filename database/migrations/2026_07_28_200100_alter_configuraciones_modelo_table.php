<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('configuraciones_modelo', 'id_tipo_modelo')) {
            try {
                Schema::table('configuraciones_modelo', function ($table) {
                    $table->dropForeign(['id_tipo_modelo']);
                });
            } catch (\Exception $e) {
                // FK might not exist
            }

            Schema::table('configuraciones_modelo', function ($table) {
                $table->renameColumn('id_tipo_modelo', 'codigo_tipo_modelo');
            });

            Schema::table('configuraciones_modelo', function ($table) {
                $table->unsignedBigInteger('codigo_tipo_modelo')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('configuraciones_modelo', function ($table) {
            $table->renameColumn('codigo_tipo_modelo', 'id_tipo_modelo');
        });
    }
};
