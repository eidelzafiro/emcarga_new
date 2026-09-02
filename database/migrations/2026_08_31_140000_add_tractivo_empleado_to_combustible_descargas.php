<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('combustible_descargas', function (Blueprint $table) {
            $table->foreignId('id_tractivo')->nullable()->after('id_hoja_ruta')->constrained('tractivos')->nullOnDelete();
            $table->foreignId('id_empleado')->nullable()->after('id_tractivo')->constrained('bolsa')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('combustible_descargas', function (Blueprint $table) {
            $table->dropForeign(['id_tractivo']);
            $table->dropColumn('id_tractivo');
            $table->dropForeign(['id_empleado']);
            $table->dropColumn('id_empleado');
        });
    }
};
