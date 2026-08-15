<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * D1 (auditoría) — Etapa 1: normaliza `aforos`.
 *
 * Crea las tablas hijas `aforo_lineas` (líneas de tarifa 1-5) y
 * `aforo_indicadores` (filas de indicadores 1-7), y migra los datos existentes
 * desde las columnas repetidas de `aforos` y la tabla `indicadores`.
 *
 * En esta etapa NO se eliminan las columnas repetidas de `aforos` (se hace en
 * una migración posterior cuando el código use las tablas hijas).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('aforo_lineas')) {
            Schema::create('aforo_lineas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_aforo');
                $table->unsignedTinyInteger('posicion');
                $table->unsignedBigInteger('id_tipo_carga')->nullable();
                $table->unsignedInteger('distancia')->nullable();
                $table->decimal('peso_cobrar', 10, 3)->nullable();
                $table->decimal('descuento', 6, 2)->nullable();
                $table->decimal('tarifa_mt', 12, 2)->nullable();
                $table->decimal('flete_mt', 12, 2)->nullable();
                $table->decimal('flete_mlc', 12, 2)->nullable();
                $table->timestamps();

                $table->unique(['id_aforo', 'posicion']);
                $table->foreign('id_aforo')->references('id')->on('aforos')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('aforo_indicadores')) {
            Schema::create('aforo_indicadores', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_aforo');
                $table->unsignedTinyInteger('posicion');
                $table->decimal('tn_pos', 10, 2)->nullable();
                $table->decimal('tn_real', 10, 2)->nullable();
                $table->decimal('km_carga', 10, 2)->nullable();
                $table->decimal('km_vacio', 10, 2)->nullable();
                $table->decimal('km_total', 10, 2)->nullable();
                $table->decimal('traf_pos', 10, 2)->nullable();
                $table->decimal('traf_real', 10, 2)->nullable();
                $table->timestamps();

                $table->unique(['id_aforo', 'posicion']);
                $table->foreign('id_aforo')->references('id')->on('aforos')->cascadeOnDelete();
            });
        }

        // ----- Migrar líneas de tarifa (posiciones 1-5) desde `aforos` -----
        $camposLinea = [1 => 'tarifa_mt', 2 => 'flete_mt', 3 => 'flete_mlc', 4 => 'peso_cobrar', 5 => 'desc'];
        $aforos = DB::table('aforos')->select('id', 'id_tipo_carga_1', 'distancia_1', 'tarifa_mt_1', 'flete_mt_1', 'flete_mlc_1', 'peso_cobrar_1', 'desc_1', 'id_tipo_carga_2', 'distancia_2', 'tarifa_mt_2', 'flete_mt_2', 'flete_mlc_2', 'peso_cobrar_2', 'desc_2', 'id_tipo_carga_3', 'distancia_3', 'tarifa_mt_3', 'flete_mt_3', 'flete_mlc_3', 'peso_cobrar_3', 'desc_3', 'id_tipo_carga_4', 'distancia_4', 'tarifa_mt_4', 'flete_mt_4', 'flete_mlc_4', 'peso_cobrar_4', 'desc_4', 'id_tipo_carga_5', 'distancia_5', 'tarifa_mt_5', 'flete_mt_5', 'flete_mlc_5', 'peso_cobrar_5', 'desc_5')->get();

        $lineas = [];
        foreach ($aforos as $a) {
            for ($p = 1; $p <= 5; $p++) {
                $tc = $a->{'id_tipo_carga_'.$p};
                $dist = $a->{'distancia_'.$p};
                $tar = $a->{'tarifa_mt_'.$p};
                $fletemt = $a->{'flete_mt_'.$p};
                $fletemlc = $a->{'flete_mlc_'.$p};
                $peso = $a->{'peso_cobrar_'.$p};
                $desc = $a->{'desc_'.$p};

                // Una línea está vacía si TODOS sus campos son null o 0.
                $vacia = (($tc ?? 0) == 0)
                    && (($dist ?? 0) == 0)
                    && (($tar ?? 0) == 0)
                    && (($fletemt ?? 0) == 0)
                    && (($peso ?? 0) == 0);

                if ($vacia) {
                    continue;
                }

                $lineas[] = [
                    'id_aforo' => $a->id,
                    'posicion' => $p,
                    'id_tipo_carga' => $tc ?: null,
                    'distancia' => $dist ?: null,
                    'peso_cobrar' => $peso ?: null,
                    'descuento' => $desc ?: null,
                    'tarifa_mt' => $tar ?: null,
                    'flete_mt' => $fletemt ?: null,
                    'flete_mlc' => $fletemlc ?: null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        foreach (array_chunk($lineas, 500) as $lote) {
            DB::table('aforo_lineas')->insert($lote);
        }

        // ----- Migrar indicadores (posiciones 1-7) -----
        // Filas 1-2 viven en `aforos`; filas 3-7 viven en la tabla `indicadores`
        // (relacionada por id_carta_porte, con un aforo por carta).
        $ind = [];

        $aforosInd = DB::table('aforos')->select('id', 'id_carta_porte', 'tn_pos_1', 'tn_real_1', 'km_carga_1', 'km_vacio_1', 'km_total_1', 'traf_pos_1', 'traf_real_1', 'tn_pos_2', 'tn_real_2', 'km_carga_2', 'km_vacio_2', 'km_total_2', 'traf_pos_2', 'traf_real_2')->get();
        $indicadoresTabla = DB::table('indicadores')->get()->keyBy('id_carta_porte');

        foreach ($aforosInd as $a) {
            $filas = [];

            // posiciones 1-2 desde aforos
            for ($p = 1; $p <= 2; $p++) {
                $filas[$p] = [
                    'tn_pos' => $a->{'tn_pos_'.$p}, 'tn_real' => $a->{'tn_real_'.$p},
                    'km_carga' => $a->{'km_carga_'.$p}, 'km_vacio' => $a->{'km_vacio_'.$p},
                    'km_total' => $a->{'km_total_'.$p}, 'traf_pos' => $a->{'traf_pos_'.$p}, 'traf_real' => $a->{'traf_real_'.$p},
                ];
            }

            // posiciones 3-7 desde tabla indicadores (si existe)
            $reg = $indicadoresTabla->get($a->id_carta_porte);
            if ($reg) {
                for ($p = 3; $p <= 7; $p++) {
                    $filas[$p] = [
                        'tn_pos' => $reg->{'tn_pos_'.$p} ?? null, 'tn_real' => $reg->{'tn_real_'.$p} ?? null,
                        'km_carga' => $reg->{'km_carga_'.$p} ?? null, 'km_vacio' => $reg->{'km_vacio_'.$p} ?? null,
                        'km_total' => $reg->{'kms_total_'.$p} ?? null, 'traf_pos' => $reg->{'traf_pos_'.$p} ?? null, 'traf_real' => $reg->{'traf_real_'.$p} ?? null,
                    ];
                }
            }

            foreach ($filas as $p => $f) {
                $tieneValor = collect($f)->contains(fn ($v) => $v !== null && $v != 0);
                if (! $tieneValor) {
                    continue;
                }

                $ind[] = [
                    'id_aforo' => $a->id,
                    'posicion' => $p,
                    'tn_pos' => $f['tn_pos'], 'tn_real' => $f['tn_real'],
                    'km_carga' => $f['km_carga'], 'km_vacio' => $f['km_vacio'],
                    'km_total' => $f['km_total'], 'traf_pos' => $f['traf_pos'], 'traf_real' => $f['traf_real'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        foreach (array_chunk($ind, 500) as $lote) {
            DB::table('aforo_indicadores')->insert($lote);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('aforo_indicadores');
        Schema::dropIfExists('aforo_lineas');
    }
};
