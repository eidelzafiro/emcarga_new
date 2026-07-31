<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('tarifas_config_contenedor');
        Schema::dropIfExists('tarifas_config_carga');

        DB::connection('legacy')->statement('DROP TABLE IF EXISTS com_tarconfigcarga46');
        DB::connection('legacy')->statement('DROP TABLE IF EXISTS com_tarconfigcont46');
    }

    public function down(): void
    {
        // no restoration needed
    }
};
