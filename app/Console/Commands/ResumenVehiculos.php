<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Resume y compara tractivos/arrastres (legacy vs nuevo) por las dimensiones:
 * marca, modelo, tipo_equipo (normalizado), tipo_combustible, color_primario,
 * color_secundario, estado y entidad. Muestra conteo por valor y si coinciden.
 *
 * Uso: php artisan zafiro:resumen-vehiculos
 */
class ResumenVehiculos extends Command
{
    protected $signature = 'zafiro:resumen-vehiculos';
    protected $description = 'Resumen comparativo legacy vs nuevo por dimensiones de vehículos';

    public function handle(): int
    {
        $legacy = DB::connection('legacy');
        $nuevo = DB::connection('mysql');

        // ---- Lookups legacy ----
        $lMarca = $legacy->table('tec_marca')->pluck('marca', 'idmarca');
        $lModelo = $legacy->table('tec_modelo')->pluck('modelo', 'idmodelo');
        $lTE = $legacy->table('tec_tipoequipos')->pluck('tipoequipos', 'idtipoequipos');
        $lTC = $legacy->table('tec_tipocombustibles')->pluck('tipocombustibles', 'idtipocombustibles');
        $lColor = $legacy->table('tec_colores')->pluck('colores', 'idcolores');
        $lEst = $legacy->table('tec_tipoestados')->pluck('tipoestados', 'idtipoestados');
        $lEnt = $legacy->table('rh_entidades')->pluck('nombentidad', 'identidades');

        // ---- Lookups nuevo ----
        $nTE = $nuevo->table('tipos_equipos')->pluck('nombre', 'id');
        $nTC = $nuevo->table('tipos_combustibles')->pluck('nombre', 'id');
        $nColor = $nuevo->table('catalogo_items')->whereNull('deleted_at')->pluck('nombre', 'id');
        $nEst = $nuevo->table('estados_componentes')->pluck('nombre', 'id');
        $nEnt = $nuevo->table('entidades')->pluck('nombre', 'id');

        // Normalizador tipo_equipo
        $GRUPOS = [
            'CUÑA TRACTORA' => ['CUÑAS TRACTORAS'],
            'OMNIBUS' => [], 'CISTERNA' => ['CAMION CISTERNA AGUA'],
            'AUTO' => ['AUTO LIGERO', 'AUTO ESPECIAL'],
            'GRUA' => ['CAMION GRUA'], 'VOLTEO' => ['CAMION VOLTEO', 'S/R VOLTEO'],
        ];
        $absorb = [];
        foreach ($GRUPOS as $f => $as) {
            foreach ($as as $a) {
                $absorb[mb_strtoupper($a)] = $f;
            }
        }
        $normTE = function ($id) use ($lTE, $absorb, $GRUPOS) {
            if ($id === null) {
                return null;
            }
            $u = mb_strtoupper(trim($lTE[$id] ?? ''));
            return $absorb[$u] ?? (array_key_exists($u, $GRUPOS) ? $u : ($u ?: null));
        };

        // ---- Cargar legacy ----
        $legacyT = $legacy->table('tec_tractivos as t')
            ->leftJoin('tec_tipotractivos as tp', 'tp.idtipotractivos', '=', 't.idtipotractivos')
            ->where('t.idgrupo', '!=', 8)
            ->select('t.*', 'tp.idtipoequipos', 'tp.idtipocombustibles', 'tp.idmarca', 'tp.idmodelo')
            ->get();
        $legacyA = $legacy->table('tec_tractivos as t')
            ->leftJoin('tec_tipoarrastres as ta', 'ta.idtipoarrastres', '=', 't.idtipotractivos')
            ->where('t.idgrupo', '=', 8)
            ->select('t.*', 'ta.idtipoequipos')
            ->get();

        $leg = [];
        foreach ($legacyT as $l) {
            $leg[] = [
                'marca' => $lMarca[$l->idmarca] ?? null,
                'modelo' => $lModelo[$l->idmodelo] ?? null,
                'tipo_equipo' => $normTE($l->idtipoequipos),
                'tipo_combustible' => $lTC[$l->idtipocombustibles] ?? null,
                'color_primario' => $lColor[$l->idcolorprimario] ?? null,
                'color_secundario' => $lColor[$l->idcolorsecundario] ?? null,
                'estado' => $lEst[$l->idtipoestados] ?? null,
                'entidad' => $lEnt[$l->idunidad] ?? null,
            ];
        }
        foreach ($legacyA as $l) {
            $leg[] = [
                'marca' => null, 'modelo' => null,
                'tipo_equipo' => $normTE($l->idtipoequipos),
                'tipo_combustible' => null,
                'color_primario' => $lColor[$l->idcolorprimario] ?? null,
                'color_secundario' => $lColor[$l->idcolorsecundario] ?? null,
                'estado' => $lEst[$l->idtipoestados] ?? null,
                'entidad' => $lEnt[$l->idunidad] ?? null,
            ];
        }

        // ---- Cargar nuevo ----
        $nT = $nuevo->table('tractivos')->whereNull('deleted_at')->get();
        $nA = $nuevo->table('arrastres')->whereNull('deleted_at')->get();
        $nue = [];
        foreach ($nT as $r) {
            $nue[] = [
                'marca' => $r->marca,
                'modelo' => $r->modelo,
                'tipo_equipo' => $nTE[$r->id_tipo_equipo] ?? null,
                'tipo_combustible' => $nTC[$r->id_tipo_combustible] ?? null,
                'color_primario' => $nColor[$r->id_color_primario] ?? null,
                'color_secundario' => $nColor[$r->id_color_secundario] ?? null,
                'estado' => $r->estado,
                'entidad' => $nEnt[$r->id_entidad] ?? null,
            ];
        }
        foreach ($nA as $r) {
            $nue[] = [
                'marca' => $r->marca ?? null,
                'modelo' => $r->modelo ?? null,
                'tipo_equipo' => null,
                'tipo_combustible' => null,
                'color_primario' => $nColor[$r->id_color_primario] ?? null,
                'color_secundario' => $nColor[$r->id_color_secundario] ?? null,
                'estado' => $r->estado,
                'entidad' => $nEnt[$r->id_entidad] ?? null,
            ];
        }

        $this->info("Legacy vehículos: " . count($leg) . " | Nuevo vehículos: " . count($nue));
        $this->info('');

        $dims = ['marca', 'modelo', 'tipo_equipo', 'tipo_combustible', 'color_primario', 'color_secundario', 'estado', 'entidad'];
        foreach ($dims as $dim) {
            $this->resumenDimension($dim, $leg, $nue);
        }

        return self::SUCCESS;
    }

    private function resumenDimension(string $dim, array $leg, array $nue): void
    {
        $cLeg = [];
        $cNue = [];
        foreach ($leg as $r) {
            $v = $r[$dim] ?? null;
            $v = $v === null || $v === '' ? '(vacío)' : mb_strtoupper(trim($v));
            $cLeg[$v] = ($cLeg[$v] ?? 0) + 1;
        }
        foreach ($nue as $r) {
            $v = $r[$dim] ?? null;
            $v = $v === null || $v === '' ? '(vacío)' : mb_strtoupper(trim($v));
            $cNue[$v] = ($cNue[$v] ?? 0) + 1;
        }

        $vals = array_unique(array_merge(array_keys($cLeg), array_keys($cNue)));
        usort($vals, fn ($a, $b) => ($cLeg[$b] ?? 0) <=> ($cLeg[$a] ?? 0));
        $filas = [];
        $noCoinc = 0;
        foreach ($vals as $v) {
            $a = $cLeg[$v] ?? 0;
            $b = $cNue[$v] ?? 0;
            $ok = $a === $b;
            if (! $ok) {
                $noCoinc++;
            }
            $filas[] = [$v, $a, $b, $ok ? 'SI' : 'NO'];
        }
        $this->info("=== {$dim} ===" . ($noCoinc ? "  ({$noCoinc} valores NO coinciden)" : '  (coincide)'));
        $this->table(['Valor', 'Legacy', 'Nuevo', 'Coincide'], $filas);
        $this->info('');
    }
}
