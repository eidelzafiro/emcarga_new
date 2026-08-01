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
     * ETL de consecutivos: tec_consecutivos → consecutivos.
     * Requiere mapeo especial porque nombconsecutivo va tanto a
     * codigo como a descripcion (no soportado por columnas genéricas).
     */
    public function migrarConsecutivos(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;
        $vistos = [];

        DB::connection('legacy')->table('tec_consecutivos')
            ->orderBy('idconsecutivos')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, &$vistos) {
                foreach ($filas as $fila) {
                    $codigo = trim($fila->nombconsecutivo);
                    if ($codigo === '') {
                        $avisos[] = "consecutivos#{$fila->idconsecutivos}: codigo vacío, omitido";

                        continue;
                    }

                    $clave = $codigo.'|'.($fila->idunidad ?: '');
                    if (isset($vistos[$clave])) {
                        $avisos[] = "consecutivos#{$fila->idconsecutivos}: duplicado '{$codigo}' (entidad ".($fila->idunidad ?: '-')."), omitido";

                        continue;
                    }
                    $vistos[$clave] = true;

                    DB::table('consecutivos')->updateOrInsert(
                        ['id' => $fila->idconsecutivos],
                        [
                            'codigo' => $codigo,
                            'descripcion' => $codigo,
                            'ultimo' => $fila->valor ?? 0,
                            'formato' => null,
                            'id_entidad' => $fila->idunidad ?: null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                    $procesados++;
                }
            });

        $this->reporte['consecutivos'] = [
            'legacy' => (int) DB::connection('legacy')->table('tec_consecutivos')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de clientes: com_clientes → clientes.
     * Handler dedicado (decisiones del usuario 2026-07-31):
     * - Migra TODOS los campos legacy (nombres originales conservados).
     * - idunidad → id_entidad (entidades.id 1:1).
     * - activo = inverso de cancelado.
     * - codcliente duplicado → sufijo '-{idunidad}' (ej. '11989' → '11989-20').
     * - razon_social, telefono y contacto NO se rellenan (sin fuente legacy).
     * Repetible: upsert por id (idcliente preservado).
     */
    public function migrarClientes(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        // Set de códigos duplicados en legacy para aplicar el sufijo
        $duplicados = DB::connection('legacy')->table('com_clientes')
            ->select('codcliente')
            ->whereRaw("TRIM(COALESCE(codcliente, '')) != ''")
            ->groupBy('codcliente')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('codcliente')
            ->flip();

        DB::connection('legacy')->table('com_clientes')
            ->orderBy('idcliente')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $duplicados) {
                foreach ($filas as $fila) {
                    $codigo = trim((string) ($fila->codcliente ?? ''));
                    $codigo = $codigo === '' ? null : $codigo;

                    if ($codigo !== null && isset($duplicados[$codigo])) {
                        $codigo = $codigo.'-'.$fila->idunidad;
                    }

                    $upsert = function (string $tabla, ?string $codigoFinal) use ($fila) {
                        DB::table($tabla)->updateOrInsert(
                            ['id' => $fila->idcliente],
                            [
                                'codigo' => $codigoFinal,
                                'nombre' => trim((string) ($fila->nombcliente ?? '')) ?: '',
                                'nit' => trim((string) ($fila->nit ?? '')) ?: null,
                                'direccion' => trim((string) ($fila->dircliente ?? '')) ?: null,
                                'email' => trim((string) ($fila->email ?? '')) ?: null,
                                'id_entidad' => $fila->idunidad ?: null,
                                'activo' => ! (bool) $fila->cancelado,
                                'nrocontrato' => $fila->nrocontrato,
                                'falta' => $fila->falta,
                                'fvencimiento' => $fila->fvencimiento,
                                'codreup' => trim((string) ($fila->codreup ?? '')) ?: null,
                                'agenciamn' => trim((string) ($fila->agenciamn ?? '')) ?: null,
                                'ctamn' => trim((string) ($fila->ctamn ?? '')) ?: null,
                                'idorganismos' => $fila->idorganismos ?: null,
                                'idosdes' => $fila->idosdes ?: null,
                                'idmonedas' => $fila->idmonedas ?: null,
                                'idclientesel' => $fila->idclientesel ?: null,
                                'emailfacturacion' => trim((string) ($fila->emailfacturacion ?? '')) ?: null,
                                'notas' => trim((string) ($fila->notas ?? '')) ?: null,
                                'cancelado' => $fila->cancelado,
                                'descuento' => $fila->descuento,
                                'plan' => $fila->plan,
                                'mora' => $fila->mora,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    };

                    try {
                        $upsert('clientes', $codigo);
                        $procesados++;
                    } catch (\Throwable $e) {
                        // Colisión con un código literal que ya traía guion en legacy:
                        // re-sufijar con idcliente (decisión del usuario 2026-07-31)
                        if ($codigo !== null && str_contains($e->getMessage(), 'clientes_codigo_unique')) {
                            try {
                                $upsert('clientes', $codigo.'-'.$fila->idcliente);
                                $procesados++;
                                $avisos[] = "clientes#{$fila->idcliente}: codigo '{$codigo}' ocupado, se usó '{$codigo}-{$fila->idcliente}'";
                            } catch (\Throwable $e2) {
                                $avisos[] = "clientes#{$fila->idcliente}: {$e2->getMessage()}";
                            }
                        } else {
                            $avisos[] = "clientes#{$fila->idcliente}: {$e->getMessage()}";
                        }
                    }
                }
            });

        $this->reporte['clientes'] = [
            'legacy' => (int) DB::connection('legacy')->table('com_clientes')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de distancias: com_distancias → distancias.
     * Regla del usuario (2026-07-31): duplicados (idorigen, iddestino)
     * se resuelven conservando la fila de MENOR kms (empate: menor iddistancia).
     * Repetible: upsert por id (iddistancia del ganador preservado).
     */
    public function migrarDistancias(): void
    {
        $avisos = [];
        $procesados = 0;
        $omitidas = 0;

        // Recarga completa: los datos de distancias son 100% del ETL y la
        // regla de menor kms puede elegir ganadores distintos entre corridas.
        DB::table('distancias')->delete();

        // Decisión del usuario (2026-07-31): SOLO filas con origen Y destino
        // existentes en lugares. El resto es dato muerto en legacy (apuntan
        // a ids de clientes o lugares eliminados; el propio ModDistancias
        // legacy usa INNER JOIN con com_lugares en ambos extremos).
        $lugaresValidos = DB::connection('legacy')->table('com_lugares')->pluck('idlugar')->flip();

        // Orden kms ASC + iddistancia ASC: el primer row por par origen-destino gana
        $filas = DB::connection('legacy')->table('com_distancias')
            ->select(['iddistancia', 'idorigen', 'iddestino', 'kms'])
            ->orderBy('idorigen')->orderBy('iddestino')
            ->orderBy('kms')->orderBy('iddistancia')
            ->get();

        $huerfanas = 0;
        $vistos = [];
        foreach ($filas as $fila) {
            $clave = $fila->idorigen.'|'.$fila->iddestino;
            if (isset($vistos[$clave])) {
                $omitidas++;
                continue;
            }
            $vistos[$clave] = true;

            if (! isset($lugaresValidos[$fila->idorigen]) || ! isset($lugaresValidos[$fila->iddestino])) {
                $huerfanas++;
                continue;
            }

            try {
                DB::table('distancias')->updateOrInsert(
                    ['id' => $fila->iddistancia],
                    [
                        'id_lugar_origen' => $fila->idorigen,
                        'id_lugar_destino' => $fila->iddestino,
                        'distancia_km' => $fila->kms,
                        'activo' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                $procesados++;
            } catch (\Throwable $e) {
                $avisos[] = "distancias#{$fila->iddistancia}: {$e->getMessage()}";
            }
        }

        $this->reporte['distancias'] = [
            'legacy' => (int) DB::connection('legacy')->table('com_distancias')->count(),
            'nueva' => $procesados,
            'avisos' => array_merge(
                ["{$omitidas} filas omitidas por duplicar par origen-destino (se conservó la de menor kms)"],
                ["{$huerfanas} filas omitidas por origen/destino inexistente en lugares (dato muerto en legacy)"],
                $avisos
            ),
        ];
    }

    /**
     * ETL de tractivos: tec_tractivos → tractivos.
     * Reglas del usuario (2026-07-31):
     * - NO se migran los que tienen fecha de baja (fbaja NOT NULL).
     * - codigo/placa duplicados → sufijo '-{idunidad}' (colisión: re-sufijo idtractivos).
     * - estado: 14→activo, 26→taller, 23→trabajando, 27→nuevo, 25→paralizado,
     *   22→propuesta_baja; cualquier otro/inválido → activo.
     * - idunidad → id_entidad.
     * - id_tipo_vehiculo se conserva aunque el tipo no exista aún en la nueva
     *   BD (los 6 tipos "BIEL" se resolverán después).
     * - marca/modelo/anno se derivan del tipo legacy; color/motor/caja por lookup.
     */
    public function migrarTractivos(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;
        $omitidosBaja = 0;
        $tiposHuerfanos = [];

        $legacy = DB::connection('legacy');

        // Lookups en memoria (tablas pequeñas)
        $tipos = $legacy->table('tec_tipotractivos')->get(['idtipotractivos', 'idmarca', 'idmodelo', 'fabricacion'])->keyBy('idtipotractivos');
        $marcas = $legacy->table('tec_marca')->pluck('marca', 'idmarca');
        $modelos = $legacy->table('tec_modelo')->pluck('modelo', 'idmodelo');
        $colores = $legacy->table('tec_colores')->pluck('colores', 'idcolores');
        $motores = $legacy->table('tec_motores')->pluck('nroserie', 'idmotores');
        $cajas = $legacy->table('tec_cajas')->pluck('nroserie', 'idcajas');
        $tiposNuevos = DB::table('tipos_tractivos')->pluck('id')->flip();

        $estados = [
            14 => 'activo',
            26 => 'taller',
            23 => 'trabajando',
            27 => 'nuevo',
            25 => 'paralizado',
            22 => 'propuesta_baja',
        ];

        // Sets de duplicados (solo activos: los de baja no se migran)
        $dupChapas = $legacy->table('tec_tractivos')
            ->whereNull('fbaja')->whereRaw("TRIM(COALESCE(chapa, '')) != ''")
            ->groupBy('chapa')->havingRaw('COUNT(*) > 1')->pluck('chapa')->flip();
        $dupCodigos = $legacy->table('tec_tractivos')
            ->whereNull('fbaja')->whereRaw("TRIM(COALESCE(codtractivo, '')) != ''")
            ->groupBy('codtractivo')->havingRaw('COUNT(*) > 1')->pluck('codtractivo')->flip();

        $legacy->table('tec_tractivos')
            ->orderBy('idtractivos')
            ->chunk($chunk, function ($filas) use (&$procesados, &$omitidosBaja, &$avisos, &$tiposHuerfanos, $tipos, $marcas, $modelos, $colores, $motores, $cajas, $tiposNuevos, $estados, $dupChapas, $dupCodigos) {
                foreach ($filas as $fila) {
                    if ($fila->fbaja !== null) {
                        $omitidosBaja++;
                        continue;
                    }

                    $tipo = $tipos->get($fila->idtipotractivos);
                    if (! isset($tiposNuevos[$fila->idtipotractivos])) {
                        $tiposHuerfanos[$fila->idtipotractivos] = ($tiposHuerfanos[$fila->idtipotractivos] ?? 0) + 1;
                    }
                    $idTipoVehiculo = isset($tiposNuevos[$fila->idtipotractivos]) ? $fila->idtipotractivos : null;

                    $codigo = trim((string) ($fila->codtractivo ?? '')) ?: null;
                    if ($codigo !== null && isset($dupCodigos[$codigo])) {
                        $codigo = $codigo.'-'.$fila->idunidad;
                    }

                    $placa = trim((string) ($fila->chapa ?? '')) ?: null;
                    if ($placa !== null && isset($dupChapas[$placa])) {
                        $placa = $placa.'-'.$fila->idunidad;
                    }

                    $fabricacion = $tipo ? trim((string) $tipo->fabricacion) : '';
                    $anno = preg_match('/^\d{4}$/', $fabricacion) ? (int) $fabricacion : null;

                    $falta = $fila->falta;
                    if (is_string($falta) && str_starts_with($falta, '0000-00-00')) {
                        $falta = null;
                    }

                    $upsert = function (?string $codigoFinal, ?string $placaFinal) use ($fila, $tipo, $marcas, $modelos, $colores, $motores, $cajas, $estados, $anno, $falta, $idTipoVehiculo) {
                        DB::table('tractivos')->updateOrInsert(
                            ['id' => $fila->idtractivos],
                            [
                                'codigo' => $codigoFinal,
                                'descripcion' => trim((string) ($fila->codtractivo ?? '')) ?: '',
                                'placa' => $placaFinal ?? '',
                                'id_tipo_vehiculo' => $idTipoVehiculo,
                                'marca' => $tipo ? ($marcas[$tipo->idmarca] ?? null) : null,
                                'modelo' => $tipo ? ($modelos[$tipo->idmodelo] ?? null) : null,
                                'anno' => $anno,
                                'color' => $colores[$fila->idcolorprimario] ?? null,
                                'numero_motor' => $motores[$fila->idmotores] ?? null,
                                'numero_chasis' => trim((string) ($fila->chassis ?? '')) ?: null,
                                'numero_caja' => $cajas[$fila->idcajas] ?? null,
                                'capacidad_toneladas' => $fila->capacidad,
                                'estado' => $estados[$fila->idtipoestados] ?? 'activo',
                                'fecha_alta' => $falta,
                                'fecha_baja' => null,
                                'kilometraje_actual' => $fila->kmsacum ?? 0,
                                'id_entidad' => $fila->idunidad ?: null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    };

                    try {
                        $upsert($codigo, $placa);
                        $procesados++;
                    } catch (\Throwable $e) {
                        if (str_contains($e->getMessage(), 'tractivos_codigo_unique') || str_contains($e->getMessage(), 'tractivos_placa_unique')) {
                            try {
                                $codigo2 = $codigo !== null ? $codigo.'-'.$fila->idtractivos : null;
                                $placa2 = $placa !== null ? $placa.'-'.$fila->idtractivos : null;
                                $upsert($codigo2, $placa2);
                                $procesados++;
                                $avisos[] = "tractivos#{$fila->idtractivos}: colisión UNIQUE, re-sufijado con id";
                            } catch (\Throwable $e2) {
                                $avisos[] = "tractivos#{$fila->idtractivos}: {$e2->getMessage()}";
                            }
                        } else {
                            $avisos[] = "tractivos#{$fila->idtractivos}: {$e->getMessage()}";
                        }
                    }
                }
            });

        $avisos[] = "{$omitidosBaja} tractivos omitidos por tener fecha de baja";
        foreach ($tiposHuerfanos as $idTipo => $n) {
            $avisos[] = "tipo {$idTipo} no existe en tipos_tractivos nueva ({$n} tractivos conservan el id legacy)";
        }

        $this->reporte['tractivos'] = [
            'legacy' => (int) $legacy->table('tec_tractivos')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de motores: tec_motores → motores.
     * - Resuelve marca/modelo desde catálogos (texto, como tractivos).
     * - id_tractivo NULL (legacy usa 0 = sin tractivo).
     * - Estado traducido a texto nuevo (decidido con usuario 2026-07-31).
     */
    public function migrarMotores(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        $legacy = DB::connection('legacy');

        $marcas = $legacy->table('tec_marca')->pluck('marca', 'idmarca');
        $modelos = $legacy->table('tec_modelo')->pluck('modelo', 'idmodelo');

        $estados = [
            27 => 'nuevo',
            18 => 'reparado',
            16 => 'regular',
            23 => 'trabajando',
            14 => 'disponible',
        ];

        $legacy->table('tec_motores')
            ->orderBy('idmotores')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $marcas, $modelos, $estados) {
                foreach ($filas as $fila) {
                    $numeroSerie = trim((string) ($fila->nroserie ?? '')) ?: null;
                    $marca = $marcas[$fila->idmarca] ?? null;
                    $modelo = $modelos[$fila->idmodelo] ?? null;
                    $estado = $estados[$fila->idtipoestados] ?? 'disponible';

                    if (! $marca) {
                        $avisos[] = "motor#{$fila->idmotores}: marca legacy {$fila->idmarca} inexistente, se deja NULL";
                    }

                    $finstalada = $fila->finstalada;
                    if (is_string($finstalada) && str_starts_with($finstalada, '0000-00-00')) {
                        $finstalada = null;
                    }
                    $fbaja = $fila->fbaja;
                    if (is_string($fbaja) && str_starts_with($fbaja, '0000-00-00')) {
                        $fbaja = null;
                    }

                    $descripcion = trim((string) ($numeroSerie ?? '')) ?: $marca ?: 'Motor';

                    DB::table('motores')->updateOrInsert(
                        ['id' => $fila->idmotores],
                        [
                            'codigo' => null,
                            'descripcion' => $descripcion,
                            'marca' => $marca,
                            'modelo' => $modelo,
                            'numero_serie' => $numeroSerie,
                            'cpl' => trim((string) ($fila->cpl ?? '')) ?: null,
                            'caballaje' => $fila->caballaje ?: null,
                            'cantidad_lubricante' => $fila->cantlub ?: null,
                            'numero_tiempos' => $fila->nrotiempos ?: null,
                            'numero_cilindros' => $fila->nrocilindros ?: null,
                            'kms_acumulados' => $fila->kacumulados ?: null,
                            'capacidad_carter' => $fila->motcapcarter ?: null,
                            'fecha_instalacion' => $finstalada,
                            'fecha_baja' => $fbaja,
                            'id_lubricante' => $fila->idlubricantes ?: null,
                            'id_pais' => $fila->idpaises ?: null,
                            'id_tractivo' => null,
                            'estado' => $estado,
                            'id_entidad' => $fila->idunidad ?: null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $procesados++;
                }
            });

        $this->reporte['motores'] = [
            'legacy' => (int) $legacy->table('tec_motores')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de diferenciales: tec_diferenciales → diferenciales.
     * - id_tractivo NULL (legacy usa 0 = sin tractivo).
     * - Estado traducido a texto nuevo (decisión usuario 2026-07-31).
     * - Incluye columnas de ficha técnica (durabilidad, relacion, ancho...).
     */
    public function migrarDiferenciales(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        $legacy = DB::connection('legacy');

        $marcas = $legacy->table('tec_marca')->pluck('marca', 'idmarca');

        $estados = [
            27 => 'nuevo',
            18 => 'reparado',
            16 => 'regular',
            23 => 'trabajando',
            14 => 'disponible',
        ];

        $legacy->table('tec_diferenciales')
            ->orderBy('iddiferenciales')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $marcas, $estados) {
                foreach ($filas as $fila) {
                    $codigo = trim((string) ($fila->codigo ?? '')) ?: null;
                    $marca = $marcas[$fila->idmarca] ?? null;
                    $estado = $estados[$fila->idtipoestados] ?? 'disponible';

                    if (! $marca) {
                        $avisos[] = "diferencial#{$fila->iddiferenciales}: marca legacy {$fila->idmarca} inexistente, se deja NULL";
                    }

                    $descripcion = trim((string) ($fila->codigo ?? '')) ?: 'Diferencial';

                    DB::table('diferenciales')->updateOrInsert(
                        ['id' => $fila->iddiferenciales],
                        [
                            'codigo' => $codigo,
                            'descripcion' => $descripcion,
                            'marca' => $marca,
                            'modelo' => null,
                            'numero_serie' => null,
                            'id_tractivo' => null,
                            'estado' => $estado,
                            'durabilidad' => $fila->durabilidad ?: null,
                            'relacion' => $fila->relacion ?: null,
                            'ancho' => $fila->ancho ?: null,
                            'cantidad_lubricante' => $fila->cantlub ?: null,
                            'cantidad' => $fila->cantidad ?: null,
                            'kms_acumulados' => $fila->kacumulados ?: null,
                            'capacidad_carter' => $fila->difcapcarter ?: null,
                            'id_entidad' => $fila->idunidad ?: null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $procesados++;
                }
            });

        $this->reporte['diferenciales'] = [
            'legacy' => (int) $legacy->table('tec_diferenciales')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de neumáticos: tec_neumaticos → neumaticos.
     * - id_tractivo preservado (todos los tractivos legacy existen).
     * - Marca/medida desde catálogos (NULL si el id no existe).
     * - Estado traducido a texto nuevo (decidido con usuario 2026-07-31).
     */
    public function migrarNeumaticos(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        $legacy = DB::connection('legacy');

        $marcas = $legacy->table('tec_marca')->pluck('marca', 'idmarca');
        $medidas = $legacy->table('tec_neumaticosmedidas')->pluck('neumaticosmedidas', 'idneumaticosmedidas');

        $estados = [
            27 => 'nuevo',
            20 => 'recauchado',
            16 => 'regular',
            14 => 'activo',
        ];

        $legacy->table('tec_neumaticos')
            ->orderBy('idneumaticos')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $marcas, $medidas, $estados) {
                foreach ($filas as $fila) {
                    $folio = trim((string) ($fila->codigo ?? '')) ?: null;
                    $marca = $marcas[$fila->idmarca] ?? null;
                    $medida = $medidas[$fila->idneumaticosmedidas] ?? null;
                    $estado = $estados[$fila->idtipoestados] ?? 'activo';

                    if (! $marca) {
                        $avisos[] = "neumatico#{$fila->idneumaticos}: marca legacy {$fila->idmarca} inexistente, se deja NULL";
                    }
                    if (! $medida) {
                        $avisos[] = "neumatico#{$fila->idneumaticos}: medida legacy {$fila->idneumaticosmedidas} inexistente, se deja NULL";
                    }

                    $fmontado = $fila->fmontado;
                    if (is_string($fmontado) && str_starts_with($fmontado, '0000-00-00')) {
                        $fmontado = null;
                    }
                    $fretirado = $fila->neumfretirado;
                    if (is_string($fretirado) && str_starts_with($fretirado, '0000-00-00')) {
                        $fretirado = null;
                    }
                    $fplanretirado = $fila->fplanretirado;
                    if (is_string($fplanretirado) && str_starts_with($fplanretirado, '0000-00-00')) {
                        $fplanretirado = null;
                    }
                    $fplanaviso = $fila->fplanaviso;
                    if (is_string($fplanaviso) && str_starts_with($fplanaviso, '0000-00-00')) {
                        $fplanaviso = null;
                    }

                    DB::table('neumaticos')->updateOrInsert(
                        ['id' => $fila->idneumaticos],
                        [
                            'folio' => $folio,
                            'marca' => $marca,
                            'modelo' => null,
                            'medida' => $medida,
                            'id_tractivo' => $fila->idtractivos,
                            'fecha_instalacion' => $fmontado,
                            'fecha_retiro' => $fretirado,
                            'kilometraje' => $fila->kminstalado ?? 0,
                            'estado' => $estado,
                            'precio_mn' => $fila->preclopmn ?: null,
                            'precio_me' => $fila->preclopme ?: null,
                            'id_posicion' => $fila->idposicion ?: null,
                            'fecha_fabricacion' => $fila->anofabricacion ?: null,
                            'balanceada' => $fila->balanceada ?: null,
                            'profinicial' => $fila->profinicial ?: null,
                            'explotacion_anterior' => $fila->explotacionanterior ?: null,
                            'kms_promedio' => $fila->kmspromedio ?: null,
                            'fecha_plan_retiro' => $fplanretirado,
                            'fecha_plan_aviso' => $fplanaviso,
                            'id_entidad' => $fila->idunidad ?: null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $procesados++;
                }
            });

        $this->reporte['neumaticos'] = [
            'legacy' => (int) $legacy->table('tec_neumaticos')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de cajas: tec_cajas → cajas.
     * - id_tractivo NULL (legacy usa 0 = sin tractivo, la columna es nullable).
     * - Marca/modelo desde catálogos, estado traducido a texto nuevo.
     */
    public function migrarCajas(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        $legacy = DB::connection('legacy');

        $marcas = $legacy->table('tec_marca')->pluck('marca', 'idmarca');
        $modelos = $legacy->table('tec_modelo')->pluck('modelo', 'idmodelo');

        $estados = [
            27 => 'nuevo',
            18 => 'reparado',
            16 => 'regular',
            14 => 'disponible',
            15 => 'regular',  // MALO → regular
        ];

        $legacy->table('tec_cajas')
            ->orderBy('idcajas')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $marcas, $modelos, $estados) {
                foreach ($filas as $fila) {
                    $numeroSerie = trim((string) ($fila->nroserie ?? '')) ?: null;
                    $marca = $marcas[$fila->idmarca] ?? null;
                    $modelo = $modelos[$fila->idmodelo] ?? null;
                    $estado = $estados[$fila->idtipoestados] ?? 'disponible';

                    if (! $marca) {
                        $avisos[] = "caja#{$fila->idcajas}: marca legacy {$fila->idmarca} inexistente, se deja NULL";
                    }
                    if (! $modelo) {
                        $avisos[] = "caja#{$fila->idcajas}: modelo legacy {$fila->idmodelo} inexistente, se deja NULL";
                    }

                    $descripcion = $numeroSerie ?: $marca ?: 'Caja';

                    DB::table('cajas')->updateOrInsert(
                        ['id' => $fila->idcajas],
                        [
                            'codigo' => null,
                            'descripcion' => $descripcion,
                            'marca' => $marca,
                            'modelo' => $modelo,
                            'numero_serie' => $numeroSerie,
                            'id_tractivo' => $fila->idtractivos ?: null,
                            'estado' => $estado,
                            'id_entidad' => $fila->idunidad ?: null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $procesados++;
                }
            });

        $this->reporte['cajas'] = [
            'legacy' => (int) $legacy->table('tec_cajas')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de líneas de mantenimiento: tec_tipomttolineas → lineas_mantenimiento.
     * Sin PK legacy: idempotencia por (id_tipo_mantenimiento, kilometraje).
     */
    public function migrarLineasMantenimiento(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        $legacy = DB::connection('legacy');

        $legacy->table('tec_tipomttolineas')
            ->orderBy('idtipomtto')
            ->orderBy('km')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos) {
                foreach ($filas as $fila) {
                    $tipo = $fila->idtipomtto;
                    $kilometraje = $fila->km;

                    $existe = DB::table('tipos_mantenimiento')->where('id', $tipo)->exists();

                    if (! $existe) {
                        $avisos[] = "linea#{$tipo}-{$kilometraje}: tipo de mantenimiento legacy {$tipo} inexistente, omitido";

                        continue;
                    }

                    DB::table('lineas_mantenimiento')->updateOrInsert(
                        [
                            'id_tipo_mantenimiento' => $tipo,
                            'kilometraje' => $kilometraje,
                        ],
                        [
                            'descripcion' => trim((string) ($fila->tipomtto ?? '')) ?: null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $procesados++;
                }
            });

        $this->reporte['lineas_mantenimiento'] = [
            'legacy' => (int) $legacy->table('tec_tipomttolineas')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de historial de tractivos: tec_htractivos → historial_tractivos.
     * Solo registros del año indicado (2026 por decisión de negocio).
     * Las FKs que no existan en el esquema nuevo se dejan NULL.
     */
    public function migrarHistorialTractivos(int $anio = 2026, int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        $legacy = DB::connection('legacy');

        $idsTractivos = DB::table('tractivos')->pluck('id')->all();
        $idsCajas = DB::table('cajas')->pluck('id')->all();
        $idsMotores = DB::table('motores')->pluck('id')->all();
        $idsDiferenciales = DB::table('diferenciales')->pluck('id')->all();
        $idsGrupos = DB::table('grupos')->pluck('id')->all();
        $idsEntidades = DB::table('entidades')->pluck('id')->all();

        $legacy->table('tec_htractivos')
            ->whereYear('fcierre', $anio)
            ->orderBy('idhtractivos')
            ->chunk($chunk, function ($filas) use ($anio, &$procesados, &$avisos, $idsTractivos, $idsCajas, $idsMotores, $idsDiferenciales, $idsGrupos, $idsEntidades) {
                foreach ($filas as $fila) {
                    $idTractivo = in_array($fila->idtractivo, $idsTractivos) ? $fila->idtractivo : null;
                    $idCaja = $fila->idcaja !== null && in_array($fila->idcaja, $idsCajas) ? $fila->idcaja : null;
                    $idMotor = $fila->idmotor !== null && in_array($fila->idmotor, $idsMotores) ? $fila->idmotor : null;
                    $idDiferencial = $fila->iddiferencial !== null && in_array($fila->iddiferencial, $idsDiferenciales) ? $fila->iddiferencial : null;
                    $idGrupo = $fila->idgrupo !== null && in_array($fila->idgrupo, $idsGrupos) ? $fila->idgrupo : null;
                    $idEntidad = $fila->idunidad !== null && in_array($fila->idunidad, $idsEntidades) ? $fila->idunidad : null;

                    if ($idCaja === null && $fila->idcaja !== null) {
                        $avisos[] = "historial#{$fila->idhtractivos}: caja legacy {$fila->idcaja} inexistente, NULL";
                    }
                    if ($idTractivo === null) {
                        $avisos[] = "historial#{$fila->idhtractivos}: tractivo legacy {$fila->idtractivo} inexistente, NULL";
                    }

                    DB::table('historial_tractivos')->updateOrInsert(
                        ['id' => $fila->idhtractivos],
                        [
                            'id_tractivo' => $idTractivo,
                            'id_grupo' => $idGrupo,
                            'id_caja' => $idCaja,
                            'id_motor' => $idMotor,
                            'id_diferencial' => $idDiferencial,
                            'id_entidad' => $idEntidad,
                            'fecha_cierre' => $fila->fcierre,
                            'km_historico' => $fila->kmhistorico ?: null,
                            'km_motor' => $fila->kmmotor ?: null,
                            'km_caja' => $fila->kmcaja ?: null,
                            'km_diferencial' => $fila->kmdiferencial ?: null,
                            'indice' => $fila->indice ?: null,
                            'indice_acumulado' => $fila->indiceac ?: null,
                            'plan_combustible' => $fila->plancomb ?: null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $procesados++;
                }
            });

        $this->reporte['historial_tractivos'] = [
            'legacy' => (int) $legacy->table('tec_htractivos')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de órdenes de taller: tec_ordentaller → ordenes_taller.
     * Solo registros del año indicado (2026). idtipomtto=0 → tipo 'SIN TIPO' (id 0).
     */
    public function migrarOrdenesTaller(int $anio = 2026, int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        $legacy = DB::connection('legacy');

        $legacy->table('tec_ordentaller')
            ->whereYear('fentrada', $anio)
            ->orderBy('idordentaller')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos) {
                foreach ($filas as $fila) {
                    $tipo = $fila->idtipomtto;

                    if (! DB::table('tipos_mantenimiento')->where('id', $tipo)->exists()) {
                        $avisos[] = "orden#{$fila->idordentaller}: tipo de mantenimiento legacy {$tipo} inexistente, omitido";

                        continue;
                    }

                    $fentrada = $fila->fentrada;
                    if (is_string($fentrada) && str_starts_with($fentrada, '0000-00-00')) {
                        $fentrada = null;
                    }
                    $fsalida = $fila->fsalida;
                    if (is_string($fsalida) && str_starts_with($fsalida, '0000-00-00')) {
                        $fsalida = null;
                    }

                    DB::table('ordenes_taller')->updateOrInsert(
                        ['id' => $fila->idordentaller],
                        [
                            'numero' => $fila->ordentaller,
                            'id_tractivo' => $fila->idtractivos,
                            'id_tipo_mantenimiento' => $tipo,
                            'fecha_ingreso' => $fentrada,
                            'fecha_salida_estimada' => $fsalida,
                            'kilometraje' => $fila->kmmtto ?: null,
                            'estado' => $fila->cancelada ? 'cancelada' : 'abierta',
                            'observaciones' => trim((string) ($fila->notas ?? '')) ?: null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $procesados++;
                }
            });

        $this->reporte['ordenes_taller'] = [
            'legacy' => (int) $legacy->table('tec_ordentaller')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de arrastres (remolques/semirremolques).
     *
     * El legacy NO tiene tabla de arrastres: tec_naves está vacía. Los arrastres
     * son tractivos cuyo idtipotractivos pertenece a tec_tipoarrastres (rango
     * 100-197). Decidido con usuario 2026-07-31: poblar arrastres desde esos
     * tractivos (código = codtractivo, marca/tipo equipo desde el tipo) y luego
     * tec_asociaciones → arrastre_tractivo.
     */
    public function migrarArrastres(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        $legacy = DB::connection('legacy');

        $tipos = $legacy->table('tec_tipotractivos')
            ->get(['idtipotractivos', 'idmarca', 'idtipoequipos'])
            ->keyBy('idtipotractivos');
        $marcas = DB::table('marcas')->pluck('id')->flip();
        $tiposEquipos = DB::table('tipos_equipos')->pluck('id')->flip();

        $dupCodigos = $legacy->table('tec_tractivos')
            ->whereIn('idtipotractivos', $legacy->table('tec_tipoarrastres')->pluck('idtipoarrastres'))
            ->whereNull('fbaja')
            ->whereRaw("TRIM(COALESCE(codtractivo, '')) != ''")
            ->groupBy('codtractivo')->havingRaw('COUNT(*) > 1')->pluck('codtractivo')->flip();

        $legacy->table('tec_tractivos')
            ->whereIn('idtipotractivos', $legacy->table('tec_tipoarrastres')->pluck('idtipoarrastres'))
            ->orderBy('idtractivos')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $tipos, $marcas, $tiposEquipos, $dupCodigos) {
                foreach ($filas as $fila) {
                    $tipo = $tipos->get($fila->idtipotractivos);

                    $codigo = trim((string) ($fila->codtractivo ?? '')) ?: null;
                    if ($codigo !== null && isset($dupCodigos[$codigo])) {
                        $codigo = $codigo.'-'.$fila->idtractivos;
                    }

                    $idMarca = null;
                    if ($tipo && isset($marcas[$tipo->idmarca])) {
                        $idMarca = $tipo->idmarca;
                    } elseif ($tipo) {
                        $avisos[] = "arrastre#{$fila->idtractivos}: marca {$tipo->idmarca} no existe en marcas nueva";
                    }

                    $idTipoEquipo = null;
                    if ($tipo && isset($tiposEquipos[$tipo->idtipoequipos])) {
                        $idTipoEquipo = $tipo->idtipoequipos;
                    } elseif ($tipo) {
                        $avisos[] = "arrastre#{$fila->idtractivos}: tipo equipo {$tipo->idtipoequipos} no existe en tipos_equipos nueva";
                    }

                    $upsert = function (?string $codigoFinal) use ($fila, $idMarca, $idTipoEquipo) {
                        DB::table('arrastres')->updateOrInsert(
                            ['id' => $fila->idtractivos],
                            [
                                'codigo' => $codigoFinal,
                                'chapa' => trim((string) ($fila->chapa ?? '')) ?: null,
                                'id_marca' => $idMarca,
                                'id_tipo_equipo' => $idTipoEquipo,
                                'capacidad' => $fila->capacidad,
                                'lot' => trim((string) ($fila->lot ?? '')) ?: null,
                                'circulacion' => trim((string) ($fila->circulacion ?? '')) ?: null,
                                'activo' => $fila->fbaja === null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    };

                    try {
                        $upsert($codigo);
                        $procesados++;
                    } catch (\Throwable $e) {
                        if ($codigo !== null && str_contains($e->getMessage(), 'arrastres_codigo_unique')) {
                            try {
                                $upsert($codigo.'-'.$fila->idtractivos);
                                $procesados++;
                                $avisos[] = "arrastre#{$fila->idtractivos}: código duplicado, re-sufijado con id";
                            } catch (\Throwable $e2) {
                                $avisos[] = "arrastre#{$fila->idtractivos}: {$e2->getMessage()}";
                            }
                        } else {
                            $avisos[] = "arrastre#{$fila->idtractivos}: {$e->getMessage()}";
                        }
                    }
                }
            });

        $avisos[] = (int) $legacy->table('tec_tractivos')
            ->whereIn('idtipotractivos', $legacy->table('tec_tipoarrastres')->pluck('idtipoarrastres'))
            ->whereNotNull('fbaja')->count().' arrastres con fecha de baja quedan activo=false';

        $this->reporte['arrastres'] = [
            'legacy' => (int) $legacy->table('tec_tractivos')
                ->whereIn('idtipotractivos', $legacy->table('tec_tipoarrastres')->pluck('idtipoarrastres'))
                ->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de asociaciones tractivos ↔ arrastres: tec_asociaciones → arrastre_tractivo.
     * - idarrastres=0 (204) → sin arrastre, se omiten.
     * - El idarrastres apunta a tec_tractivos (arrastres son tractivos).
     */
    public function migrarAsociaciones(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;
        $sinArrastre = 0;

        $legacy = DB::connection('legacy');

        $arrastres = DB::table('arrastres')->pluck('id')->flip();
        $tractivos = DB::table('tractivos')->pluck('id')->flip();

        $legacy->table('tec_asociaciones')
            ->orderBy('idasociaciones')
            ->chunk($chunk, function ($filas) use (&$procesados, &$sinArrastre, &$avisos, $arrastres, $tractivos) {
                foreach ($filas as $fila) {
                    if ($fila->idarrastres == 0) {
                        $sinArrastre++;
                        continue;
                    }
                    if (! isset($tractivos[$fila->idtractivos])) {
                        $avisos[] = "asociacion#{$fila->idasociaciones}: tractivo {$fila->idtractivos} no existe en tractivos nueva (baja?)";

                        continue;
                    }
                    if (! isset($arrastres[$fila->idarrastres])) {
                        $avisos[] = "asociacion#{$fila->idasociaciones}: arrastre {$fila->idarrastres} no existe en arrastres nueva";

                        continue;
                    }

                    DB::table('arrastre_tractivo')->updateOrInsert(
                        ['id_tractivo' => $fila->idtractivos, 'id_arrastre' => $fila->idarrastres],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                    $procesados++;
                }
            });

        $avisos[] = "{$sinArrastre} asociaciones omitidas por idarrastres=0 (sin arrastre)";

        $this->reporte['arrastre_tractivo'] = [
            'legacy' => (int) $legacy->table('tec_asociaciones')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de baterías: tec_baterias → baterias.
     * - folio desde codigo (int), re-sufijado con id si duplicado (unique nueva).
     * - marca texto desde catálogo, id_tractivo NULL si 0 (columna nullable).
     * - estado 'activa'/'baja' según fbaja.
     */
    public function migrarBaterias(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        $legacy = DB::connection('legacy');

        $marcas = $legacy->table('tec_marca')->pluck('marca', 'idmarca');

        $dupCodigos = $legacy->table('tec_baterias')
            ->whereRaw('codigo != 0')
            ->groupBy('codigo')->havingRaw('COUNT(*) > 1')->pluck('codigo')->flip();

        $legacy->table('tec_baterias')
            ->orderBy('idbaterias')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $marcas, $dupCodigos) {
                foreach ($filas as $fila) {
                    $folio = trim((string) ($fila->codigo ?? '')) ?: null;
                    if ($folio !== null && isset($dupCodigos[$fila->codigo])) {
                        $folio = $folio.'-'.$fila->idbaterias;
                    }

                    $marca = $marcas[$fila->idmarca] ?? null;
                    if (! $marca) {
                        $avisos[] = "bateria#{$fila->idbaterias}: marca legacy {$fila->idmarca} inexistente, se deja NULL";
                    }

                    $finstalada = $fila->finstalada;
                    if (is_string($finstalada) && str_starts_with($finstalada, '0000-00-00')) {
                        $finstalada = null;
                    }
                    $fbaja = $fila->fbaja;
                    if (is_string($fbaja) && str_starts_with($fbaja, '0000-00-00')) {
                        $fbaja = null;
                    }

                    DB::table('baterias')->updateOrInsert(
                        ['id' => $fila->idbaterias],
                        [
                            'folio' => $folio,
                            'marca' => $marca,
                            'modelo' => null,
                            'id_tractivo' => $fila->idtractivos ?: null,
                            'fecha_instalacion' => $finstalada,
                            'fecha_retiro' => $fbaja,
                            'estado' => $fbaja === null ? 'activa' : 'baja',
                            'id_entidad' => $fila->idunidad ?: null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $procesados++;
                }
            });

        $this->reporte['baterias'] = [
            'legacy' => (int) $legacy->table('tec_baterias')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de movimientos de baterías: tec_bateriasmov → baterias_movimientos.
     * - tipo 'movimiento' (no existe columna legacy).
     * - id_tractivo NULL si 0, iddestagregados → id_destino.
     * - 1 fila con idbaterias=0 se omite (FK obligatoria).
     */
    public function migrarBateriasMovimientos(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;
        $sinBateria = 0;

        $legacy = DB::connection('legacy');

        $legacy->table('tec_bateriasmov')
            ->orderBy('idbateriasmov')
            ->chunk($chunk, function ($filas) use (&$procesados, &$sinBateria, &$avisos) {
                foreach ($filas as $fila) {
                    if ($fila->idbaterias == 0) {
                        $sinBateria++;
                        $avisos[] = "baterias_movimiento#{$fila->idbateriasmov}: idbaterias=0, omitido";
                        continue;
                    }

                    $fmovimiento = $fila->fmovimiento;
                    if (is_string($fmovimiento) && str_starts_with($fmovimiento, '0000-00-00')) {
                        $fmovimiento = null;
                    }
                    $fretirada = $fila->fretirada;
                    if (is_string($fretirada) && str_starts_with($fretirada, '0000-00-00')) {
                        $fretirada = null;
                    }

                    DB::table('baterias_movimientos')->updateOrInsert(
                        ['id' => $fila->idbateriasmov],
                        [
                            'id_bateria' => $fila->idbaterias,
                            'id_tractivo' => $fila->idtractivos ?: null,
                            'fecha_movimiento' => $fmovimiento,
                            'tipo' => 'movimiento',
                            'fecha_retiro' => $fretirada,
                            'tiempo_trabajo' => $fila->tiempotrabajo ?: null,
                            'observaciones' => trim((string) ($fila->observacion ?? '')) ?: null,
                            'id_destino' => $fila->iddestagregados ?: null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $procesados++;
                }
            });

        $avisos[] = "{$sinBateria} movimientos omitidos por idbaterias=0";

        $this->reporte['baterias_movimientos'] = [
            'legacy' => (int) $legacy->table('tec_bateriasmov')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de la bolsa de empleados: rh_bolsa → bolsa.
     * - ci único: se descartan filas con CI repetido (se conserva el idbolsa menor).
     * - nombrecompleto se parte por espacios: nombre = 2 primeras palabras,
     *   apellidos = resto (con 2 palabras se reparte 1/1).
     * - sexo: 1=M, 2=F.
     * - id_cargo derivado del último movimiento (rh_movimientos → rh_plantilla);
     *   si no tiene movimiento se asigna el cargo default 'SIN ASIGNAR'.
     * - id_entidad = idunidad (misma id que entidades nueva).
     * - activo = (baja == 0).
     */
    public function migrarBolsa(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        $legacy = DB::connection('legacy');

        // Cargo default 'SIN ASIGNAR' (id alto fijo para no colisionar con rh_cargos).
        $cargoDefaultId = 1_000_000;
        DB::table('cargos')->updateOrInsert(
            ['id' => $cargoDefaultId],
            [
                'codigo' => 'SIN-ASIGNAR',
                'nombre' => 'SIN ASIGNAR',
                'es_chofer' => false,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // CIs duplicados: conservar el idbolsa menor por CI.
        $keepBolsaPorCi = $legacy->table('rh_bolsa')
            ->selectRaw('cidentidad, MIN(idbolsa) AS keep_id')
            ->groupBy('cidentidad')
            ->pluck('keep_id', 'cidentidad');

        // Último movimiento por persona → idcargos (via rh_plantilla).
        $cargoPorBolsa = $legacy->table('rh_movimientos as m')
            ->join('rh_plantilla as p', 'p.idplantilla', '=', 'm.idplantilla')
            ->whereIn('m.idmovimientos', function ($q) {
                $q->selectRaw('MAX(mm.idmovimientos)')
                    ->from('rh_movimientos as mm')
                    ->groupBy('mm.idbolsa');
            })
            ->select('m.idbolsa', 'p.idcargos')
            ->get()
            ->pluck('idcargos', 'idbolsa');

        $legacy->table('rh_bolsa')
            ->orderBy('idbolsa')
            ->chunk($chunk, function ($filas) use (
                &$procesados, &$avisos, $keepBolsaPorCi, $cargoPorBolsa, $cargoDefaultId
            ) {
                foreach ($filas as $fila) {
                    $ci = trim((string) $fila->cidentidad);

                    if ($ci !== '' && ($keepBolsaPorCi[$ci] ?? null) != $fila->idbolsa) {
                        $avisos[] = "bolsa#{$fila->idbolsa}: CI {$ci} duplicado, omitido";
                        continue;
                    }

                    $palabras = preg_split('/\s+/', trim((string) $fila->nombrecompleto));
                    if (count($palabras) >= 3) {
                        $nombre = implode(' ', array_slice($palabras, 0, 2));
                        $apellidos = implode(' ', array_slice($palabras, 2));
                    } elseif (count($palabras) === 2) {
                        $nombre = $palabras[0];
                        $apellidos = $palabras[1];
                    } else {
                        $nombre = $palabras[0] ?? '';
                        $apellidos = '';
                    }

                    $sexo = match ((int) $fila->idtiposexo) {
                        1 => 'M',
                        2 => 'F',
                        default => null,
                    };

                    $direccion = trim((string) $fila->direccion) ?: null;
                    $telefono = trim((string) $fila->telefono) ?: null;

                    DB::table('bolsa')->updateOrInsert(
                        ['id' => $fila->idbolsa],
                        [
                            'ci' => $ci,
                            'codigo' => null,
                            'nombre' => $nombre,
                            'apellidos' => $apellidos,
                            'sexo' => $sexo,
                            'fecha_nacimiento' => null,
                            'direccion' => $direccion,
                            'telefono' => $telefono,
                            'email' => null,
                            'id_cargo' => $cargoPorBolsa[$fila->idbolsa] ?? $cargoDefaultId,
                            'id_entidad' => $fila->idunidad ?: 1,
                            'activo' => (int) $fila->baja === 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $procesados++;
                }
            });

        $this->reporte['bolsa'] = [
            'legacy' => (int) $legacy->table('rh_bolsa')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL genérico de una tabla definida en config/etl.php.
     * Preserva el id legacy como id nuevo (upsert repetible).
     */
    public function migrarLugares(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        $legacy = DB::connection('legacy');

        // Lookups en memoria (tablas pequeñas): nombre de provincia/municipio
        $provincias = $legacy->table('rh_provincias')->pluck('nombprovincia', 'idprovincias');
        $municipios = $legacy->table('rh_municipios')->pluck('nombmunicipio', 'idmunicipios');

        $legacy->table('com_lugares')
            ->orderBy('idlugar')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $provincias, $municipios) {
                foreach ($filas as $fila) {
                    $datos = [
                        'id' => $fila->idlugar,
                        'nombre' => trim((string) ($fila->nomblugar ?? '') ?: ''),
                        'provincia' => isset($provincias[$fila->idprovincias]) ? $provincias[$fila->idprovincias] : null,
                        'municipio' => isset($municipios[$fila->idmunicipios]) ? $municipios[$fila->idmunicipios] : null,
                        'activo' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $this->insertarFila('lugares', $datos, $procesados, $avisos);
                }
            });

        $this->reporte['lugares'] = [
            'legacy' => (int) $legacy->table('com_lugares')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

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
            $datos[$colNueva] = is_string($valor) ? (trim($valor) !== '' ? trim($valor) : null) : $valor;

            // Fechas legacy '0000-00-00' son inválidas en MySQL estricto → NULL
            if (is_string($datos[$colNueva]) && str_starts_with($datos[$colNueva], '0000-00-00')) {
                $datos[$colNueva] = null;
            }
        }

        foreach ($config['defaults'] ?? [] as $col => $valor) {
            if (! array_key_exists($col, $datos)) {
                $datos[$col] = $valor;
            }
        }

        foreach ($config['cero_a_null'] ?? [] as $col) {
            if (array_key_exists($col, $datos) && (int) $datos[$col] === 0) {
                $datos[$col] = null;
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
            if (isset($datos['id']) && isset($datos['codigo']) && str_contains($e->getMessage(), 'codigo_unique')) {
                $datos['codigo'] = $datos['codigo'].'-'.$datos['id'];
                DB::table($tabla)->updateOrInsert(['id' => $datos['id']], $datos);
                $procesados++;
                $avisos[] = "{$tabla}#{$datos['id']}: código duplicado, re-sufijado con id";

                return;
            }
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

        // Arrastres: legacy NO tiene tabla (tec_naves vacía). Los arrastres son
        // tractivos tipo-arrastre (idtipotractivos ∈ tec_tipoarrastres, 100-197).
        $legacyArrastres = (int) DB::connection('legacy')->table('tec_tractivos')
            ->whereIn('idtipotractivos', DB::connection('legacy')->table('tec_tipoarrastres')->pluck('idtipoarrastres'))
            ->count();
        $resultado['arrastres'] = [
            'legacy' => $legacyArrastres,
            'nueva' => (int) DB::table('arrastres')->count(),
        ];
        $resultado['arrastre_tractivo'] = [
            'legacy' => (int) DB::connection('legacy')->table('tec_asociaciones')
                ->where('idarrastres', '>', 0)->count(),
            'nueva' => (int) DB::table('arrastre_tractivo')->count(),
        ];
        $resultado['baterias'] = [
            'legacy' => (int) DB::connection('legacy')->table('tec_baterias')->count(),
            'nueva' => (int) DB::table('baterias')->count(),
        ];
        $resultado['baterias_movimientos'] = [
            'legacy' => (int) DB::connection('legacy')->table('tec_bateriasmov')->count(),
            'nueva' => (int) DB::table('baterias_movimientos')->count(),
        ];
        $resultado['bolsa'] = [
            'legacy' => (int) DB::connection('legacy')->table('rh_bolsa')->count(),
            'nueva' => (int) DB::table('bolsa')->count(),
        ];

        return $resultado;
    }
}
