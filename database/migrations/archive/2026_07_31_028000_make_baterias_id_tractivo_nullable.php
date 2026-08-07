<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 24/91 baterías legacy usan idtractivos=0 (sin tractivo asociado) →
     * id_tractivo debe ser nullable. La definición base decía nullable pero
     * la BD real quedó NOT NULL.
     */
    public function up(): void
    {
        Schema::table('baterias', function (Blueprint $table) {
            $table->foreignId('id_tractivo')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('baterias', function (Blueprint $table) {
            $table->foreignId('id_tractivo')->nullable(false)->change();
        });
    }
};
