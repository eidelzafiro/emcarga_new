<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amortizacion_taller', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tractivo')->nullable()->constrained('tractivos')->nullOnDelete();
            $table->decimal('amortizacion_mn', 10, 2)->default(0);
            $table->decimal('chapa', 10, 2)->default(0);
            $table->foreignId('id_entidad')->nullable()->constrained('entidades')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amortizacion_taller');
    }
};
