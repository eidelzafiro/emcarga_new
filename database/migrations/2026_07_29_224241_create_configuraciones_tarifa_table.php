<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones_tarifa', function (Blueprint $table) {
            $table->id();
            $table->decimal('demora_1', 10, 2)->default(0);
            $table->decimal('demora_2', 10, 2)->default(0);
            $table->decimal('kms_vacio_1', 10, 2)->default(0);
            $table->decimal('kms_vacio_2', 10, 2)->default(0);
            $table->decimal('tarifa_horaria_1', 10, 2)->default(0);
            $table->decimal('tarifa_horaria_2', 10, 2)->default(0);
            $table->decimal('kms_adicionales_1', 10, 2)->default(0);
            $table->decimal('kms_adicionales_2', 10, 2)->default(0);
            $table->decimal('almacenaje', 10, 2)->default(0);
            $table->decimal('recargo_1', 10, 2)->default(0);
            $table->decimal('recargo_2', 10, 2)->default(0);
            $table->decimal('recargo_3_1', 10, 2)->default(0);
            $table->decimal('recargo_3_2', 10, 2)->default(0);
            $table->decimal('recargo_3_3', 10, 2)->default(0);
            $table->decimal('recargo_4', 10, 2)->default(0);
            $table->decimal('recargo_5', 10, 2)->default(0);
            $table->integer('hora_1')->default(0);
            $table->integer('hora_2')->default(0);
            $table->integer('hora_3')->default(0);
            $table->decimal('izaje_1', 10, 2)->default(0);
            $table->decimal('izaje_2', 10, 2)->default(0);
            $table->decimal('valor_izaje_mt', 10, 2)->default(0);
            $table->decimal('valor_izaje_me', 10, 2)->default(0);
            $table->decimal('valor_almacenaje', 10, 2)->default(0);
            $table->integer('plazo_libre_exp')->default(0);
            $table->timestamps();
        });

        $legacyCarga = DB::connection('legacy')->table('com_tarconfigcarga')->first();
        $legacyCont = DB::connection('legacy')->table('com_tarconfigcont')->first();

        if ($legacyCarga || $legacyCont) {
            DB::table('configuraciones_tarifa')->insert([
                'demora_1' => $legacyCarga->demora1 ?? ($legacyCont->demora1 ?? 0),
                'demora_2' => $legacyCarga->demora2 ?? ($legacyCont->demora2 ?? 0),
                'kms_vacio_1' => $legacyCarga->kmsvacio1 ?? ($legacyCont->kmsvacio1 ?? 0),
                'kms_vacio_2' => $legacyCarga->kmsvacio2 ?? ($legacyCont->kmsvacio2 ?? 0),
                'tarifa_horaria_1' => $legacyCarga->tarhor1 ?? ($legacyCont->tarhor1 ?? 0),
                'tarifa_horaria_2' => $legacyCarga->tarhor2 ?? 0,
                'kms_adicionales_1' => $legacyCarga->kmsadic1 ?? 0,
                'kms_adicionales_2' => $legacyCarga->kmsadic2 ?? 0,
                'almacenaje' => $legacyCarga->almacenaje ?? 0,
                'recargo_1' => $legacyCarga->recargo1 ?? 0,
                'recargo_2' => $legacyCarga->recargo2 ?? 0,
                'recargo_3_1' => $legacyCarga->recargo31 ?? 0,
                'recargo_3_2' => $legacyCarga->recargo32 ?? 0,
                'recargo_3_3' => $legacyCarga->recargo33 ?? 0,
                'recargo_4' => $legacyCarga->recargo4 ?? 0,
                'recargo_5' => $legacyCarga->recargo5 ?? 0,
                'hora_1' => $legacyCarga->hora1 ?? 0,
                'hora_2' => $legacyCarga->hora2 ?? 0,
                'hora_3' => $legacyCarga->hora3 ?? 0,
                'izaje_1' => $legacyCont->izaje1 ?? 0,
                'izaje_2' => $legacyCont->izaje2 ?? 0,
                'valor_izaje_mt' => $legacyCont->vizajemt ?? 0,
                'valor_izaje_me' => $legacyCont->vizajeme ?? 0,
                'valor_almacenaje' => $legacyCont->valmacenaje ?? 0,
                'plazo_libre_exp' => $legacyCont->plibreexp ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones_tarifa');
    }
};
