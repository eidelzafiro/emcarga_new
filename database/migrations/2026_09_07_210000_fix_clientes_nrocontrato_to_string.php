<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El legacy com_clientes.nrocontrato es varchar(15) y contiene valores de
        // texto (ej. 'TTC-19/2024'). La columna nueva se creó como int por error,
        // lo que impedía migrar ~62 clientes con contrato alfanumérico.
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('nrocontrato', 15)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            DB::statement('ALTER TABLE clientes MODIFY nrocontrato INT NULL');
        });
    }
};
