<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_mantenimiento', function (Blueprint $table) {
            $table->unsignedBigInteger('kms_max')->nullable()->after('descripcion');
            $table->unsignedBigInteger('frecuencia')->nullable()->after('kms_max');
            $table->unsignedBigInteger('mtto_base')->nullable()->after('frecuencia');
            $table->unsignedBigInteger('holgura')->nullable()->after('mtto_base');
            $table->text('mttos')->nullable()->after('holgura');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_mantenimiento', function (Blueprint $table) {
            $table->dropColumn(['kms_max', 'frecuencia', 'mtto_base', 'holgura', 'mttos']);
        });
    }
};