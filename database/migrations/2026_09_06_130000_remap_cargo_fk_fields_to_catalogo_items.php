<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Re-mapea los campos FK de `cargos` a las tablas actuales (catálogo unificado)
 * después de la FASE 3 de unificación (2026-08-23).
 *
 * La migración 2026_08_04_034200 (anterior a la FASE 3) resolvía estos campos
 * contra tablas dedicadas (categorias_cargo, tipos_nivel_educacion,
 * tipos_clasificacion_laboral, tipos_grupo_horario) que luego fueron eliminadas
 * y absorbidas por catalogo_items. Al re-correr migrate:fresh post-FASE 3 esa
 * migración quedó apuntando a tablas inexistentes y dejó en NULL:
 *   - id_categoria_cargo (nombcatcargo OBRERO/OPERARIO → regular1)
 *   - id_fondo_tiempo (fondotiempo → regular1 y "ESCALA" de la prenómina)
 *   - id_nivel_educacion (maestría)
 *   - id_clasificacion_laboral / id_calificador
 * y `en_salario` con el valor por defecto (1) en vez del real del legacy.
 *
 * Idempotente: actualiza por codigo (= idcargos legacy).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->legacyDisponible('rh_cargos')) {
            return;
        }

        $legacy = DB::connection('legacy');
        $legacyCargos = $legacy->table('rh_cargos')
            ->select(
                'idcargos', 'idtipocalificadores', 'idfondotiempo',
                'idtiponiveducacion', 'idgruposescala', 'idtipoclasflaboral',
                'idtipocatcargos', 'idtipogrupohorario',
                'tipsalario', 'ensalario', 'tarifa', 'cla',
                'padicional', 'noct1', 'noct2', 'aseo'
            )
            ->get();

        foreach ($legacyCargos as $lc) {
            $data = [];

            if ($lc->idtipocalificadores) {
                $cal = DB::table('calificadores')->where('codigo', (string) $lc->idtipocalificadores)->first();
                if ($cal) $data['id_calificador'] = $cal->id;
            }

            if ($lc->idfondotiempo && DB::table('fondos_tiempo')->where('id', $lc->idfondotiempo)->exists()) {
                $data['id_fondo_tiempo'] = $lc->idfondotiempo;
            }

            if ($lc->idtiponiveducacion) {
                $ci = DB::table('catalogo_items')->where('tipo', 'tipos_nivel_educacion')
                    ->where('origen_id', $lc->idtiponiveducacion)->value('id');
                if ($ci) $data['id_nivel_educacion'] = $ci;
            }

            if ($lc->idgruposescala && DB::table('grupos_escala')->where('id', $lc->idgruposescala)->exists()) {
                $data['id_grupo_escala'] = $lc->idgruposescala;
                $ge = DB::table('grupos_escala')->where('id', $lc->idgruposescala)->first();
                $data['salario_escala'] = round((float) ($ge->salario ?? 0) + (float) ($lc->cla ?? 0), 2);
            }

            if ($lc->idtipoclasflaboral) {
                $ci = DB::table('catalogo_items')->where('tipo', 'tipos_clasificacion_laboral')
                    ->where('origen_id', $lc->idtipoclasflaboral)->value('id');
                if ($ci) $data['id_clasificacion_laboral'] = $ci;
            }

            if ($lc->idtipocatcargos) {
                $ci = DB::table('catalogo_items')->where('tipo', 'categorias_cargo')
                    ->where('origen_id', $lc->idtipocatcargos)->value('id');
                if ($ci) $data['id_categoria_cargo'] = $ci;
            }

            if ($lc->idtipogrupohorario) {
                $ci = DB::table('catalogo_items')->where('tipo', 'tipos_grupo_horario')
                    ->where('origen_id', $lc->idtipogrupohorario)->value('id');
                if ($ci) $data['id_grupo_horario'] = $ci;
            }

            $data['tipo_salario'] = $lc->tipsalario;
            $data['en_salario'] = $lc->ensalario;
            $data['tarifa'] = $lc->tarifa;
            $data['cla'] = $lc->cla;
            $data['noct1'] = $lc->noct1;
            $data['noct2'] = $lc->noct2;
            $data['pago_adicional'] = $lc->padicional;
            $data['aseo_tecnologico'] = (bool) $lc->aseo;

            DB::table('cargos')->where('codigo', (string) $lc->idcargos)->update($data);
        }
    }

    public function down(): void
    {
        // No se revierte — los valores correctos son los del legacy.
    }

    private function legacyDisponible(string $tabla): bool
    {
        try {
            return DB::connection('legacy')->getSchemaBuilder()->hasTable($tabla);
        } catch (Throwable) {
            return false;
        }
    }
};
