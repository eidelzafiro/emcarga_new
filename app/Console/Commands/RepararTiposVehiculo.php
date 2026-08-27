<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Repara el FK id_tipo_vehiculo de tractivos y arrastres para que apunte al
 * tipo_vehiculos coherente con la identidad real del vehiculo (marca/modelo).
 *
 * El ETL denormalizo marca/modelo en tractivos (correcto) pero el mapa
 * idtipotractivos -> tipo_vehiculos quedo desalineado, dejando el FK
 * apuntando a un tipo con distinta marca/modelo. Este comando empareja
 * por marca/modelo (o, en arrastres, por el pivote legacy) y crea las
 * entradas faltantes en tipo_vehiculos.
 */
class RepararTiposVehiculo extends Command
{
    protected $signature = 'zafiro:reparar-tipos-vehiculo {--dry-run : Solo reporta, no escribe}';

    protected $description = 'Repara id_tipo_vehiculo de tractivos/arrastres para que coincida con marca/modelo';

    private bool $dry = false;

    private array $cacheMarca = [];

    private array $cacheModelo = [];

    public function handle(): int
    {
        $this->dry = $this->option('dry-run');
        $this->info($this->dry ? 'MODO DRY-RUN (no se escribe)' : 'MODO APLICAR');

        $this->repararTractores();
        $this->repararArrastres();

        $this->info('Listo.');

        return self::SUCCESS;
    }

    private function idMarca(string $nombre): ?int
    {
        $key = strtolower(trim($nombre));
        if (! array_key_exists($key, $this->cacheMarca)) {
            $row = DB::table('catalogo_items')
                ->where('tipo', 'marcas')
                ->whereRaw('LOWER(nombre) = ?', [$key])
                ->first();
            $this->cacheMarca[$key] = $row?->id;
        }

        return $this->cacheMarca[$key];
    }

    private function idModelo(string $nombre): ?int
    {
        $key = strtolower(trim($nombre));
        if (! array_key_exists($key, $this->cacheModelo)) {
            $row = DB::table('catalogo_items')
                ->where('tipo', 'modelos')
                ->whereRaw('LOWER(nombre) = ?', [$key])
                ->first();
            $this->cacheModelo[$key] = $row?->id;
        }

        return $this->cacheModelo[$key];
    }

    /**
     * Devuelve [id_tipo_vehiculo, creado?].
     */
    private function resolverTipo(int $idMarca, int $idModelo, string $clase, ?int $idTipoEquipo, ?string $fabricacion): array
    {
        $candidatos = DB::table('tipo_vehiculos')
            ->where('clase', $clase)
            ->where('id_marca', $idMarca)
            ->where('id_modelo', $idModelo)
            ->get();

        if ($candidatos->isNotEmpty()) {
            $mejor = $candidatos->firstWhere('id_tipo_equipo', $idTipoEquipo)
                ?? $candidatos->first();

            return [$mejor->id, false];
        }

        if ($this->dry) {
            return [null, true];
        }

        $id = DB::table('tipo_vehiculos')->insertGetId([
            'id_tipo_equipo' => $idTipoEquipo,
            'id_marca' => $idMarca,
            'id_modelo' => $idModelo,
            'id_tipo_mantenimiento' => 1,
            'id_tipo_tractivo' => null,
            'id_tipo_arrastre' => null,
            'clase' => $clase,
            'fabricacion' => $fabricacion,
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$id, true];
    }

    private function repararTractores(): void
    {
        $this->info('--- Tractores ---');
        $idsArrastres = DB::table('arrastres')->whereNull('deleted_at')->pluck('id');
        $tractivos = DB::table('tractivos')
            ->whereNull('deleted_at')
            ->whereNotIn('id', $idsArrastres)
            ->get();

        $ok = 0;
        $creados = 0;
        $sinMarca = 0;
        $saltados = 0;
        $cambiados = 0;

        foreach ($tractivos as $t) {
            if (! $t->marca || ! $t->modelo) {
                $sinMarca++;

                continue;
            }

            $idMarca = $this->idMarca($t->marca);
            $idModelo = $this->idModelo($t->modelo);

            if (! $idMarca || ! $idModelo) {
                $saltados++;
                $this->warn("  tractivo {$t->id}: marca/modelo '{$t->marca}'/'{$t->modelo}' no existe en catalogo_items");

                continue;
            }

            [$nuevoTipo, $creado] = $this->resolverTipo(
                $idMarca,
                $idModelo,
                'tractivo',
                $t->id_tipo_equipo,
                $t->anno ? (string) $t->anno : null
            );

            if ($nuevoTipo === null && $this->dry) {
                $creados++;
                $this->line("  [dry] tractivo {$t->id}: crear tipo {$t->marca}/{$t->modelo}");

                continue;
            }

            if ($nuevoTipo && $nuevoTipo == $t->id_tipo_vehiculo) {
                $ok++;

                continue;
            }

            if ($nuevoTipo) {
                if (! $this->dry) {
                    DB::table('tractivos')->where('id', $t->id)
                        ->update(['id_tipo_vehiculo' => $nuevoTipo, 'updated_at' => now()]);
                }
                if ($creado) {
                    $creados++;
                }
                $cambiados++;
                $ok++;
                $this->line("  tractivo {$t->id}: FK {$t->id_tipo_vehiculo} -> {$nuevoTipo} ({$t->marca}/{$t->modelo})".($this->dry ? ' [dry]' : ''));
            }
        }

        $this->info("  tractores: revisados=".$tractivos->count()." cambiados={$cambiados} creados_tipos={$creados} sin_marca/modelo={$sinMarca} saltados={$saltados}");
    }

    private function repararArrastres(): void
    {
        $this->info('--- Arrastres (por pivote legacy) ---');
        try {
            $legacy = DB::connection('legacy');
        } catch (\Throwable $e) {
            $this->warn('  conexion legacy no disponible; se omite arrastres.');

            return;
        }

        $arrastres = DB::table('arrastres')->whereNull('deleted_at')->get();
        $ok = 0;
        $creados = 0;
        $saltados = 0;
        $cambiados = 0;

        foreach ($arrastres as $a) {
            $leg = $legacy->table('tec_tractivos')->where('idtractivos', $a->id)->first();
            if (! $leg || ! $leg->idtipotractivos) {
                $saltados++;

                continue;
            }
            // Para arrastres el idtipotractivos coincide con el id de tec_tipoarrastres
            // (ahi vive la ficha: marca/modelo), no en tec_tipotractivos.
            $tp = $legacy->table('tec_tipoarrastres')->where('idtipoarrastres', $leg->idtipotractivos)->first();
            if (! $tp || ! $tp->idmarca || ! $tp->idmodelo) {
                $saltados++;

                continue;
            }

            $marca = $legacy->table('tec_marca')->where('idmarca', $tp->idmarca)->value('marca');
            $modelo = $legacy->table('tec_modelo')->where('idmodelo', $tp->idmodelo)->value('modelo');
            if (! $marca || ! $modelo) {
                $saltados++;

                continue;
            }

            $idMarca = $this->idMarca($marca);
            $idModelo = $this->idModelo($modelo);
            if (! $idMarca || ! $idModelo) {
                $saltados++;
                $this->warn("  arrastre {$a->id}: marca/modelo '{$marca}'/'{$modelo}' no existe en catalogo_items");

                continue;
            }

            [$nuevoTipo, $creado] = $this->resolverTipo($idMarca, $idModelo, 'arrastre', null, null);

            if ($nuevoTipo === null && $this->dry) {
                $creados++;
                $this->line("  [dry] arrastre {$a->id}: crear tipo {$marca}/{$modelo}");

                continue;
            }

            if ($nuevoTipo && $nuevoTipo == $a->id_tipo_vehiculo) {
                $ok++;

                continue;
            }

            if ($nuevoTipo) {
                if (! $this->dry) {
                    DB::table('arrastres')->where('id', $a->id)
                        ->update(['id_tipo_vehiculo' => $nuevoTipo, 'updated_at' => now()]);
                }
                if ($creado) {
                    $creados++;
                }
                $cambiados++;
                $ok++;
                $this->line("  arrastre {$a->id}: FK {$a->id_tipo_vehiculo} -> {$nuevoTipo} ({$marca}/{$modelo})".($this->dry ? ' [dry]' : ''));
            }
        }

        $this->info("  arrastres: revisados=".$arrastres->count()." cambiados={$cambiados} creados_tipos={$creados} saltados={$saltados}");
    }
}
