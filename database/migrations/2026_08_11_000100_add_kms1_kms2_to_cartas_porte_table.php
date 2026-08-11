<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cartas_porte', function (Blueprint $table) {
            $table->unsignedInteger('kms1')->nullable()->after('distancia');
            $table->unsignedInteger('kms2')->nullable()->after('kms1');
        });
    }

    public function down(): void
    {
        Schema::table('cartas_porte', function (Blueprint $table) {
            $table->dropColumn(['kms1', 'kms2']);
        });
    }
};
