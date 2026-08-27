<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Correcciones acordadas:
 *  1) Entidad id=2 con mojibake latin1 ("BAÃ'OS") -> "BAÑOS".
 *  2) Estados vacíos en legacy (idtipoestados NULL) -> dejar '' en nuevo (eran default ACTIVO).
 *  4) Arrastres: sembrar tipo_vehiculos desde el tipo_equipo legacy y enlazar id_tipo_vehiculo.
 *
 * Uso: php artisan zafiro:corregir-vehiculos
 */
class CorregirVehiculos extends Command
{
    protected $signature = 'zafiro:corregir-vehiculos';
    protected $description = 'Corrige entidad mojibake, estados vacíos y tipo_equipo de arrastres';

    public function handle(): int
    {
        $legacy = DB::connection('legacy');
        $nuevo = DB::connection('mysql');

        // ---- 1) Entidad mojibake ----
        $nombreOk = 'EMPRESA FILIAL CAMIONES SAN ANTONIO DE LOS BAÑOS';
        $antes = $nuevo->table('entidades')->where('id', 2)->value('nombre');
        if ($antes !== $nombreOk) {
            $nuevo->table('entidades')->where('id', 2)->update(['nombre' => $nombreOk]);
            $this->info("[1] entidad id=2: '{$antes}' -> '{$nombreOk}'");
        } else {
            $this->info('[1] entidad id=2 ya correcta.');
        }

        // ---- Lectura legacy resiliente (el contenedor mysql flapea por conexión) ----
        $d = $this->leerLegacy($legacy);
        $legA = $d['arrastres'];
        $lTE = $d['te'];

        // ---- 2) Estados legacy no mapeables (idtipoestados huérfano) -> '' ----
        // El nuevo guarda el string de tec_tipoestados; si el id no existe en esa
        // tabla, el estado es "vacío" en legacy y debe quedar en blanco en nuevo
        // (el ETL lo había defaultado a ACTIVO).
        $idsEst = [];
        foreach ($d['all'] as $r) {
            $txt = $d['teEst'][$r->idtipoestados] ?? null;
            if ($txt === null || $txt === '') {
                $idsEst[] = $r->idtractivos;
            }
        }
        $this->info('[2] legacy con estado NO mapeable (huérfano): ' . count($idsEst) . ' -> se dejan en blanco en nuevo.');
        if ($idsEst) {
            $n = $nuevo->table('tractivos')->whereIn('id', $idsEst)->where('estado', '!=', '')->update(['estado' => '']);
            $n += $nuevo->table('arrastres')->whereIn('id', $idsEst)->where('estado', '!=', '')->update(['estado' => '']);
            $this->info("     filas nuevas actualizadas (estado->''): {$n}");
        }

        // ---- 4) Arrastres: tipo_vehiculo desde tipo_equipo legacy ----
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
        $norm = function ($id) use ($lTE, $absorb, $GRUPOS) {
            if ($id === null) {
                return null;
            }
            $u = mb_strtoupper(trim($lTE[$id] ?? ''));
            return $absorb[$u] ?? (array_key_exists($u, $GRUPOS) ? $u : ($u ?: null));
        };

        // lookup tipos_equipos por nombre (upper)
        $tePorNombre = $nuevo->table('tipos_equipos')->pluck('id', 'nombre')
            ->mapWithKeys(fn ($v, $k) => [mb_strtoupper($k) => $v]);

        // Construir mapa: idtractivos -> tipo_vehiculos.id
        $tvCache = []; // teId -> tipo_vehiculos id
        $sinMap = [];
        $map = [];
        foreach ($legA as $r) {
            $eq = $norm($r->idtipoequipos);
            if ($eq === null) {
                $sinMap['(sin equipo)'] = ($sinMap['(sin equipo)'] ?? 0) + 1;
                continue;
            }
            $teId = $tePorNombre[mb_strtoupper($eq)]
                ?? $tePorNombre[mb_strtoupper(str_replace(' ', '-', $eq))]
                ?? null;
            if ($teId === null) {
                $sinMap[$eq] = ($sinMap[$eq] ?? 0) + 1;
                continue;
            }
            if (! isset($tvCache[$teId])) {
                $tv = $nuevo->table('tipo_vehiculos')
                    ->where('id_tipo_equipo', $teId)
                    ->where('clase', 'arrastre')
                    ->whereNull('id_marca')->whereNull('id_modelo')
                    ->first();
                if (! $tv) {
                    $tvId = $nuevo->table('tipo_vehiculos')->insertGetId([
                        'id_tipo_equipo' => $teId,
                        'clase' => 'arrastre',
                        'activo' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $tvId = $tv->id;
                }
                $tvCache[$teId] = $tvId;
                $this->info("[4] tipo_vehiculos creado/encontrado: teId={$teId} ({$eq}) -> tvId={$tvId}");
            }
            $map[$r->idtractivos] = $tvCache[$teId];
        }

        if ($sinMap) {
            $this->warn('[4] equipos legacy sin mapeo a tipos_equipos: ' . json_encode($sinMap, JSON_UNESCAPED_UNICODE));
        }

        // Actualizar arrastres.id_tipo_vehiculo
        $upd = 0;
        foreach ($map as $id => $tvId) {
            $nuevo->table('arrastres')->where('id', $id)->update(['id_tipo_vehiculo' => $tvId]);
            $upd++;
        }
        $this->info("[4] arrastres actualizados con id_tipo_vehiculo: {$upd}");

        // Verificación rápida
        $sinTv = $nuevo->table('arrastres')->whereNull('id_tipo_vehiculo')->count();
        $this->info("[4] arrastres aún sin id_tipo_vehiculo: {$sinTv}");

        return self::SUCCESS;
    }

    /**
     * Lee de legacy con reintentos: el contenedor mysql pierde la columna
     * idtractivos de forma intermitente, así que reconectamos y reintentamos.
     */
    private function leerLegacy($legacy): array
    {
        $max = 15;
        $last = null;
        for ($i = 1; $i <= $max; $i++) {
            try {
                $legacy->reconnect();
                $all = $legacy->table('tec_tractivos')
                    ->select('idtractivos', 'idtipoestados', 'idgrupo')
                    ->get();
                $teEst = $legacy->table('tec_tipoestados')->pluck('tipoestados', 'idtipoestados');
                $te = $legacy->table('tec_tipoequipos')->pluck('tipoequipos', 'idtipoequipos');
                $arr = $legacy->table('tec_tractivos as t')
                    ->join('tec_tipoarrastres as ta', 'ta.idtipoarrastres', '=', 't.idtipotractivos')
                    ->where('t.idgrupo', 8)
                    ->select('t.idtractivos', 'ta.idtipoequipos')
                    ->get();

                return ['all' => $all, 'teEst' => $teEst, 'te' => $te, 'arrastres' => $arr];
            } catch (\Throwable $e) {
                $last = $e;
                $this->warn("  reintento legacy {$i}/{$max}: " . $e->getMessage());
                usleep(300000);
            }
        }
        throw $last ?? new \RuntimeException('No se pudo leer legacy');
    }
}
