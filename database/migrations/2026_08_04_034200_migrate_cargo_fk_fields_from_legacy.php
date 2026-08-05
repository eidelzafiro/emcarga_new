<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacy = DB::connection('legacy');

        // rh_tipocalificadores → calificadores (ya migrados en seed)
        // rh_tipocatcargos → categorias_cargo (ya tienen codigo = idtipocatcargos en ETL)
        // etc.

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

            // Calificador: buscar en calificadores por codigo (codigo = idtipocalificadores legacy)
            if ($lc->idtipocalificadores) {
                $cal = DB::table('calificadores')->where('codigo', (string) $lc->idtipocalificadores)->first();
                if ($cal) {
                    $data['id_calificador'] = $cal->id;
                }
            }

            // Fondo tiempo: fondos_tiempo (ya tiene datos ETL, usar id directo si coinciden)
            // La tabla fondos_tiempo fue migrada por ETL con sus ids legacy preservados
            if ($lc->idfondotiempo) {
                $ft = DB::table('fondos_tiempo')->where('id', $lc->idfondotiempo)->first();
                if ($ft) {
                    $data['id_fondo_tiempo'] = $lc->idfondotiempo;
                }
            }

            // Nivel educacion
            if ($lc->idtiponiveducacion) {
                $ne = DB::table('tipos_nivel_educacion')->where('id', $lc->idtiponiveducacion)->first();
                if ($ne) {
                    $data['id_nivel_educacion'] = $lc->idtiponiveducacion;
                }
            }

            // Grupo escala
            if ($lc->idgruposescala) {
                $ge = DB::table('grupos_escala')->where('id', $lc->idgruposescala)->first();
                if ($ge) {
                    $data['id_grupo_escala'] = $lc->idgruposescala;
                    // Calcular salario_escala desde grupo_escala
                    $data['salario_escala'] = round((float) ($ge->salario ?? 0) + (float) ($lc->cla ?? 0), 2);
                }
            }

            // Clasificación laboral
            if ($lc->idtipoclasflaboral) {
                $cl = DB::table('tipos_clasificacion_laboral')->where('id', $lc->idtipoclasflaboral)->first();
                if ($cl) {
                    $data['id_clasificacion_laboral'] = $lc->idtipoclasflaboral;
                }
            }

            // Categoría cargo
            if ($lc->idtipocatcargos) {
                $cc = DB::table('categorias_cargo')->where('id', $lc->idtipocatcargos)->first();
                if ($cc) {
                    $data['id_categoria_cargo'] = $lc->idtipocatcargos;
                }
            }

            // Grupo horario
            if ($lc->idtipogrupohorario) {
                $gh = DB::table('tipos_grupo_horario')->where('id', $lc->idtipogrupohorario)->first();
                if ($gh) {
                    $data['id_grupo_horario'] = $lc->idtipogrupohorario;
                }
            }

            $data['tipo_salario'] = $lc->tipsalario;
            $data['en_salario'] = $lc->ensalario;
            $data['tarifa'] = $lc->tarifa;
            $data['cla'] = $lc->cla;
            $data['noct1'] = $lc->noct1;
            $data['noct2'] = $lc->noct2;
            $data['pago_adicional'] = $lc->padicional;
            $data['aseo_tecnologico'] = (bool) $lc->aseo;

            DB::table('cargos')->where('id', $lc->idcargos)->update($data);
        }
    }

    public function down(): void
    {
        DB::table('cargos')->update([
            'id_calificador' => null,
            'id_fondo_tiempo' => null,
            'id_nivel_educacion' => null,
            'id_grupo_escala' => null,
            'id_clasificacion_laboral' => null,
            'id_categoria_cargo' => null,
            'id_grupo_horario' => null,
            'tipo_salario' => 1,
            'en_salario' => 1,
            'tarifa' => null,
            'salario_escala' => null,
            'cla' => null,
            'noct1' => null,
            'noct2' => null,
            'pago_adicional' => null,
            'aseo_tecnologico' => false,
        ]);
    }
};
