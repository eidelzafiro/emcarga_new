<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconciliarParidadVehiculos extends Command
{
    protected $signature = 'zafiro:reconciliar-paridad-vehiculos {--dry-run : Solo muestra lo que haria sin escribir}';
    protected $description = 'Deja tractivos/arrastres identicos al legacy (emcarga) por id, dividiendo tipo_vehiculo por tipo legacy.';

    private bool $dry = false;
    private array $log = ['tipos_equipos' => 0, 'tipos_mtto' => 0, 'marcas' => 0, 'modelos' => 0, 'tipo_vehiculo_creados' => 0, 'tipo_vehiculo_reusados' => 0, 'vehiculos_reasignados' => 0, 'placa' => 0, 'codigo' => 0];

    public function handle(): int
    {
        $this->dry = $this->option('dry-run');
        $leg = fn (string $t) => DB::connection('legacy')->table($t);
        $new = fn (string $t) => DB::table($t);

        $tiposEq = $leg('tec_tipoequipos')->pluck('tipoequipos', 'idtipoequipos')->toArray();
        $tiposMtto = $leg('tec_tipomtto')->pluck('tipomtto', 'idtipomtto')->toArray();
        $marcasLeg = $leg('tec_marca')->pluck('marca', 'idmarca')->toArray();
        $modelosLeg = $leg('tec_modelo')->pluck('modelo', 'idmodelo')->toArray();
        $legTP = $leg('tec_tipotractivos')->get()->keyBy('idtipotractivos');
        $legTA = $leg('tec_tipoarrastres')->get()->keyBy('idtipoarrastres');

        $marcasCat = DB::table('catalogo_items')->where('tipo', 'marcas')->pluck('id', 'origen_id')->toArray();
        $modelosCat = DB::table('catalogo_items')->where('tipo', 'modelos')->pluck('id', 'origen_id')->toArray();
        $tiposTractExisten = DB::table('tipos_tractivos')->pluck('id')->all();
        $tiposArrExisten = DB::table('tipos_arrastres')->pluck('id')->all();

        $resolverCatalogo = function (string $tipo, int $origenId, array &$cache, array $legNames) {
            if (isset($cache[$origenId])) {
                $id = $cache[$origenId];
            } else {
                $row = DB::table('catalogo_items')->where('tipo', $tipo)->where('origen_id', $origenId)->first();
                if (!$row) {
                    $row = DB::table('catalogo_items')->where('tipo', $tipo)->where('codigo', $origenId)->first();
                }
                if ($row) {
                    $id = $row->id;
                } else {
                    if ($this->dry) {
                        return null;
                    }
                    $id = DB::table('catalogo_items')->insertGetId([
                        'tipo' => $tipo, 'origen_id' => $origenId, 'codigo' => $origenId,
                        'nombre' => $legNames[$origenId] ?? 'DESCONOCIDO',
                        'extra' => json_encode([]), 'activo' => 1,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                $cache[$origenId] = $id;
            }
            if (!$this->dry) {
                DB::table('catalogo_items')->where('id', $id)->update([
                    'nombre' => $legNames[$origenId] ?? 'DESCONOCIDO',
                    'origen_id' => $origenId, 'updated_at' => now(),
                ]);
            }
            return $id;
        };

        // 1) Repoblar tipos_equipos / tipos_mantenimiento con ids/nombres exactos legacy
        foreach ($tiposEq as $id => $nom) {
            if (!$this->dry) {
                DB::table('tipos_equipos')->updateOrInsert(['id' => $id], ['nombre' => $nom, 'activo' => 1, 'created_at' => now(), 'updated_at' => now()]);
            }
            $this->log['tipos_equipos']++;
        }
        foreach ($tiposMtto as $id => $nom) {
            if (!$this->dry) {
                DB::table('tipos_mantenimiento')->updateOrInsert(['id' => $id], ['nombre' => $nom, 'activo' => 1, 'created_at' => now(), 'updated_at' => now()]);
            }
            $this->log['tipos_mtto']++;
        }

        // 2) Por cada vehiculo, derivar combo legacy y asegurar tipo_vehiculo dedicado
        $tvCache = []; // key combo -> id tipo_vehiculo
        foreach (DB::table('tipo_vehiculos')->get() as $tv) {
            $k = $tv->id_tipo_equipo . '_' . $tv->id_tipo_mantenimiento . '_' . $tv->id_marca . '_' . $tv->id_modelo;
            if (!isset($tvCache[$k])) {
                $tvCache[$k] = $tv->id;
            }
        }
        $descIds = []; // tipo_vehiculo generico por clase para anomalias legacy sin tipo
        if (!$this->dry) {
            foreach (['tractivo', 'arrastre'] as $clase) {
                $id = DB::table('tipo_vehiculos')->where('clase', $clase)->whereNull('id_tipo_equipo')->first()?->id;
                if (!$id) {
                    $id = DB::table('tipo_vehiculos')->insertGetId([
                        'clase' => $clase, 'activo' => 1,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                $descIds[$clase] = $id;
            }
        }
        $procesar = function (string $tabla, bool $esArrastre) use (
            $leg, $new, $legTP, $legTA, $marcasLeg, $modelosLeg, &$marcasCat, &$modelosCat, $resolverCatalogo, &$tvCache, $tiposTractExisten, $tiposArrExisten, &$descIds
        ) {
            $legRows = $leg('tec_tractivos')
                ->when($esArrastre, fn ($q) => $q->where('idgrupo', 8), fn ($q) => $q->where('idgrupo', '!=', 8))
                ->select('idtractivos', 'chapa', 'codtractivo', 'idtipotractivos')
                ->get();

            foreach ($legRows as $t) {
                $legType = $esArrastre ? ($legTA[$t->idtipotractivos] ?? null) : ($legTP[$t->idtipotractivos] ?? null);
                if (!$legType) {
                    if (!$this->dry) {
                        $new($tabla)->where('id', $t->idtractivos)->update([
                            'id_tipo_vehiculo' => $descIds[$esArrastre ? 'arrastre' : 'tractivo'],
                            'placa' => $t->chapa,
                            'codigo' => $t->codtractivo,
                        ]);
                    }
                    $this->log['vehiculos_reasignados']++;
                    $this->log['placa']++;
                    $this->log['codigo']++;
                    continue;
                }
                $idte = (int) $legType->idtipoequipos;
                $idmtto = (int) $legType->idtipomtto;
                $idm = $resolverCatalogo('marcas', (int) $legType->idmarca, $marcasCat, $marcasLeg);
                $idmo = $resolverCatalogo('modelos', (int) $legType->idmodelo, $modelosCat, $modelosLeg);

                $key = "{$idte}_{$idmtto}_{$idm}_{$idmo}";
                if (!isset($tvCache[$key])) {
                    if ($this->dry) {
                        $tvId = 'DRY';
                    } else {
                        $tvId = DB::table('tipo_vehiculos')->insertGetId([
                            'id_tipo_equipo' => $idte,
                            'id_tipo_mantenimiento' => $idmtto,
                            'id_marca' => $idm,
                            'id_modelo' => $idmo,
                            'id_tipo_tractivo' => (!$esArrastre && in_array((int) $t->idtipotractivos, $tiposTractExisten, true)) ? (int) $t->idtipotractivos : null,
                            'id_tipo_arrastre' => ($esArrastre && in_array((int) $t->idtipotractivos, $tiposArrExisten, true)) ? (int) $t->idtipotractivos : null,
                            'clase' => $esArrastre ? 'arrastre' : 'tractivo',
                            'activo' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        if (isset($tiposEq[$idte])) {
                            $this->log['tipo_vehiculo_creados']++;
                        } else {
                            $this->log['tipo_vehiculo_reusados']++;
                        }
                    }
                    $tvCache[$key] = $tvId;
                } else {
                    $tvId = $tvCache[$key];
                    if (!$this->dry) {
                        $this->log['tipo_vehiculo_reusados']++;
                    }
                }

                if (!$this->dry) {
                    $new($tabla)->where('id', $t->idtractivos)->update([
                        'id_tipo_vehiculo' => $tvId,
                        'placa' => $t->chapa,
                        'codigo' => $t->codtractivo,
                    ]);
                }
                $this->log['vehiculos_reasignados']++;
                $this->log['placa']++;
                $this->log['codigo']++;
            }
        };

        $procesar('tractivos', false);
        $procesar('arrastres', true);

        // 3) Limpiar tipos_equipos fuera de legacy y tipo_vehiculos huerfanos
        if (!$this->dry) {
            $legIds = array_keys($tiposEq);
            $borradosEq = DB::table('tipos_equipos')->whereNotIn('id', $legIds)->delete();
            $this->info("tipos_equipos eliminados (fuera de legacy): {$borradosEq}");

            $usados = DB::table('tractivos')->whereNotNull('id_tipo_vehiculo')->distinct()->pluck('id_tipo_vehiculo')->all();
            $usadosA = DB::table('arrastres')->whereNotNull('id_tipo_vehiculo')->distinct()->pluck('id_tipo_vehiculo')->all();
            $usados = array_unique(array_merge($usados, $usadosA));
            $huerfanos = DB::table('tipo_vehiculos')->whereNotIn('id', $usados)->delete();
            $this->info("tipo_vehiculos huerfanos eliminados: {$huerfanos}");
        }

        $this->info('Resumen: ' . json_encode($this->log));
        $this->info($this->dry ? 'DRY-RUN: no se escribio nada.' : 'Reconciliacion aplicada. Re-ejecuta zafiro:exportar-comparacion-vehiculos para verificar 0 diffs.');
        return self::SUCCESS;
    }
}
