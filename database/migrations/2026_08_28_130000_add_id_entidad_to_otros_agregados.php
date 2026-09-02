<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otros_agregados', function (Blueprint $table) {
            $table->unsignedBigInteger('id_entidad')->nullable()->after('id_estado');
            $table->index('id_entidad');
        });

        // Backfill: todo lo existente pasa a la OFICINA CENTRAL (matriz, id 23).
        DB::table('otros_agregados')->whereNull('id_entidad')->update(['id_entidad' => 23]);
    }

    public function down(): void
    {
        Schema::table('otros_agregados', function (Blueprint $table) {
            $table->dropIndex(['id_entidad']);
            $table->dropColumn('id_entidad');
        });
    }
};
