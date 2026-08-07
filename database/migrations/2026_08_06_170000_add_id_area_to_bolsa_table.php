<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bolsa', function (Blueprint $table) {
            $table->unsignedBigInteger('id_area')->nullable()->after('id_cargo');

            $table->foreign('id_area')
                ->references('id')
                ->on('areas')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('bolsa', function (Blueprint $table) {
            $table->dropForeign(['id_area']);
            $table->dropColumn('id_area');
        });
    }
};