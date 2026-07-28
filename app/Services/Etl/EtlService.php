<?php

namespace App\Services\Etl;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Motor del ETL legacy → nuevo esquema (Fase 3).
 *
 * - Lee la conexión `legacy` por chunks (config/database.php).
 * - Los ids legacy se preservan como ids nuevos: las FKs entre tablas
 *   migradas siguen resolviendo sin tabla de correspondencia.
 * - Repetible: upsert por id (migrate:fresh + ETL = estado completo).
 * - Usuarios: descifra el password legacy (CI_Encrypt) y lo re-hashea
 *   con bcrypt (decisión D3), asigna el rol según mapeo de perfiles.
 */
class EtlService
{
    /** Resultados por tabla: ['tabla' => ['legacy' => n, 'nueva' => m, 'avisos' => [...]]] */
    private array $reporte = [];

    public function __construct(
        private readonly LegacyDecryptor $decryptor,
    ) {}

    public function getReporte(): array
    {
        return $this->reporte;
    }

    /**
     * ETL de usuarios: cod_usuarios → users + roles + password_histories.
     * Handler dedicado (transformación de password y perfil).
     */
    public function migrarUsuarios(int $chunk = 500): void
    {
        $mapeoPerfiles = config('etl.mapeo_perfiles');
        $avisos = [];
        $procesados = 0;

        // Gana el iduser mayor por login duplicado (legacy tiene logins repetidos)
        $usuarios = DB::connection('legacy')->table('cod_usuarios')
            ->orderBy('iduser')->get()
            ->groupBy(fn ($u) => mb_strtoupper(trim($u->login)))
            ->map(function ($grupo) use (&$avisos) {
                if ($grupo->count() > 1) {
                    $avisos[] = "login duplicado '{$grupo->first()->login}': se conserva iduser {$grupo->last()->iduser}";
                }

                return $grupo->last();
            });

        foreach ($usuarios->chunk($chunk) as $lote) {
            foreach ($lote as $legacy) {
                $plain = $this->decryptor->decrypt($legacy->password);

                if ($plain === null || $plain === '') {
                    $avisos[] = "password indescifrable para '{$legacy->login}' (iduser {$legacy->iduser}): se genera temporal";
                    $plain = 'Temporal*'.bin2hex(random_bytes(4));
                }

                $user = User::withTrashed()->find($legacy->iduser);

                if (! $user) {
                    $user = new User;
                    $user->id = $legacy->iduser;
                }

                $user->forceFill([
                    'name' => trim($legacy->login),
                    'username' => mb_strtoupper(trim($legacy->login)),
                    'email' => null,
                    'password' => Hash::make($plain),
                    'id_entidad' => $legacy->idunidad ?: null,
                    'fecha_operaciones' => $legacy->foperaciones,
                    'bloqueado' => (bool) $legacy->bloqueado,
                    'intentos_fallidos' => 0,
                    'fecha_cambio_password' => $legacy->fpass,
                    'password_temporal' => (bool) $legacy->cpass,
                    'deleted_at' => null,
                ]);

                $user->save();

                $rol = $mapeoPerfiles[$legacy->idperfil] ?? null;
                if ($rol) {
                    $user->syncRoles([$rol]);
                } else {
                    $avisos[] = "idperfil {$legacy->idperfil} sin rol mapeado para '{$legacy->login}'";
                }

                $procesados++;
            }
        }

        // Historial de contraseñas: cod_usuariosh → password_histories
        $historial = 0;
        DB::connection('legacy')->table('cod_usuariosh')
            ->orderBy('iduserh')
            ->chunk($chunk, function ($filas) use (&$historial, &$avisos) {
                foreach ($filas as $h) {
                    if (! User::withTrashed()->where('id', $h->iduser)->exists()) {
                        continue;
                    }

                    $plain = $this->decryptor->decrypt($h->password);
                    if ($plain === null || $plain === '') {
                        $avisos[] = "historial indescifrable iduserh {$h->iduserh}: omitido";

                        continue;
                    }

                    DB::table('password_histories')->updateOrInsert(
                        ['id' => $h->iduserh],
                        [
                            'user_id' => $h->iduser,
                            'password' => Hash::make($plain),
                            'fecha_cambio' => $h->fcambio ?? now(),
                        ]
                    );
                    $historial++;
                }
            });

        $this->reporte['users'] = [
            'legacy' => (int) DB::connection('legacy')->table('cod_usuarios')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
        $this->reporte['password_histories'] = [
            'legacy' => (int) DB::connection('legacy')->table('cod_usuariosh')->count(),
            'nueva' => $historial,
            'avisos' => [],
        ];
    }

    /**
     * ETL genérico de una tabla definida en config/etl.php.
     * Preserva el id legacy como id nuevo (upsert repetible).
     */
    public function migrarTabla(string $nombre, int $chunk = 1000): void
    {
        $config = config("etl.tablas.{$nombre}");

        if (! $config) {
            throw new \InvalidArgumentException("Tabla '{$nombre}' sin mapeo en config/etl.php");
        }

        $legacy = $config['legacy'];
        $pk = $config['pk'];
        $avisos = [];
        $procesados = 0;

        $query = DB::connection('legacy')->table($legacy);

        if ($pk) {
            $query->orderBy($pk)->chunk($chunk, function ($filas) use ($nombre, $config, $pk, &$procesados, &$avisos) {
                foreach ($filas as $fila) {
                    $datos = $this->filaADatos($fila, $config, $pk);
                    if (array_key_exists('codigo', $datos) && ($datos['codigo'] === null || $datos['codigo'] === '')) {
                        $avisos[] = "{$nombre}#{$fila->{$pk}}: codigo vacío, omitido";

                        continue;
                    }
                    $datos['id'] = $fila->{$pk};
                    $this->insertarFila($nombre, $datos, $procesados, $avisos);
                }
            });
        } else {
            $filas = $query->get();
            foreach ($filas as $fila) {
                $datos = $this->filaADatos($fila, $config);
                if (array_key_exists('codigo', $datos) && ($datos['codigo'] === null || $datos['codigo'] === '')) {
                    $avisos[] = "{$nombre}#?: codigo vacío, omitido";

                    continue;
                }
                unset($datos['id']);
                $this->insertarFila($nombre, $datos, $procesados, $avisos);
            }
        }

        $this->reporte[$nombre] = [
            'legacy' => (int) DB::connection('legacy')->table($legacy)->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    private function filaADatos(object $fila, array $config, ?string $pk = null): array
    {
        $datos = [];

        foreach ($config['columnas'] ?? [] as $colLegacy => $colNueva) {
            $valor = $fila->{$colLegacy} ?? null;
            $datos[$colNueva] = is_string($valor) ? (trim($valor) ?: null) : $valor;
        }

        foreach ($config['defaults'] ?? [] as $col => $valor) {
            if (! array_key_exists($col, $datos)) {
                $datos[$col] = $valor;
            }
        }

        $datos['created_at'] = now();
        $datos['updated_at'] = now();

        return $datos;
    }

    private function insertarFila(string $tabla, array $datos, int &$procesados, array &$avisos): void
    {
        try {
            if (isset($datos['id'])) {
                DB::table($tabla)->updateOrInsert(['id' => $datos['id']], $datos);
            } else {
                DB::table($tabla)->insert($datos);
            }
            $procesados++;
        } catch (\Throwable $e) {
            $id = $datos['id'] ?? '?';
            $avisos[] = "{$tabla}#{$id}: {$e->getMessage()}";
        }
    }

    /**
     * Siembra la pivote entidad_user tras migrar usuarios y entidades:
     * - cada usuario con su entidad principal (users.id_entidad)
     * - los ADMIN con acceso a todas las entidades activas
     *
     * Repetible: insertOrIgnore respeta la unique (user_id, entidad_id).
     */
    public function sembrarPivoteEntidades(): void
    {
        $ahora = now();

        $porEntidadPrincipal = User::whereNotNull('id_entidad')
            ->get(['id', 'id_entidad'])
            ->map(fn (User $u) => [
                'user_id' => $u->id,
                'entidad_id' => $u->id_entidad,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ])
            ->all();

        $entidadesActivas = DB::table('entidades')->where('activo', true)->pluck('id');
        $admins = User::role('SUPERADMIN')->get(['id']);

        $deAdmins = [];
        foreach ($admins as $admin) {
            foreach ($entidadesActivas as $entidadId) {
                $deAdmins[] = [
                    'user_id' => $admin->id,
                    'entidad_id' => $entidadId,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            }
        }

        $filas = array_merge($porEntidadPrincipal, $deAdmins);

        foreach (array_chunk($filas, 500) as $lote) {
            DB::table('entidad_user')->insertOrIgnore($lote);
        }

        $this->reporte['entidad_user'] = [
            'legacy' => count($filas),
            'nueva' => (int) DB::table('entidad_user')->count(),
            'avisos' => [],
        ];
    }

    /**
     * Conteos old vs new de todas las tablas mapeadas (sin migrar).
     */
    public function validar(): array
    {
        $resultado = [];

        $resultado['users'] = [
            'legacy' => (int) DB::connection('legacy')->table('cod_usuarios')->count(),
            'nueva' => User::withTrashed()->count(),
        ];
        $resultado['password_histories'] = [
            'legacy' => (int) DB::connection('legacy')->table('cod_usuariosh')->count(),
            'nueva' => (int) DB::table('password_histories')->count(),
        ];

        foreach (config('etl.tablas') as $nombre => $config) {
            try {
                $legacy = (int) DB::connection('legacy')->table($config['legacy'])->count();
            } catch (\Throwable) {
                $legacy = -1;
            }

            try {
                $nueva = (int) DB::table($nombre)->count();
            } catch (\Throwable) {
                $nueva = -1;
            }

            $resultado[$nombre] = [
                'legacy' => $legacy,
                'nueva' => $nueva,
            ];
        }

        return $resultado;
    }
}
