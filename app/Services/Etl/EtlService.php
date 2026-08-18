<?php

namespace App\Services\Etl;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

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
                        $avisos[] = "consecutivos#{$fila->idconsecutivos}: duplicado '{$codigo}' (entidad ".($fila->idunidad ?: '-').'), omitido';

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

        // Ids reales de las tablas destino (los FKs deben apuntar a ids existentes;
        // el legacy guarda ids huérfanos tipo idcolorprimario=1 que no existen).
        $idsNuevos = [
            'grupos' => DB::table('grupos')->pluck('id')->flip(),
            'tipos_servicios' => DB::table('tipos_servicios')->pluck('id')->flip(),
            'colores' => DB::table('colores')->pluck('id')->flip(),
            'estados_componentes' => DB::table('estados_componentes')->pluck('id')->flip(),
            'lubricantes' => DB::table('lubricantes')->pluck('id')->flip(),
            'motores' => DB::table('motores')->pluck('id')->flip(),
            'cajas' => DB::table('cajas')->pluck('id')->flip(),
            'diferenciales' => DB::table('diferenciales')->pluck('id')->flip(),
        ];

        // Helpers de normalización
        $nullif0 = fn ($v) => $v === null || (int) $v === 0 ? null : (int) $v;
        $fecha = function ($v): ?string {
            if ($v === null) {
                return null;
            }
            $s = (string) $v;

            return str_starts_with($s, '0000-00-00') ? null : $s;
        };
        // Resuelve FK: null si es 0/vacío o si el id no existe en la tabla destino.
        $fk = function (string $tabla, $v) use ($idsNuevos): ?int {
            if ($v === null || (int) $v === 0) {
                return null;
            }
            $id = (int) $v;

            return isset($idsNuevos[$tabla][$id]) ? $id : null;
        };

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
            ->chunk($chunk, function ($filas) use (&$procesados, &$omitidosBaja, &$avisos, &$tiposHuerfanos, $tipos, $marcas, $modelos, $colores, $motores, $cajas, $tiposNuevos, $estados, $dupChapas, $dupCodigos, $fecha, $fk) {
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

                    $upsert = function (?string $codigoFinal, ?string $placaFinal) use ($fila, $tipo, $marcas, $modelos, $colores, $motores, $cajas, $estados, $anno, $falta, $fecha, $fk, $idTipoVehiculo) {
                        DB::table('tractivos')->updateOrInsert(
                            ['id' => $fila->idtractivos],
                            [
                                'codigo' => $codigoFinal,
                                'descripcion' => trim((string) ($fila->codtractivo ?? '')) ?: '',
                                'placa' => $placaFinal ?? '',
                                'id_tipo_vehiculo' => $idTipoVehiculo,
                                // FKs componentes (id legacy preservado, validado contra destino)
                                'id_motor' => $fk('motores', $fila->idmotores),
                                'id_caja' => $fk('cajas', $fila->idcajas),
                                'id_diferencial' => $fk('diferenciales', $fila->iddiferenciales),
                                // FKs catálogos (validado contra destino)
                                'id_grupo' => $fk('grupos', $fila->idgrupo),
                                'id_tipo_servicio' => $fk('tipos_servicios', $fila->idtiposervicios),
                                'id_color_primario' => $fk('colores', $fila->idcolorprimario),
                                'id_color_secundario' => $fk('colores', $fila->idcolorsecundario),
                                'id_tipo_estado' => $fk('estados_componentes', $fila->idtipoestados),
                                'id_lubricante_hidraulico' => $fk('lubricantes', $fila->idlubricantes),
                                // Descripción / identidad
                                'marca' => $tipo ? ($marcas[$tipo->idmarca] ?? null) : null,
                                'modelo' => $tipo ? ($modelos[$tipo->idmodelo] ?? null) : null,
                                'anno' => $anno,
                                'color' => $colores[$fila->idcolorprimario] ?? null,
                                'vin' => trim((string) ($fila->vin ?? '')) ?: null,
                                'numero_motor' => $motores[$fila->idmotores] ?? null,
                                'numero_chasis' => trim((string) ($fila->chassis ?? '')) ?: null,
                                'numero_caja' => $cajas[$fila->idcajas] ?? null,
                                'capacidad_toneladas' => $fila->capacidad,
                                // Físico / capacidad de combustible
                                // tara > 99.999.999 no cabe en decimal(10,2): valores corruptos legacy → null
                                'tara' => $fila->tara && $fila->tara < 100000000 ? $fila->tara : null,
                                'cap_deposito' => $fila->captanque ?: null,
                                'cap_hidraulico' => $fila->caphidraulico ?: null,
                                'cta_combustible' => trim((string) ($fila->ctacomb ?? '')) ?: null,
                                'indice_consumo' => $fila->indice ?: null,
                                'indice_aceite' => $fila->indiceac ?? null,
                                // Kilometrajes / planes
                                'kms_disp' => $fila->kmsdisp ?: null,
                                'kms_plan_mtto' => $fila->kmsplanmtto ?: null,
                                'kilometraje_actual' => $fila->kmsacum ?? 0,
                                'plan_comb' => $fila->plancomb ?? null,
                                'plan_tn' => $fila->plantn ?? null,
                                'plan_viajes' => $fila->planviajes ?? null,
                                'plan_gastos' => $fila->plangastos ?? null,
                                'plan_cdt' => $fila->plancdt ?? null,
                                'plan_diario' => $fila->plandiario ?: null,
                                // Estado / fechas
                                'estado' => $estados[$fila->idtipoestados] ?? 'activo',
                                'fecha_alta' => $falta,
                                'fecha_baja' => null,
                                // Vencimientos
                                'ficav' => trim((string) ($fila->ficav ?? '')) ?: null,
                                'femision_ficav' => $fecha($fila->femision_ficav),
                                'fvence_ficav' => $fecha($fila->fvence_ficav),
                                'lot' => trim((string) ($fila->lot ?? '')) ?: null,
                                'femision_lot' => $fecha($fila->femision_lot),
                                'fvence_lot' => $fecha($fila->fvence_lot),
                                'circulacion' => trim((string) ($fila->circulacion ?? '')) ?: null,
                                'femision_circ' => $fecha($fila->femision_circ),
                                'fvence_circ' => $fecha($fila->fvence_circ),
                                'f_reconstruccion' => $fecha($fila->fureconstruccion),
                                'gps' => $fila->gps ?: null,
                                'id_entidad' => $fila->idunidad ?: null,
                                // Costos (amortización y chapa por tractivo)
                                'amortmn' => $fila->amortmn ?? 0,
                                'amortme' => $fila->amortme ?? 0,
                                'vchapa' => $fila->vchapa ?? 0,
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

        $codigosUsados = [];

        $legacy->table('tec_motores')
            ->orderBy('idmotores')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $marcas, $modelos, $estados, &$codigosUsados) {
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

                    // Código = nº de serie; sufijo -2/-3 si hay duplicados (columna unique)
                    $codigo = $numeroSerie;
                    if ($codigo) {
                        $base = $codigo;
                        $i = 2;
                        while (isset($codigosUsados[$codigo])) {
                            $codigo = $base.'-'.$i++;
                        }
                        $codigosUsados[$codigo] = true;
                    }

                    $descripcion = $numeroSerie
                        ? ($marca ? "Motor {$marca} núm. {$numeroSerie}" : "Motor núm. {$numeroSerie}")
                        : ($marca ? "Motor {$marca}" : 'Motor');

                    DB::table('motores')->updateOrInsert(
                        ['id' => $fila->idmotores],
                        [
                            'codigo' => $codigo,
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

                    $descripcion = $marca ? "Diferencial {$marca}" : 'Diferencial';

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

        $codigosUsados = [];

        $lubIds = DB::table('lubricantes')->pluck('id')->flip();
        $paisIds = DB::table('paises')->pluck('id')->flip();
        $lubFk = fn ($v) => (int) $v && isset($lubIds[(int) $v]) ? (int) $v : null;
        $paisFk = fn ($v) => (int) $v && isset($paisIds[(int) $v]) ? (int) $v : null;

        $legacy->table('tec_cajas')
            ->orderBy('idcajas')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $marcas, $modelos, $estados, &$codigosUsados, $lubFk, $paisFk) {
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

                    // Código = nº de serie; sufijo -2/-3 si hay duplicados (columna unique)
                    $codigo = $numeroSerie;
                    if ($codigo) {
                        $base = $codigo;
                        $i = 2;
                        while (isset($codigosUsados[$codigo])) {
                            $codigo = $base.'-'.$i++;
                        }
                        $codigosUsados[$codigo] = true;
                    }

                    $descripcion = $numeroSerie
                        ? ($marca ? "Caja {$marca} núm. {$numeroSerie}" : "Caja núm. {$numeroSerie}")
                        : ($marca ? "Caja {$marca}" : 'Caja');

                    DB::table('cajas')->updateOrInsert(
                        ['id' => $fila->idcajas],
                        [
                            'codigo' => $codigo,
                            'descripcion' => $descripcion,
                            'marca' => $marca,
                            'modelo' => $modelo,
                            'numero_serie' => $numeroSerie,
                            'id_tractivo' => $fila->idtractivos ?: null,
                            'estado' => $estado,
                            'durabilidad' => $fila->durabilidad ?: null,
                            'velocidades' => $fila->velocidades ?: null,
                            'cantidad_lubricante' => $fila->cantlub ?: null,
                            'kms_acumulados' => $fila->kacumulados ?: null,
                            'capacidad_carter' => $fila->cajcapcarter ?: null,
                            'id_lubricante' => $lubFk($fila->idlubricantes),
                            'id_pais' => $paisFk($fila->idpaises),
                            'fecha_instalacion' => $fila->finstalada,
                            'fecha_baja' => $fila->fbaja,
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
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $idsTractivos, $idsCajas, $idsMotores, $idsDiferenciales, $idsGrupos, $idsEntidades) {
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
                        $avisos[] = "historial#{$fila->idhtractivos}: tractivo legacy {$fila->idtractivo} inexistente, omitido";

                        continue;
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
     * El legacy NO tiene tabla de arrastres: tec_naves está vacía. La regla de
     * negocio: un tractivo es ARRASTRE si su idgrupo == 8 (grupo ARRASTRES),
     * sino es TRACTOR. La ficha técnica (marca y tipo de equipo) de cada
     * arrastre se lee de tec_tipoarrastres (key idtipotractivos = idtipoarrastres),
     * NO de tec_tipotractivos (esa es la ficha de los tractores). Decidido con
     * usuario 2026-08-06 (corrige clasificación errónea por rango 100-197 y el
     * bug del arrastre 25120 que quedaba como CUÑA TRACTORA en vez de SEMI-REM).
     *
     * Como 2026-08-07 los arrastres viven en la tabla `tractivos` (id_grupo=8);
     * la tabla física `arrastres` se eliminó y las FKs (hojas_ruta,
     * arrastre_tractivo) apuntan a tractivos. Este método re-asocia el tipo de
     * arrastre (id_tipo_vehiculo) y la entidad de cada tractivo grupo 8.
     */
    public function migrarArrastres(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;
        $omitidos = 0;

        $legacy = DB::connection('legacy');

        // Ids reales de tipos de arrastre en BD nueva (heredan id del legacy).
        $tiposA = DB::table('tipos_arrastres')->pluck('id')->flip();

        $legacy->table('tec_tractivos')
            ->where('idgrupo', 8)
            ->orderBy('idtractivos')
            ->chunk($chunk, function ($filas) use (&$procesados, &$omitidos, &$avisos, $tiposA) {
                foreach ($filas as $fila) {
                    // El tractivo referencia idtipotractivos (coincide con id de
                    // tec_tipoarrastres/tipos_arrastres) → id_tipo_vehiculo.
                    $idTipoVehiculo = null;
                    if (isset($tiposA[$fila->idtipotractivos])) {
                        $idTipoVehiculo = $fila->idtipotractivos;
                    } elseif ($fila->idtipotractivos !== null && (int) $fila->idtipotractivos > 0) {
                        $avisos[] = "arrastre#{$fila->idtractivos}: tipo {$fila->idtipotractivos} no existe en tipos_arrastres nueva";
                    }

                    // migrarTractivos excluye los de baja (fbaja != null): no hay
                    // fila en tractivos sobre la que actualizar.
                    $existe = DB::table('tractivos')->where('id', $fila->idtractivos)->exists();
                    if (! $existe) {
                        $omitidos++;

                        continue;
                    }

                    DB::table('tractivos')
                        ->where('id', $fila->idtractivos)
                        ->update([
                            'id_tipo_vehiculo' => $idTipoVehiculo,
                            'id_entidad' => $fila->idunidad ?: null,
                            'updated_at' => now(),
                        ]);

                    $procesados++;
                }
            });

        $avisos[] = (int) $legacy->table('tec_tractivos')
            ->where('idgrupo', 8)
            ->whereNotNull('fbaja')->count().' arrastres con fecha de baja (no migrados como tractivos)';

        $this->reporte['arrastres'] = [
            'legacy' => (int) $legacy->table('tec_tractivos')
                ->where('idgrupo', 8)
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

        $arrastres = DB::table('tractivos')->where('id_grupo', 8)->pluck('id')->flip();
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
                            'fecha_movimiento' => $fila->fmovimiento,
                            'estado' => $fbaja === null ? 'activa' : 'baja',
                            'voltaje' => $fila->batvoltaje ?: null,
                            'amperaje' => $fila->batamperaje ?: null,
                            'precio_mn' => $fila->batpreciomn ?: null,
                            'precio_me' => $fila->batpreciome ?: null,
                            'id_motivo_baja' => (int) $fila->idmotbajabat ?: null,
                            'id_destino' => (int) $fila->iddestagregados ?: null,
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
                            'id_entidad' => $fila->idunidad ?: null,
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

        // Último movimiento por persona → idcargos + idareas (via rh_plantilla).
        $asignacionPorBolsa = $legacy->table('rh_movimientos as m')
            ->join('rh_plantilla as p', 'p.idplantilla', '=', 'm.idplantilla')
            ->whereIn('m.idmovimientos', function ($q) {
                $q->selectRaw('MAX(mm.idmovimientos)')
                    ->from('rh_movimientos as mm')
                    ->groupBy('mm.idbolsa');
            })
            ->select('m.idbolsa', 'p.idcargos', 'p.idareas')
            ->get()
            ->keyBy('idbolsa');

        $cargoPorBolsa = $asignacionPorBolsa->map(fn ($r) => $r->idcargos);

        // Solo asignar áreas que realmente existan (el legacy tiene referencias huérfanas).
        $areasValidas = DB::table('areas')->pluck('id')->all();
        $areaPorBolsa = $asignacionPorBolsa->map(
            fn ($r) => in_array($r->idareas, $areasValidas, true) ? $r->idareas : null
        );

        $legacy->table('rh_bolsa')
            ->orderBy('idbolsa')
            ->chunk($chunk, function ($filas) use (
                &$procesados, &$avisos, $keepBolsaPorCi, $cargoPorBolsa, $areaPorBolsa, $cargoDefaultId
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

                    $categoriasLicencia = trim((string) $fila->licencia) ?: null;
                    $fechaNula = static fn ($f) => $f && $f !== '0000-00-00' ? $f : null;

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
                            'tiene_licencia' => $categoriasLicencia !== null ? 1 : 0,
                            'categorias_licencia' => $categoriasLicencia,
                            'licencia_emision' => $fechaNula($fila->femisionlic),
                            'licencia_vencimiento' => $fechaNula($fila->fvencelic),
                            'chequeo_medico_emision' => $fechaNula($fila->femisioncm),
                            'chequeo_medico_vencimiento' => $fechaNula($fila->fvencecm),
                            'reubicacion_emision' => $fechaNula($fila->femisionrec),
                            'reubicacion_vencimiento' => $fechaNula($fila->fvencerec),
                            'psicometrico_emision' => $fechaNula($fila->femisionpsi),
                            'psicometrico_vencimiento' => $fechaNula($fila->fvencepsi),
                            'id_cargo' => $cargoPorBolsa[$fila->idbolsa] ?? $cargoDefaultId,
                            'id_area' => ($areaPorBolsa[$fila->idbolsa] ?? null) ?: null,
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
     * ETL de plantilla de puestos: rh_plantilla → plantilla.
     * La entidad se deriva del área (rh_areas.idunidad → areas.id_entidad).
     */
    public function migrarPlantilla(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;
        $huerfanas = 0;

        $entidadArea = DB::table('areas')->pluck('id_entidad', 'id');
        $cargosValidos = DB::table('cargos')->pluck('id')->flip();
        $areasValidas = DB::table('areas')->pluck('id')->flip();

        DB::table('plantilla')->delete();

        DB::connection('legacy')->table('rh_plantilla')
            ->orderBy('idplantilla')
            ->chunkById($chunk, function ($filas) use (&$procesados, &$huerfanas, &$avisos, $entidadArea, $cargosValidos, $areasValidas) {
                foreach ($filas as $fila) {
                    if (! isset($areasValidas[$fila->idareas]) || ! isset($cargosValidos[$fila->idcargos])) {
                        $huerfanas++;

                        continue;
                    }

                    try {
                        DB::table('plantilla')->updateOrInsert(
                            ['id' => $fila->idplantilla],
                            [
                                'id_cargo' => $fila->idcargos,
                                'id_area' => $fila->idareas,
                                'aprobada' => (int) $fila->aprobada,
                                'cubierta' => (int) $fila->cubierta,
                                'cubierta2' => (int) $fila->cubierta2,
                                'propuesta' => (int) $fila->propuesta,
                                'v_necesidad' => (int) $fila->vnecesidad,
                                'necesidad' => (int) $fila->necesidad,
                                'id_entidad' => $entidadArea[$fila->idareas] ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        $procesados++;
                    } catch (\Throwable $e) {
                        $avisos[] = "plantilla#{$fila->idplantilla}: {$e->getMessage()}";
                    }
                }
            }, 'idplantilla');

        $this->reporte['plantilla'] = [
            'legacy' => (int) DB::connection('legacy')->table('rh_plantilla')->count(),
            'nueva' => $procesados,
            'avisos' => array_merge(
                ["{$huerfanas} filas omitidas por cargo o área inexistente en la BD nueva (dato huérfano)"],
                $avisos
            ),
        ];
    }

    /**
     * ETL de salarios administrativos: rh_saladmin → salarios_administrativos.
     * La tabla `movimientos` (salario por chofer) no está migrada, por lo que
     * id_movimiento se deja NULL.
     */
    public function migrarSalariosAdministrativos(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        DB::table('salarios_administrativos')->delete();

        DB::connection('legacy')->table('rh_saladmin')
            ->orderBy('idsaladmin')
            ->chunkById($chunk, function ($filas) use (&$procesados, &$avisos) {
                foreach ($filas as $fila) {
                    try {
                        DB::table('salarios_administrativos')->updateOrInsert(
                            ['id' => $fila->idsaladmin],
                            [
                                'fecha' => $fila->fsaladmin,
                                'id_movimiento' => null,
                                'feriados' => (float) $fila->feriados,
                                'irregular' => (float) $fila->irregular,
                                'cpl' => (float) $fila->cpl,
                                'alimentos_extra' => (float) $fila->alimextra,
                                'dias_taller' => (float) $fila->diastaller,
                                'h_extra' => (float) $fila->hextra,
                                'imp_h_extra' => (float) $fila->imphextra,
                                'estado' => 'cerrado',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        $procesados++;
                    } catch (\Throwable $e) {
                        $avisos[] = "salarios_administrativos#{$fila->idsaladmin}: {$e->getMessage()}";
                    }
                }
            }, 'idsaladmin');

        $this->reporte['salarios_administrativos'] = [
            'legacy' => (int) DB::connection('legacy')->table('rh_saladmin')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL genérico de una tabla definida en config/etl.php.
     * Preserva el id legacy como id nuevo (upsert repetible).
     */
    public function migrarLugares(int $chunk = 1000): void
    {        $avisos = [];
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

    /**
     * ETL de hojas de ruta: com_hojaruta → hojas_ruta (solo el año de negocio).
     * Reglas:
     * - id = idhojaruta (preservado).
     * - estado derivado: cancelada==1 → 'cancelada'; si no y hay fecha_cierre → 'cerrada'; si no 'abierta'.
     * - id_entidad derivado del tractivo (hojas_ruta.id_entidad). Si el tractivo no está, la HR se omite.
     * - FKs (chofer/arrastre/parqueo/grupo/entidad/hr_anterior) validadas contra catálogos migrados; no existe → null.
     */
    public function migrarHojasRuta(int $anio = 2026, int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;
        $omitidas = 0;

        $legacy = DB::connection('legacy');

        $idsTractivos = DB::table('tractivos')->pluck('id')->flip();
        $idsArrastres = DB::table('tractivos')->where('id_grupo', 8)->pluck('id')->flip();
        $idsChoferes = DB::table('bolsa')->pluck('id')->flip();
        $idsParqueos = DB::table('lugares')->pluck('id')->flip();
        $idsGrupos = DB::table('grupos')->pluck('id')->flip();
        $idsUsers = DB::table('users')->pluck('id')->flip();
        // Ids ya presentes (re-ejecución) y que se irán acumulando por lote
        $idsHojas = DB::table('hojas_ruta')->pluck('id')->flip();

        // id_entidad de la HR se deriva del tractivo
        $entidadPorTractivo = DB::table('tractivos')
            ->whereNotNull('id_entidad')
            ->pluck('id_entidad', 'id');

        $legacy->table('com_hojaruta')
            ->whereYear('femision', $anio)
            ->orderBy('idhojaruta')
            ->chunk($chunk, function ($filas) use (
                &$procesados, &$omitidas, &$avisos, &$idsHojas,
                $idsTractivos, $idsArrastres, $idsChoferes, $idsParqueos, $idsGrupos,
                $idsUsers, $entidadPorTractivo
            ) {
                foreach ($filas as $fila) {
                    $idTractivo = (int) $fila->idtractivos;
                    if (! isset($idsTractivos[$idTractivo])) {
                        $omitidas++;
                        $avisos[] = "hojas_ruta#{$fila->idhojaruta}: tractivo {$idTractivo} no migrado, id_tractivo NULL";
                        $idTractivo = null;
                        $idEntidad = null;
                    } else {
                        $idEntidad = $entidadPorTractivo[$idTractivo] ?? null;
                    }

                    // id_hr_anterior solo si apunta a una HR ya migrada (en BD).
                    // Los ancestros de otros años (fuera del anio) se dejan null.
                    $idHrAnterior = $fila->idhranterior
                        ? ($idsHojas->has((int) $fila->idhranterior) ? (int) $fila->idhranterior : null)
                        : null;

                    $fechaCierre = $fila->fcierre ?: null;
                    $cancelada = (int) $fila->cancelada === 1;
                    $estado = $cancelada
                        ? 'cancelada'
                        : ($fechaCierre ? 'cerrada' : 'abierta');

                    try {
                        DB::table('hojas_ruta')->updateOrInsert(
                            ['id' => $fila->idhojaruta],
                            [
                                'numero' => $fila->nrohr,
                                'fecha_emision' => $fila->femision,
                                'hora_emision' => $fila->hemision,
                                'id_solicitud' => null,
                                'id_tractivo' => $idTractivo,
                                'id_entidad' => $idEntidad,
                                'id_arrastre' => isset($idsArrastres[$fila->idarrastre]) ? $fila->idarrastre : null,
                                'id_chofer' => isset($idsChoferes[$fila->idchofer]) ? $fila->idchofer : null,
                                'id_chofer2' => isset($idsChoferes[$fila->idchofer2]) ? $fila->idchofer2 : null,
                                'kms_disponible' => $fila->kms_disp,
                                'kms_disponibles_adicionales' => $fila->kms_dispa,
                                'id_hr_anterior' => $idHrAnterior,
                                'id_parqueo' => isset($idsParqueos[$fila->idparqueo]) ? $fila->idparqueo : null,
                                'id_grupo' => isset($idsGrupos[$fila->idgrupo]) ? $fila->idgrupo : null,
                                'id_user' => isset($idsUsers[$fila->iduser]) ? $fila->iduser : null,
                                'fecha_cierre' => $fechaCierre,
                                'hora_cierre' => $fila->hcierre,
                                'kms_totales' => $fila->kms_total,
                                'combustible_habilitado' => $fila->comb_hab,
                                'combustible_consumido' => $fila->comb_cons,
                                'combustible_tecnico' => $fila->comb_tec,
                                'indice_hr' => $fila->indicehr,
                                'tiempo_mov' => $fila->tmov,
                                'tiempo_espera' => $fila->tespera,
                                'tiempo_carga' => $fila->tcarga,
                                'tiempo_taller' => $fila->ttaller,
                                'tiempo_inactivo' => $fila->tinactivo,
                                'tiempo_otras_actividades' => $fila->totrasact,
                                'tiempo_total' => $fila->ttotal,
                                'notas' => $fila->notas,
                                'analisis' => $fila->analisis,
                                'dias_trabajados' => $fila->dtrabajados,
                                'cancelada' => $cancelada,
                                'estado' => $estado,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        $procesados++;
                        $idsHojas->put((int) $fila->idhojaruta, true);
                    } catch (\Throwable $e) {
                        $avisos[] = "hojas_ruta#{$fila->idhojaruta}: {$e->getMessage()}";
                    }
                }
            });

        $this->reporte['hojas_ruta'] = [
            'legacy' => (int) $legacy->table('com_hojaruta')->whereYear('femision', $anio)->count(),
            'nueva' => $procesados,
            'avisos' => array_merge(["{$omitidas} con id_tractivo NULL por tractivo no migrado"], $avisos),
        ];
    }

    /**
     * ETL de cartas de porte (girado): com_girado → cartas_porte.
     * - Solo el año de negocio (2026).
     * - numero = nrocp legacy (varchar numérico 5-6 dígitos) con sufijo -2/-3
     *   cuando nrocp está duplicado (columna UNIQUE en la tabla nueva).
     * - id preservado (FCKs entre tablas migradas resuelven directo).
     * - id_solicitud se vincula en `migrarSolicitudes()` (que corre después) vía
     *   com_solicitudes.idcartaporte = cartas_porte.id.
     * - Fase 4d: la carta NO persiste equipo/choferes/cliente/tipos/productos;
     *   esos valores se derivan de la hoja de ruta y la solicitud.
     * - estado: 'cancelada' (cancelada=1) / 'recepcionada' (frecepcion) / 'emitida'.
     * - toneladas = peso1 + peso2; ingreso_mt se rellena igual (seguimiento).
     */
    public function migrarCartasPorte(int $anio = 2026, int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;
        $canceladas = 0;

        $legacy = DB::connection('legacy');

        $idsHojas = DB::table('hojas_ruta')->pluck('id')->flip();
        $idsUsers = DB::table('users')->pluck('id')->flip();

        // nrocp duplicados en el año → sufijo -2, -3 (unique nueva).
        $dupNrocp = $legacy->table('com_girado')
            ->whereYear('femision', $anio)
            ->groupBy('nrocp')->havingRaw('COUNT(*) > 1')
            ->pluck('nrocp')->flip();

        $usosNrocp = [];

        $fechaValida = function (?string $f): ?string {
            if (! $f || str_starts_with($f, '0000')) {
                return null;
            }

            return substr($f, 0, 10);
        };

        $legacy->table('com_girado')
            ->whereYear('femision', $anio)
            ->orderBy('idcartaporte')
            ->chunk($chunk, function ($filas) use (
                &$procesados, &$canceladas, &$avisos, &$usosNrocp,
                $idsHojas, $idsUsers,
                $dupNrocp, $fechaValida
            ) {
                foreach ($filas as $fila) {
                    $nrocpRaw = trim((string) $fila->nrocp);
                    $nrocp = $nrocpRaw;
                    if (isset($dupNrocp[$nrocpRaw])) {
                        $usosNrocp[$nrocpRaw] = ($usosNrocp[$nrocpRaw] ?? 0) + 1;
                        if ($usosNrocp[$nrocpRaw] > 1) {
                            $nrocp = $nrocpRaw.'-'.$usosNrocp[$nrocpRaw];
                        }
                    }

                    $laFechaEmision = $fechaValida($fila->femision);

                    $idUserRec = (int) $fila->iduserrecepcion;

                    $toneladas = ((float) $fila->peso1 + (float) $fila->peso2) ?: 0;

                    try {
                        DB::table('cartas_porte')->updateOrInsert(
                            ['id' => $fila->idcartaporte],
                            [
                                'numero' => $nrocp,
                                'id_hoja_ruta' => (int) $fila->idhojaruta && isset($idsHojas[$fila->idhojaruta]) ? $fila->idhojaruta : null,
                                'id_solicitud' => null,
                                'fecha_emision' => $laFechaEmision,
                                'fecha_parte' => $laFechaEmision,
                                'fecha_recepcion' => $fechaValida($fila->frecepcion),
                                'toneladas' => $toneladas,
                                'peso1' => $fila->peso1 ?: 0,
                                'peso2' => $fila->peso2 ?: 0,
                                'distancia' => (int) $fila->distancia ?: null,
                                'conduce' => trim((string) $fila->conduce) ?: null,
                                'estado' => (int) $fila->cancelada === 1
                                    ? 'cancelada'
                                    : ($fila->frecepcion ? 'recepcionada' : 'emitida'),
                                'cancelada' => (int) $fila->cancelada === 1,
                                'imprimir' => (int) $fila->imprimir === 1,
                                'notas' => trim((string) $fila->notas) ?: null,
                                'id_user' => (int) $fila->iduser && isset($idsUsers[$fila->iduser]) ? $fila->iduser : null,
                                'id_user_recepcion' => $idUserRec && isset($idsUsers[$idUserRec]) ? $idUserRec : null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        $procesados++;
                        if ((int) $fila->cancelada === 1) {
                            $canceladas++;
                        }
                    } catch (\Throwable $e) {
                        $avisos[] = "cartas_porte#{$fila->idcartaporte} ({$nrocpRaw}): {$e->getMessage()}";
                    }
                }
            });

        $this->reporte['cartas_porte'] = [
            'legacy' => (int) $legacy->table('com_girado')->whereYear('femision', $anio)->count(),
            'nueva' => $procesados,
            'avisos' => array_merge(
                ["{$canceladas} canceladas (estado 'cancelada')", "{$dupNrocp->count()} nrocp duplicados re-sufijados"],
                $avisos
            ),
        ];
    }

    /**
     * ETL de solicitudes de servicio: com_solicitudes → solicitudes_servicio.
     * - Solo el año de negocio (2026).
     * - Paso 1: migra las solicitudes legacy que tienen carta de porte vinculada
     *   (com_solicitudes.idcartaporte = cartas_porte.id, 396 en 2026), preservando
     *   el id legacy y generando numero correlativo SOL-YYYY-NNNNN. Vincula
     *   cartas_porte.id_solicitud.
     * - Paso 2: crea solicitudes agrupadas para las cartas que aún no tienen
     *   solicitud, agrupadas por (cliente, origen, destino, productos, tipos de
     *   carga, moneda, entidad) sumando las toneladas (peso1+peso2). Cada carta
     *   del grupo se vincula a la solicitud resultante.
     */
    public function migrarSolicitudes(int $anio = 2026, int $chunk = 1000): void
    {
        $avisos = [];
        $procesadasLegacy = 0;
        $creadasGrupo = 0;
        $vinculadasGrupo = 0;
        $omitidasLegacy = 0;

        $legacy = DB::connection('legacy');

        $idsCartas = DB::table('cartas_porte')->pluck('id')->flip();
        $idsClientes = DB::table('clientes')->pluck('id')->flip();
        $idsLugares = DB::table('lugares')->pluck('id')->flip();
        $idsProductos = DB::table('productos')->pluck('id')->flip();
        $idsTiposCarga = DB::table('tipos_cargas')->pluck('id')->flip();
        $idsMonedas = DB::table('monedas')->pluck('id')->flip();
        $idsUsers = DB::table('users')->pluck('id')->flip();
        $idsEntidades = DB::table('entidades')->pluck('id')->flip();

        // Entidad de cada carta (vía su hoja de ruta → tractivo) para agrupar solicitudes nuevas.
        $entidadPorCarta = DB::table('cartas_porte as cp')
            ->join('hojas_ruta as h', 'h.id', '=', 'cp.id_hoja_ruta')
            ->join('tractivos as t', 't.id', '=', 'h.id_tractivo')
            ->whereNotNull('t.id_entidad')
            ->pluck('t.id_entidad', 'cp.id');

        // Correlativo SOL-YYYY-NNNNN: continúa tras las solicitudes ya existentes.
        $secuencia = 0;
        $ultimoNumero = DB::table('solicitudes_servicio')
            ->where('numero', 'like', "SOL-{$anio}-%")
            ->orderByDesc('numero')
            ->value('numero');
        if ($ultimoNumero) {
            $secuencia = (int) substr((string) $ultimoNumero, 9);
        }

        $numero = function () use (&$secuencia, $anio): string {
            $secuencia++;

            return 'SOL-'.$anio.'-'.str_pad((string) $secuencia, 5, '0', STR_PAD_LEFT);
        };

        $fechaValida = function (?string $f): ?string {
            if (! $f || str_starts_with($f, '0000')) {
                return null;
            }

            return substr($f, 0, 10);
        };

        // ----- Paso 1: solicitudes legacy con carta vinculada -----
        // Idempotente: el `numero` es estable por id legacy. En re-ejecución solo se
        // actualiza la solicitud (sin regenerar numero) y se re-vincula su carta.
        $idsSolicitudes = DB::table('solicitudes_servicio')->pluck('id')->flip();

        $legacy->table('com_solicitudes')
            ->whereYear('fsolicitud', $anio)
            ->where('idcartaporte', '>', 0)
            ->orderBy('idsolicitud')
            ->chunk($chunk, function ($filas) use (
                &$procesadasLegacy, &$omitidasLegacy, &$avisos, &$secuencia, $numero, $fechaValida,
                $idsCartas, $idsClientes, $idsLugares, $idsProductos, $idsTiposCarga, $idsUsers, $idsEntidades, $idsSolicitudes
            ) {
                foreach ($filas as $fila) {
                    $idCarta = (int) $fila->idcartaporte;

                    if (! isset($idsCartas[$idCarta])) {
                        $omitidasLegacy++;
                        $avisos[] = "solicitudes#{$fila->idsolicitud}: carta {$idCarta} no migrada, omitida";

                        continue;
                    }

                    $idCliente = (int) $fila->idcliente;
                    if (! isset($idsClientes[$idCliente])) {
                        $omitidasLegacy++;
                        $avisos[] = "solicitudes#{$fila->idsolicitud}: cliente {$idCliente} no migrado, omitida";

                        continue;
                    }

                    $idEntidad = (int) $fila->idunidad && isset($idsEntidades[$fila->idunidad]) ? (int) $fila->idunidad : null;

                    $fechaEjecutada = $fechaValida($fila->fejecutado);
                    $estado = $fechaEjecutada ? 'ejecutada' : 'pendiente';

                    $existe = isset($idsSolicitudes[$fila->idsolicitud]);

                    $datos = [
                        'id_entidad' => $idEntidad,
                        'id_cliente' => $idCliente,
                        'id_lugar_origen' => isset($idsLugares[$fila->idorigen]) ? (int) $fila->idorigen : null,
                        'id_lugar_destino' => isset($idsLugares[$fila->iddestino]) ? (int) $fila->iddestino : null,
                        'id_producto' => (int) $fila->idproducto1 && isset($idsProductos[$fila->idproducto1]) ? (int) $fila->idproducto1 : null,
                        'id_producto2' => (int) $fila->idproducto2 && isset($idsProductos[$fila->idproducto2]) ? (int) $fila->idproducto2 : null,
                        'id_tipo_carga' => (int) $fila->idtipocarga1 && isset($idsTiposCarga[$fila->idtipocarga1]) ? (int) $fila->idtipocarga1 : null,
                        'id_tipo_carga2' => (int) $fila->idtipocarga2 && isset($idsTiposCarga[$fila->idtipocarga2]) ? (int) $fila->idtipocarga2 : null,
                        'id_moneda' => null,
                        'id_user' => (int) $fila->iduser && isset($idsUsers[$fila->iduser]) ? (int) $fila->iduser : null,
                        'fecha_solicitud' => $fechaValida($fila->fsolicitud) ?? $anio.'-01-01',
                        'fecha_planificada' => $fechaValida($fila->fplanificado),
                        'fecha_ejecutada' => $fechaEjecutada,
                        'valor_mt' => $fila->valor_mt ?: null,
                        'valor_total' => null,
                        'peso1' => $fila->peso1 ?: 0,
                        'peso2' => $fila->peso2 ?: 0,
                        'distancia' => (int) $fila->distancia ?: null,
                        'notas' => trim((string) $fila->notas) ?: null,
                        'estado' => $estado,
                        'updated_at' => now(),
                    ];

                    try {
                        if (! $existe) {
                            // Preserva el id legacy (las cartas se vinculan por idsolicitud).
                            $datos['id'] = $fila->idsolicitud;
                            $datos['numero'] = $numero();
                            $datos['created_at'] = now();
                            DB::table('solicitudes_servicio')->insert($datos);
                        } else {
                            DB::table('solicitudes_servicio')->where('id', $fila->idsolicitud)->update($datos);
                        }

                        DB::table('cartas_porte')->where('id', $idCarta)->update(['id_solicitud' => $fila->idsolicitud]);
                        $procesadasLegacy++;
                    } catch (\Throwable $e) {
                        $avisos[] = "solicitudes#{$fila->idsolicitud}: {$e->getMessage()}";
                    }
                }
            });

        // ----- Paso 2: solicitudes agrupadas para cartas sin solicitud -----
        $cartasSinSolicitud = DB::table('cartas_porte')
            ->whereNull('id_solicitud')
            ->orderBy('fecha_emision')
            ->orderBy('id')
            ->get();

        $grupos = $cartasSinSolicitud->groupBy(fn ($c) => implode('|', [
            $c->id_cliente,
            $c->id_lugar_origen,
            $c->id_lugar_destino,
            $c->id_producto,
            $c->id_producto2,
            $c->id_tipo_carga,
            $c->id_tipo_carga2,
            $c->id_moneda,
            $entidadPorCarta[$c->id] ?? '',
        ]));

        foreach ($grupos as $cartasGrupo) {
            $primera = $cartasGrupo->first();

            try {
                $idSolicitud = DB::table('solicitudes_servicio')->insertGetId([
                    'numero' => $numero(),
                    'id_entidad' => $entidadPorCarta[$primera->id] ?? null,
                    'id_cliente' => $primera->id_cliente,
                    'id_lugar_origen' => $primera->id_lugar_origen,
                    'id_lugar_destino' => $primera->id_lugar_destino,
                    'id_producto' => $primera->id_producto,
                    'id_producto2' => $primera->id_producto2,
                    'id_tipo_carga' => $primera->id_tipo_carga,
                    'id_tipo_carga2' => $primera->id_tipo_carga2,
                    'id_moneda' => $primera->id_moneda,
                    'id_user' => null,
                    'fecha_solicitud' => $primera->fecha_emision ?? $anio.'-01-01',
                    'fecha_planificada' => null,
                    'fecha_ejecutada' => null,
                    'valor_mt' => null,
                    'valor_total' => null,
                    'peso1' => round($cartasGrupo->sum('peso1'), 2),
                    'peso2' => round($cartasGrupo->sum('peso2'), 2),
                    'distancia' => $primera->distancia,
                    'notas' => null,
                    'estado' => 'pendiente',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($cartasGrupo as $carta) {
                    DB::table('cartas_porte')->where('id', $carta->id)->update(['id_solicitud' => $idSolicitud]);
                    $vinculadasGrupo++;
                }
                $creadasGrupo++;
            } catch (\Throwable $e) {
                $avisos[] = "solicitud agrupada (cliente {$primera->id_cliente}): {$e->getMessage()}";
            }
        }

        $this->reporte['solicitudes'] = [
            'legacy' => (int) $legacy->table('com_solicitudes')->whereYear('fsolicitud', $anio)->count(),
            'nueva' => $procesadasLegacy,
            'avisos' => array_merge(
                ["{$omitidasLegacy} legacy omitidas (carta/cliente no migrado)"],
                ["{$creadasGrupo} solicitudes agrupadas creadas ({$vinculadasGrupo} cartas vinculadas)"],
                $avisos
            ),
        ];

        // Recalcula el estado de TODAS las solicitudes según sus cartas vigentes
        // (ejecutada si alguna está recepcionada o tiene aforo; en_proceso si hay
        // cartas no canceladas sin recepción/aforo; pendiente si no hay cartas).
        $this->recalcularEstadosSolicitudes();
    }

    /**
     * Recalcula el estado de todas las solicitudes a partir de sus cartas de
     * porte vigentes, replicando `SolicitudesServicio::recalcularEstado()`.
     * Idempotente; actualiza también la fecha de ejecución (primera carta).
     */
    private function recalcularEstadosSolicitudes(): void
    {
        $conEjecucion = DB::table('solicitudes_servicio as s')
            ->join('cartas_porte as c', 'c.id_solicitud', '=', 's.id')
            ->where('c.estado', '!=', 'cancelada')
            ->where(function ($q) {
                $q->where('c.estado', 'recepcionada')
                    ->orWhereExists(function ($a) {
                        $a->selectRaw('1')->from('aforos as af')->whereColumn('af.id_carta_porte', 'c.id');
                    });
            })
            ->select('s.id', DB::raw('MIN(c.fecha_emision) as fe'))
            ->groupBy('s.id')
            ->get();

        foreach ($conEjecucion as $fila) {
            DB::table('solicitudes_servicio')->where('id', $fila->id)->update([
                'estado' => 'ejecutada',
                'fecha_ejecutada' => $fila->fe ?: now()->toDateString(),
            ]);
        }

        $idsEjecutadas = $conEjecucion->pluck('id');

        $conCartas = DB::table('solicitudes_servicio as s')
            ->join('cartas_porte as c', 'c.id_solicitud', '=', 's.id')
            ->where('c.estado', '!=', 'cancelada')
            ->when($idsEjecutadas->isNotEmpty(), fn ($q) => $q->whereNotIn('s.id', $idsEjecutadas))
            ->distinct()
            ->pluck('s.id');

        DB::table('solicitudes_servicio')
            ->when($idsEjecutadas->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $idsEjecutadas))
            ->update(['estado' => 'pendiente', 'fecha_ejecutada' => null]);

        if ($conCartas->isNotEmpty()) {
            DB::table('solicitudes_servicio')
                ->whereIn('id', $conCartas)
                ->update(['estado' => 'en_proceso']);
        }
    }

    /**
     * Facturas: solo el año de negocio (2026). El folio legacy (com_rfactura.factura)
     * NO es único (secuencia por unidad, duplicados: 2132 número+año). Se re-numera
     * correlativamente como {$anio}00001+ ordenado por fecha/id (idempotente) y el
     * folio original queda en `numero_legacy`. Totales importados tal cual.
     */
    public function migrarFacturas(int $anio = 2026, int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;
        $secuencia = 0;

        $idsClientes = DB::table('clientes')->pluck('id')->flip();
        $idsUsers = DB::table('users')->pluck('id')->flip();
        $idsEntidades = DB::table('entidades')->pluck('id')->flip();
        $idsTipoIngreso = DB::table('tipo_ingresos')->pluck('id')->flip();

        $fechaValida = function (?string $f): ?string {
            if (! $f || str_starts_with($f, '0000')) {
                return null;
            }

            return substr($f, 0, 10);
        };

        DB::connection('legacy')->table('com_rfactura')
            ->whereYear('ffactura', $anio)
            ->orderBy('ffactura')
            ->orderBy('idfactura')
            ->chunk($chunk, function ($filas) use (
                &$procesados, &$secuencia, &$avisos, $anio,
                $idsClientes, $idsUsers, $idsEntidades, $idsTipoIngreso, $fechaValida
            ) {
                foreach ($filas as $fila) {
                    $secuencia++;
                    $numero = (int) ($anio * 100000 + $secuencia);
                    $idCliente = (int) $fila->idcliente;

                    if (! isset($idsClientes[$idCliente])) {
                        $avisos[] = "facturas#{$fila->idfactura}: cliente {$idCliente} no migrado, omitida";

                        continue;
                    }

                    $idTipoIngreso = (int) $fila->idtipoingresos;
                    $idEntidad = (int) $fila->idunidad;

                    try {
                        DB::table('facturas')->updateOrInsert(
                            ['id' => $fila->idfactura],
                            [
                                'numero' => $numero,
                                'numero_legacy' => (string) $fila->factura,
                                'fecha_emision' => $fechaValida($fila->ffactura) ?? '1970-01-01',
                                'id_cliente' => $idCliente,
                                'id_entidad' => $idEntidad && isset($idsEntidades[$idEntidad]) ? $idEntidad : null,
                                'id_user' => (int) $fila->iduser && isset($idsUsers[$fila->iduser]) ? $fila->iduser : null,
                                'flete_mt' => $fila->fletemtt,
                                'flete_mlc' => $fila->fletemlc,
                                'flete_demora' => $fila->fletedemt,
                                'otros_mt' => $fila->otrosmtt,
                                'ingreso_mt' => $fila->ingresomt,
                                'cancelada' => (int) $fila->cancelada === 1,
                                'refacturada' => (int) $fila->refacturada === 1,
                                'oventas' => (int) $fila->oventas === 1,
                                'id_tipo_ingreso' => $idTipoIngreso && isset($idsTipoIngreso[$idTipoIngreso]) ? $idTipoIngreso : null,
                                'notas' => trim((string) $fila->notas) ?: null,
                                'fecha_firma' => $fechaValida($fila->ffirma),
                                'fecha_cobro_mn' => $fechaValida($fila->fcobromn),
                                'fecha_conciliacion' => $fechaValida($fila->fconciliada),
                                'doc_pago_mn' => trim((string) $fila->docpagomn) ?: null,
                                'estado' => (int) $fila->cancelada === 1
                                    ? 'cancelada'
                                    : ((int) $fila->refacturada === 1 ? 'refacturada' : 'emitida'),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        $procesados++;
                    } catch (\Throwable $e) {
                        $avisos[] = "facturas#{$fila->idfactura}: {$e->getMessage()}";
                    }
                }
            });

        $this->reporte['facturas'] = [
            'legacy' => (int) DB::connection('legacy')->table('com_rfactura')->whereYear('ffactura', $anio)->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * Aforos: solo el año de negocio (2026). Un aforo por carta (PK idcartaporte),
     * vinculado a facturas ya migradas vía idfactura (las facturas de cliente no
     * migrado quedan con id_factura NULL = pendientes de facturar).
     */
    public function migrarAforos(int $anio = 2026, int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        $legacy = DB::connection('legacy');
        $idsFacturas = DB::table('facturas')->pluck('id')->flip();

        $fechaValida = function (?string $f): ?string {
            if (! $f || str_starts_with($f, '0000')) {
                return null;
            }

            return substr($f, 0, 10);
        };

        DB::connection('legacy')->table('com_aforo')
            ->leftJoin('com_girado', 'com_girado.idcartaporte', '=', 'com_aforo.idcartaporte')
            ->whereYear('com_aforo.fparte', $anio)
            ->orderBy('com_aforo.idcartaporte')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $idsFacturas, $fechaValida, $legacy) {
                foreach ($filas as $fila) {
                    $idFactura = (int) $fila->idfactura;

                    try {
                        DB::table('aforos')->updateOrInsert(
                            ['id' => $fila->idcartaporte],
                            [
                                'id_carta_porte' => $fila->idcartaporte,
                                'id_factura' => $idFactura && isset($idsFacturas[$idFactura]) ? $idFactura : null,
                                'id_prefactura' => null,
                                'fecha_parte' => $fechaValida($fila->fparte) ?? '1970-01-01',
                                'flete_mt' => $fila->fletemtt,
                                'flete_mlc' => $fila->fletemlc,
                                'flete_demora' => $fila->fletedemt,
                                'otros_mt' => $fila->otrosmtt,
                                'ingreso_mt' => $fila->ingresomt,
                                'descuento' => (float) $fila->descuento,
                                'refactura' => false,
                                'id_user' => null,

                                // Almacenaje
                                'almacenaje_peso' => $fila->almpeso, 'almacenaje_horas' => $fila->almhoras,
                                'almacenaje_tarifa' => $fila->almtar, 'almacenaje_flete' => $fila->almflete,

                                // Demora
                                'tar_dem_1' => $fila->tardem1, 'tar_dem_2' => $fila->tardem2,
                                'flete_dem_1' => $fila->fletedem1, 'flete_dem_2' => $fila->fletedem2,
                                'dem_carga' => $fila->demcarga, 'dem_descarga' => $fila->demdescarga, 'dem_total' => $fila->demtotal,
                                'fecha_carga' => $fechaValida($fila->fcarga), 'hora_carga_1' => $fila->hcarga1, 'hora_carga_2' => $fila->hcarga2,
                                'fecha_descarga' => $fechaValida($fila->fdescarga), 'hora_descarga_1' => $fila->hdescarga1, 'hora_descarga_2' => $fila->hdescarga2,

                                // Tiempos
                                'tiempo_otros' => $fila->tperm, 'tiempo_movimiento' => $fila->tmov,
                                'tiempo_carga' => $fila->tcarga, 'tiempo_descarga' => $fila->tdescarga,
                                'tiempo_total' => $fila->ttotal, 'tiempo_feriado' => $fila->tferiado,

                                // Recargos
                                'recargo_1' => $fila->recargo1, 'recargo_2' => $fila->recargo2,
                                'recargo_3' => $fila->recargo3, 'recargo_4' => $fila->recargo4, 'recargo_5' => $fila->recargo5,

                                // Salario / coeficiente
                                'id_tasa' => $fila->idtasa ?: null, 'tasa' => $fila->tasa, 'salario' => $fila->salario,

                                // Indicadores: viajes/tipo + totales (las filas viven en aforo_indicadores, D1)
                                'viajes' => $fila->viajes ?: 1, 'tipo_indicadores' => $fila->tipindicadores ?: 1,
                                'tn_pos_total' => $fila->tnpos, 'tn_real_total' => $fila->tnreal,
                                'km_carga_total' => $fila->kmcarga, 'km_vacio_total' => $fila->kmvacio,
                                'km_total_total' => $fila->kmstot, 'traf_pos_total' => $fila->trafpos, 'traf_real_total' => $fila->trafreal,

                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );

                        // Líneas de tarifa (1-5) → aforo_lineas (D1)
                        foreach ([1 => 'fletemt', 2 => 'fletemt2', 3 => 'fletemt3', 4 => 'fletemt4', 5 => 'fletemt5'] as $pos => $campoFlete) {
                            if ((float) $fila->$campoFlete != 0) {
                                DB::table('aforo_lineas')->updateOrInsert(
                                    ['id_aforo' => $fila->idcartaporte, 'posicion' => $pos],
                                    [
                                        'id_tipo_carga' => $fila->{"idtipocarga{$pos}"} ?: null,
                                        'distancia' => $pos === 1 ? $fila->distancia : ($fila->{"distancia{$pos}"} ?: null),
                                        'peso_cobrar' => $fila->{"pesocobrar{$pos}"} ?: null,
                                        'descuento' => $fila->{"desc{$pos}"} ?: null,
                                        'tarifa_mt' => $fila->{"tarmn{$pos}"} ?: null,
                                        'flete_mt' => $fila->$campoFlete ?: null,
                                        'flete_mlc' => $fila->{"fletemlc{$pos}"} ?: null,
                                        'updated_at' => now(),
                                    ]
                                );
                            }
                        }

                        // Filas de indicadores (1-7) → aforo_indicadores (D1).
                        // Filas 1-2 desde com_aforo (tnpos1..), filas 3-7 desde com_indicadores.
                        $indFilas = [
                            1 => ['tn_pos' => $fila->tnpos1, 'tn_real' => $fila->tnreal1, 'km_carga' => $fila->kmcarga1, 'km_vacio' => $fila->kmvacio1, 'km_total' => $fila->kmstot1, 'traf_pos' => $fila->trafpos1, 'traf_real' => $fila->trafreal1],
                            2 => ['tn_pos' => $fila->tnpos2, 'tn_real' => $fila->tnreal2, 'km_carga' => $fila->kmcarga2, 'km_vacio' => $fila->kmvacio2, 'km_total' => $fila->kmstot2, 'traf_pos' => $fila->trafpos2, 'traf_real' => $fila->trafreal2],
                        ];

                        $indLegacy = $legacy->table('com_indicadores')->where('idcartaporte', $fila->idcartaporte)->first();
                        for ($pos = 3; $pos <= 7; $pos++) {
                            if ($indLegacy) {
                                $indFilas[$pos] = [
                                    'tn_pos' => $indLegacy->{"tnpos{$pos}"} ?? null, 'tn_real' => $indLegacy->{"tnreal{$pos}"} ?? null,
                                    'km_carga' => $indLegacy->{"kmcarga{$pos}"} ?? null, 'km_vacio' => $indLegacy->{"kmvacio{$pos}"} ?? null,
                                    'km_total' => $indLegacy->{"kmstot{$pos}"} ?? null, 'traf_pos' => $indLegacy->{"trafpos{$pos}"} ?? null, 'traf_real' => $indLegacy->{"trafreal{$pos}"} ?? null,
                                ];
                            }
                        }

                        foreach ($indFilas as $pos => $f) {
                            $tieneValor = collect($f)->contains(fn ($v) => $v !== null && $v != 0);
                            if (! $tieneValor) {
                                continue;
                            }

                            DB::table('aforo_indicadores')->updateOrInsert(
                                ['id_aforo' => $fila->idcartaporte, 'posicion' => $pos],
                                [
                                    'tn_pos' => $f['tn_pos'], 'tn_real' => $f['tn_real'],
                                    'km_carga' => $f['km_carga'], 'km_vacio' => $f['km_vacio'],
                                    'km_total' => $f['km_total'], 'traf_pos' => $f['traf_pos'], 'traf_real' => $f['traf_real'],
                                    'updated_at' => now(),
                                ]
                            );
                        }

                        $procesados++;
                    } catch (\Throwable $e) {
                        $avisos[] = "aforos#{$fila->idcartaporte}: {$e->getMessage()}";
                    }
                }
            });

        $this->reporte['aforos'] = [
            'legacy' => (int) DB::connection('legacy')->table('com_aforo')->whereYear('fparte', $anio)->count(),
            'nueva' => $procesados,
            'avisos' => array_merge($avisos, ["{$idsFacturas->count()} facturas migradas disponibles para vincular"]),
        ];
    }

    /**
     * ETL de tasas de salario por rango: rh_tipotasas → tasas.
     * Cada fila define el coeficiente (tasa/tasa2) para un tipo de carga dentro de
     * un rango de distancia y de capacidad. Idempotente: upsert por id de legacy.
     */
    public function migrarTasas(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        DB::connection('legacy')->table('rh_tipotasas')
            ->orderBy('idtasa')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos) {
                foreach ($filas as $fila) {
                    try {
                        DB::table('tasas')->updateOrInsert(
                            ['id' => $fila->idtasa],
                            [
                                'nombre' => $fila->nombtasa,
                                'tasa' => $fila->tasa,
                                'tasa2' => $fila->tasa2,
                                'id_tipo_carga' => $fila->idtipocargas ?: null,
                                'distancia_1' => $fila->dist1,
                                'distancia_2' => $fila->dist2,
                                'capacidad_1' => $fila->cap1,
                                'capacidad_2' => $fila->cap2,
                                'id_entidad' => $fila->idunidad ?: null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        $procesados++;
                    } catch (\Throwable $e) {
                        $avisos[] = "tasas#{$fila->idtasa}: {$e->getMessage()}";
                    }
                }
            });

        $this->reporte['tasas'] = [
            'legacy' => (int) DB::connection('legacy')->table('rh_tipotasas')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de tarifas: com_tarifas46 + com_tarifas → tarifas.
     *
     * - `com_tarifas46` (tarifario corriente, 8 tipos base) → version='46'.
     * - `com_tarifas` (tipos especiales 117/118 + base) → version='normal'.
     *   (El legacy `modAforo::mostrar_tarifa` usa com_tarifas46 por defecto y
     *   com_tarifas SOLO para 117/118.)
     * - Idempotente: upsert por (id_tipo_carga, kms, version).
     */
    public function migrarTarifas(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        $this->migrarTarifasDesde('com_tarifas46', '46', $chunk, $procesados, $avisos);
        $this->migrarTarifasDesde('com_tarifas', 'normal', $chunk, $procesados, $avisos);

        $this->reporte['tarifas'] = [
            'legacy' => (int) DB::connection('legacy')->table('com_tarifas46')->count()
                + (int) DB::connection('legacy')->table('com_tarifas')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    private function migrarTarifasDesde(string $legacyTabla, string $version, int $chunk, int &$procesados, array &$avisos): void
    {
        DB::connection('legacy')->table($legacyTabla)
            ->orderBy('idtarifas')
            ->chunk($chunk, function ($filas) use ($version, &$procesados, &$avisos) {
                foreach ($filas as $fila) {
                    try {
                        DB::table('tarifas')->updateOrInsert(
                            [
                                'id_tipo_carga' => $fila->idtipocargas,
                                'kms' => $fila->kms,
                                'version' => $version,
                            ],
                            [
                                'tarifa_mt' => $fila->tarmt,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        $procesados++;
                    } catch (\Throwable $e) {
                        $avisos[] = "tarifas#{$legacyTabla}#{$fila->idtarifas}: {$e->getMessage()}";
                    }
                }
            });
    }

    /**
     * ETL de configuraciones de tarifa: com_tarconfigcarga + com_tarconfigcont →
     * una única fila en `configuraciones_tarifa` (diseño unificado de Zafiro).
     */
    public function migrarConfiguracionesTarifa(): void
    {
        $avisos = [];

        $carga = DB::connection('legacy')->table('com_tarconfigcarga')->first();
        $cont = DB::connection('legacy')->table('com_tarconfigcont')->first();

        if (! $carga && ! $cont) {
            $this->reporte['configuraciones_tarifa'] = [
                'legacy' => 0,
                'nueva' => (int) DB::table('configuraciones_tarifa')->count(),
                'avisos' => ['Sin datos en legacy com_tarconfigcarga/com_tarconfigcont'],
            ];

            return;
        }

        try {
            DB::table('configuraciones_tarifa')->updateOrInsert(
                ['id' => 1],
                [
                    'demora_1' => $carga->demora1 ?? null,
                    'demora_2' => $carga->demora2 ?? null,
                    'kms_vacio_1' => $carga->kmsvacio1 ?? null,
                    'kms_vacio_2' => $carga->kmsvacio2 ?? null,
                    'tarifa_horaria_1' => $cont->tarhor1 ?? $carga->tarhor1 ?? null,
                    'tarifa_horaria_2' => $carga->tarhor2 ?? null,
                    'kms_adicionales_1' => $carga->kmsadic1 ?? null,
                    'kms_adicionales_2' => $carga->kmsadic2 ?? null,
                    'almacenaje' => $carga->almacenaje ?? null,
                    'recargo_1' => $carga->recargo1 ?? null,
                    'recargo_2' => $carga->recargo2 ?? null,
                    'recargo_3_1' => $carga->recargo31 ?? null,
                    'recargo_3_2' => $carga->recargo32 ?? null,
                    'recargo_3_3' => $carga->recargo33 ?? null,
                    'recargo_4' => $carga->recargo4 ?? null,
                    'recargo_5' => $carga->recargo5 ?? null,
                    'hora_1' => $carga->hora1 ?? null,
                    'hora_2' => $carga->hora2 ?? null,
                    'hora_3' => $carga->hora3 ?? null,
                    'izaje_1' => $cont->izaje1 ?? null,
                    'izaje_2' => $cont->izaje2 ?? null,
                    'valor_izaje_mt' => $cont->vizajemt ?? null,
                    'valor_izaje_me' => $cont->vizajeme ?? null,
                    'valor_almacenaje' => $cont->valmacenaje ?? null,
                    'plazo_libre_exp' => $cont->plibreexp ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $procesados = 1;
        } catch (\Throwable $e) {
            $avisos[] = "configuraciones_tarifa: {$e->getMessage()}";
            $procesados = 0;
        }

        $this->reporte['configuraciones_tarifa'] = [
            'legacy' => (int) DB::connection('legacy')->table('com_tarconfigcarga')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de acuerdos de tarifas: com_taracuerdos → tarifas_acuerdos.
     * FKs validadas contra clientes/lugares/productos; idunidad → id_entidad.
     */
    public function migrarTarifasAcuerdos(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;
        $omitidas = 0;

        DB::connection('legacy')->table('com_taracuerdos')
            ->orderBy('idtaracuerdos')
            ->chunk($chunk, function ($filas) use (&$procesados, &$omitidas, &$avisos) {
                foreach ($filas as $fila) {
                    if (! DB::table('clientes')->where('id', $fila->idcliente)->exists()
                        || ! DB::table('lugares')->where('id', $fila->idorigen)->exists()
                        || ! DB::table('lugares')->where('id', $fila->iddestino)->exists()) {
                        $omitidas++;

                        continue;
                    }

                    try {
                        DB::table('tarifas_acuerdos')->updateOrInsert(
                            ['id' => $fila->idtaracuerdos],
                            [
                                'id_cliente' => $fila->idcliente,
                                'id_origen' => $fila->idorigen,
                                'id_destino' => $fila->iddestino,
                                'id_producto' => ($fila->idproducto && DB::table('productos')->where('id', $fila->idproducto)->exists())
                                    ? $fila->idproducto
                                    : null,
                                'tarifa_mt' => $fila->tarmt,
                                'flete_mt' => $fila->fletemtt,
                                'id_entidad' => $fila->idunidad ?: null,
                                'origen_id' => $fila->idtaracuerdos,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        $procesados++;
                    } catch (\Throwable $e) {
                        $avisos[] = "tarifas_acuerdos#{$fila->idtaracuerdos}: {$e->getMessage()}";
                    }
                }
            });

        $this->reporte['tarifas_acuerdos'] = [
            'legacy' => (int) DB::connection('legacy')->table('com_taracuerdos')->count(),
            'nueva' => $procesados,
            'avisos' => array_merge(["{$omitidas} acuerdos omitidos por cliente/origen/destino inexistente"], $avisos),
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

        // Columnas enteras que en legacy pueden traer texto sucio (p. ej.
        // fabricacion='BIEL'): si no es numérico, se anula en vez de fallar.
        foreach ($config['int_or_null'] ?? [] as $col) {
            if (array_key_exists($col, $datos) && $datos[$col] !== null && ! is_numeric($datos[$col])) {
                $datos[$col] = null;
            }
        }

        // FKs huérfanas del legacy: si el id no existe en la tabla destino
        // (catálogo no migrado con ese id, o dato sucio), se anula en vez de
        // descartar la fila completa por violación de integridad referencial.
        foreach ($config['fk_validar'] ?? [] as $col => $tablaDestino) {
            if (! array_key_exists($col, $datos) || $datos[$col] === null) {
                continue;
            }
            if (! DB::table($tablaDestino)->where('id', $datos[$col])->exists()) {
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
     * ETL de tarjetas de combustible: cont_tarjetas → tarjetas.
     * - ids preservados (las FKs de cargas/descargas/cierres apuntan directo).
     * - numero = codtm legacy (UNIQUE en la nueva).
     * - id_entidad desde idunidad (cero → null).
     * - idempleado/idchofer/idtractivos solo si existen en bolsa/tractivos.
     */
    public function migrarTarjetas(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        $legacy = DB::connection('legacy');

        $idsBolsa = DB::table('bolsa')->pluck('id')->flip();
        $idsTractivos = DB::table('tractivos')->pluck('id')->flip();
        $idsEntidades = DB::table('entidades')->pluck('id')->flip();

        $legacy->table('cont_tarjetas')
            ->orderBy('idtarjeta')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $idsBolsa, $idsTractivos, $idsEntidades) {
                foreach ($filas as $fila) {
                    $idEntidad = (int) $fila->idunidad;
                    if (! isset($idsEntidades[$idEntidad])) {
                        $idEntidad = null;
                    }

                    $idEmpleado = (int) $fila->idempleado;
                    if (! $idEmpleado || ! isset($idsBolsa[$idEmpleado])) {
                        $idEmpleado = null;
                    }

                    $idChofer = (int) $fila->idchofer;
                    if (! $idChofer || ! isset($idsBolsa[$idChofer])) {
                        $idChofer = null;
                    }

                    $idTractivo = (int) $fila->idtractivos;
                    if (! $idTractivo || ! isset($idsTractivos[$idTractivo])) {
                        $idTractivo = null;
                    }

                    try {
                        DB::table('tarjetas')->updateOrInsert(
                            ['id' => $fila->idtarjeta],
                            [
                                'numero' => $fila->codtm,
                                'descripcion' => '',
                                'saldo_actual' => $fila->saldoactualmon,
                                'fcompra' => $fila->fcompra,
                                'fvence' => $fila->fvence,
                                'saldoinicialmon' => $fila->saldoinicialmon,
                                'saldoiniciallts' => $fila->saldoiniciallts,
                                'saldoactuallts' => $fila->saldoactuallts,
                                'saldotransferenciamon' => $fila->saldotransferenciamon,
                                'saldotransferencialts' => $fila->saldotransferencialts,
                                'idmonedas' => $fila->idmonedas,
                                'idtipocombustibles' => $fila->idtipocombustibles,
                                'idempleado' => $idEmpleado,
                                'idtractivos' => $idTractivo,
                                'idchofer' => $idChofer,
                                'cancelado' => $fila->cancelado,
                                'inactiva' => $fila->inactiva,
                                'fmovimiento' => $fila->fmovimiento,
                                'fcancelado' => $fila->fcancelado,
                                'fcierre' => $fila->fcierre,
                                'id_entidad' => $idEntidad,
                                'estado' => ((int) $fila->cancelado === 1) ? 'cancelada' : ((int) $fila->inactiva === 1 ? 'inactiva' : 'activa'),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        $procesados++;
                    } catch (\Throwable $e) {
                        $avisos[] = "tarjetas#{$fila->idtarjeta}: {$e->getMessage()}";
                    }
                }
            });

        $this->reporte['tarjetas'] = [
            'legacy' => (int) $legacy->table('cont_tarjetas')->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de cargas de combustible: cont_combcarga → combustible_cargas
     * (cabecera) y cont_combdetallecarga → detalles_carga_combustible
     * (detalle por tarjeta). Solo el año de negocio (2026).
     * - La cabecera lleva fcarga, saldocargado, saldoxtarjeta y moneda/
     *   tipo/responsable; el detalle lleva saldo_mon y saldo_lts por tarjeta.
     * - Se omiten cabeceras con idresponsable fuera de bolsa o detalle sin
     *   cabecera/tarjeta migrada.
     */
    public function migrarCargasCombustible(int $anio = 2026, int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        $legacy = DB::connection('legacy');

        $idsBolsa = DB::table('bolsa')->pluck('id')->flip();
        $idsEntidades = DB::table('entidades')->pluck('id')->flip();
        $idsTarjetas = DB::table('tarjetas')->pluck('id')->flip();
        $idsMonedas = DB::table('monedas')->pluck('id')->flip();
        $idsTiposComb = DB::table('tipos_combustibles')->pluck('id')->flip();

        $idsCargas = DB::table('combustible_cargas')->pluck('id')->flip();

        $legacy->table('cont_combcarga')
            ->whereYear('fcarga', $anio)
            ->orderBy('idcarga')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, &$idsCargas, $idsBolsa, $idsEntidades, $idsMonedas, $idsTiposComb) {
                foreach ($filas as $fila) {
                    $idResponsable = (int) $fila->idresponsable;
                    if (! isset($idsBolsa[$idResponsable])) {
                        $avisos[] = "combustible_cargas#{$fila->idcarga}: idresponsable {$idResponsable} fuera de bolsa, omitida";
                        continue;
                    }

                    $idEntidad = (int) $fila->idunidad;
                    if (! isset($idsEntidades[$idEntidad])) {
                        $idEntidad = null;
                    }

                    try {
                        DB::table('combustible_cargas')->updateOrInsert(
                            ['id' => $fila->idcarga],
                            [
                                'fcarga' => $fila->fcarga,
                                'saldocargado' => $fila->saldocargado,
                                'saldoxtarjeta' => $fila->saldoxtarjeta,
                                'id_monedas' => isset($idsMonedas[(int) $fila->idmonedas]) ? $fila->idmonedas : null,
                                'id_tipo_combustibles' => isset($idsTiposComb[(int) $fila->idtipocombustibles]) ? $fila->idtipocombustibles : null,
                                'id_responsable' => $idResponsable,
                                'folio' => $fila->folio,
                                'notas' => $fila->notas,
                                'id_entidad' => $idEntidad,
                                'estado' => 'registrada',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        $procesados++;
                        $idsCargas->put((int) $fila->idcarga, true);
                    } catch (\Throwable $e) {
                        $avisos[] = "combustible_cargas#{$fila->idcarga}: {$e->getMessage()}";
                    }
                }
            });

        // Detalle de carga por tarjeta (cont_combdetallecarga)
        $procesadosDetalle = 0;
        $avisosDetalle = [];

        $legacy->table('cont_combdetallecarga')
            ->whereYear('fcarga', $anio)
            ->orderBy('idmovimiento')
            ->chunk($chunk, function ($filas) use (&$procesadosDetalle, &$avisosDetalle, $idsCargas, $idsTarjetas) {
                foreach ($filas as $fila) {
                    $idCarga = (int) $fila->idcarga;
                    if (! isset($idsCargas[$idCarga])) {
                        $avisosDetalle[] = "detalles_carga_combustible#{$fila->idmovimiento}: cabecera {$idCarga} no migrada, omitido";
                        continue;
                    }
                    $idTarjeta = (int) $fila->idtarjeta;
                    if (! isset($idsTarjetas[$idTarjeta])) {
                        $avisosDetalle[] = "detalles_carga_combustible#{$fila->idmovimiento}: tarjeta {$idTarjeta} no migrada, omitido";
                        continue;
                    }

                    try {
                        DB::table('detalles_carga_combustible')->updateOrInsert(
                            ['id' => $fila->idmovimiento],
                            [
                                'id_carga' => $idCarga,
                                'id_tarjeta' => $idTarjeta,
                                'fcarga' => $fila->fcarga,
                                'folio' => $fila->folio,
                                'saldo_mon' => $fila->saldomon,
                                'saldo_lts' => $fila->saldolts,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        $procesadosDetalle++;
                    } catch (\Throwable $e) {
                        $avisosDetalle[] = "detalles_carga_combustible#{$fila->idmovimiento}: {$e->getMessage()}";
                    }
                }
            });

        $this->reporte['combustible_cargas'] = [
            'legacy' => (int) $legacy->table('cont_combcarga')->whereYear('fcarga', $anio)->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
        $this->reporte['detalles_carga_combustible'] = [
            'legacy' => (int) $legacy->table('cont_combdetallecarga')->whereYear('fcarga', $anio)->count(),
            'nueva' => $procesadosDetalle,
            'avisos' => $avisosDetalle,
        ];
    }

    /**
     * ETL de descargas de combustible: cont_combdescarga → combustible_descargas.
     * Solo el año de negocio (2026).
     * - id_entidad se deriva de la hoja de ruta migrada (hojas_ruta.id_entidad).
     * - Se omiten las descargas sin hoja de ruta migrada o sin tarjeta migrada.
     * - id_comprobante se conserva como columna suelta (sin FK: no existe
     *   tabla `comprobantes` en el esquema nuevo).
     */
    public function migrarDescargasCombustible(int $anio = 2026, int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        $legacy = DB::connection('legacy');

        $idsHojas = DB::table('hojas_ruta')->pluck('id')->flip();
        $idsTarjetas = DB::table('tarjetas')->pluck('id')->flip();
        $idsServicentros = DB::table('servicentros')->pluck('id')->flip();

        $entidadPorHoja = DB::table('hojas_ruta')
            ->whereNotNull('id_entidad')
            ->pluck('id_entidad', 'id');

        $legacy->table('cont_combdescarga')
            ->whereYear('fdescarga', $anio)
            ->orderBy('iddescarga')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $idsHojas, $idsTarjetas, $idsServicentros, $entidadPorHoja) {
                foreach ($filas as $fila) {
                    $idHoja = (int) $fila->idhojaruta;
                    if (! $idHoja || ! isset($idsHojas[$idHoja])) {
                        $avisos[] = "combustible_descargas#{$fila->iddescarga}: hoja de ruta {$idHoja} no migrada, omitida";
                        continue;
                    }
                    $idTarjeta = (int) $fila->idtarjeta;
                    if (! isset($idsTarjetas[$idTarjeta])) {
                        $avisos[] = "combustible_descargas#{$fila->iddescarga}: tarjeta {$idTarjeta} no migrada, omitida";
                        continue;
                    }

                    $idServicentro = (int) $fila->idservicentros;
                    if (! isset($idsServicentros[$idServicentro])) {
                        $idServicentro = null;
                    }

                    try {
                        DB::table('combustible_descargas')->updateOrInsert(
                            ['id' => $fila->iddescarga],
                            [
                                'id_tarjeta' => $idTarjeta,
                                'fdescarga' => $fila->fdescarga,
                                'folio' => $fila->folio,
                                'saldo_mon' => $fila->saldomon,
                                'saldo_lts' => $fila->saldolts,
                                'id_hoja_ruta' => $idHoja,
                                'id_comprobante' => $fila->idcomprobante ?: null,
                                'hora_descarga' => $fila->hdescarga,
                                'id_servicentro' => $idServicentro,
                                'f_chip' => $fila->fchip,
                                'kms' => $fila->kms,
                                'id_entidad' => $entidadPorHoja[$idHoja] ?? null,
                                'estado' => 'registrada',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        $procesados++;
                    } catch (\Throwable $e) {
                        $avisos[] = "combustible_descargas#{$fila->iddescarga}: {$e->getMessage()}";
                    }
                }
            });

        $this->reporte['combustible_descargas'] = [
            'legacy' => (int) $legacy->table('cont_combdescarga')->whereYear('fdescarga', $anio)->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de cierres mensuales de tarjetas: cont_htarjetas → cierre_tarjetas.
     * Solo el año de negocio (2026). Los ids legacy se preservan; id_tarjeta
     * solo si la tarjeta fue migrada (resto se omite).
     */
    public function migrarCierreTarjetas(int $anio = 2026, int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        $legacy = DB::connection('legacy');

        $idsTarjetas = DB::table('tarjetas')->pluck('id')->flip();
        $idsEntidades = DB::table('entidades')->pluck('id')->flip();
        $idsMonedas = DB::table('monedas')->pluck('id')->flip();
        $idsTiposComb = DB::table('tipos_combustibles')->pluck('id')->flip();

        $legacy->table('cont_htarjetas')
            ->whereYear('ftrabajo', $anio)
            ->orderBy('idhtarjeta')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $idsTarjetas, $idsEntidades, $idsMonedas, $idsTiposComb) {
                foreach ($filas as $fila) {
                    $idTarjeta = (int) $fila->idtarjeta;
                    if (! isset($idsTarjetas[$idTarjeta])) {
                        $avisos[] = "cierre_tarjetas#{$fila->idhtarjeta}: tarjeta {$idTarjeta} no migrada, omitido";
                        continue;
                    }

                    $idEntidad = (int) $fila->idunidad;
                    if (! isset($idsEntidades[$idEntidad])) {
                        $idEntidad = null;
                    }

                    try {
                        DB::table('cierre_tarjetas')->updateOrInsert(
                            ['id' => $fila->idhtarjeta],
                            [
                                'ftrabajo' => $fila->ftrabajo,
                                'id_tarjeta' => $idTarjeta,
                                'codtm' => $fila->codtm,
                                'saldoinicialmon' => $fila->saldoinicialmon,
                                'saldoiniciallts' => $fila->saldoiniciallts,
                                'id_monedas' => isset($idsMonedas[(int) $fila->idmonedas]) ? $fila->idmonedas : null,
                                'id_tipo_combustibles' => isset($idsTiposComb[(int) $fila->idtipocombustibles]) ? $fila->idtipocombustibles : null,
                                'preciomn' => $fila->preciomn,
                                'saldocargadomon' => $fila->saldocargadomon,
                                'saldocargadolts' => $fila->saldocargadolts,
                                'saldodescargadomon' => $fila->saldodescargadomon,
                                'saldodescargadolts' => $fila->saldodescargadolts,
                                'saldotransferenciamon' => $fila->saldotransferenciamon,
                                'saldotransferencialts' => $fila->saldotransferencialts,
                                'saldoactualmon' => $fila->saldoactualmon,
                                'saldoactuallts' => $fila->saldoactuallts,
                                'id_entidad' => $idEntidad,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        $procesados++;
                    } catch (\Throwable $e) {
                        $avisos[] = "cierre_tarjetas#{$fila->idhtarjeta}: {$e->getMessage()}";
                    }
                }
            });

        $this->reporte['cierre_tarjetas'] = [
            'legacy' => (int) $legacy->table('cont_htarjetas')->whereYear('ftrabajo', $anio)->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de dietas: cont_dietas → dietas. Solo el año de negocio (2026).
     * - id_bolsa/id_hoja_ruta son NOT NULL en la tabla nueva; se omiten las
     *   dietas sin empleado en bolsa o sin hoja de ruta migrada.
     * - idmonedas legacy viene en 0 → null (monedas solo 1=MN, 2=CL).
     * - id_entidad se deriva de la hoja de ruta.
     */
    public function migrarDietas(int $anio = 2026, int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;

        $legacy = DB::connection('legacy');

        $idsBolsa = DB::table('bolsa')->pluck('id')->flip();
        $idsHojas = DB::table('hojas_ruta')->pluck('id')->flip();
        $idsTractivos = DB::table('tractivos')->pluck('id')->flip();
        $idsMonedas = DB::table('monedas')->pluck('id')->flip();
        $idsReembolsos = DB::table('reembolsos')->pluck('id')->flip();

        $entidadPorHoja = DB::table('hojas_ruta')
            ->whereNotNull('id_entidad')
            ->pluck('id_entidad', 'id');

        $legacy->table('cont_dietas')
            ->whereYear('fcostodietas', $anio)
            ->orderBy('idcostodietas')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $idsBolsa, $idsHojas, $idsTractivos, $idsMonedas, $idsReembolsos, $entidadPorHoja) {
                foreach ($filas as $fila) {
                    $idBolsa = (int) $fila->idempleado;
                    if (! isset($idsBolsa[$idBolsa])) {
                        $avisos[] = "dietas#{$fila->idcostodietas}: empleado {$idBolsa} fuera de bolsa, omitida";
                        continue;
                    }
                    $idHoja = (int) $fila->idhojaruta;
                    if (! isset($idsHojas[$idHoja])) {
                        $avisos[] = "dietas#{$fila->idcostodietas}: hoja de ruta {$idHoja} no migrada, omitida";
                        continue;
                    }

                    $idTractivo = (int) $fila->idtractivos;
                    if (! isset($idsTractivos[$idTractivo])) {
                        $idTractivo = null;
                    }

                    $idReembolso = (int) $fila->idreembolso;
                    if (! $idReembolso || ! isset($idsReembolsos[$idReembolso])) {
                        $idReembolso = null;
                    }

                    $idMonedas = (int) $fila->idmonedas;
                    if (! isset($idsMonedas[$idMonedas])) {
                        $idMonedas = null;
                    }

                    try {
                        DB::table('dietas')->updateOrInsert(
                            ['id' => $fila->idcostodietas],
                            [
                                'id_bolsa' => $idBolsa,
                                'id_hoja_ruta' => $idHoja,
                                'folio' => $fila->folio,
                                'fecha' => $fila->fcostodietas,
                                'monto' => $fila->total,
                                'anticipo' => $fila->anticipo,
                                'f_anticipo' => $fila->fanticipo,
                                'alimentos' => $fila->alimentos,
                                'hospedaje' => $fila->hospedaje,
                                'otros' => $fila->otros,
                                'id_monedas' => $idMonedas,
                                'id_tractivo' => $idTractivo,
                                'id_reembolso' => $idReembolso,
                                'f_liquidacion' => $fila->fliquidacion,
                                'folio_caja' => $fila->foliocaja ?: null,
                                'cancelada' => (int) $fila->cancelada === 1,
                                'id_entidad' => $entidadPorHoja[$idHoja] ?? null,
                                'tipo_dieta' => 'viático',
                                'estado' => (int) $fila->cancelada === 1 ? 'cancelada' : 'registrada',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        $procesados++;
                    } catch (\Throwable $e) {
                        $avisos[] = "dietas#{$fila->idcostodietas}: {$e->getMessage()}";
                    }
                }
            });

        $this->reporte['dietas'] = [
            'legacy' => (int) $legacy->table('cont_dietas')->whereYear('fcostodietas', $anio)->count(),
            'nueva' => $procesados,
            'avisos' => $avisos,
        ];
    }

    /**
     * ORDEN ORGANIZATIVA (jerarquía de entidades).
     *
     * Regla de negocio: la OFICINA CENTRAL (entidad con abreviatura
     * 'OFICINA CENTRAL', es decir EMPRESA CAMIONES EMCARGA) es la MATRIZ
     * de la empresa: es_matriz = true y parent_id = NULL. Todas las demás
     * entidades (filiales, UEB, brigadas) quedan subordinadas a ella
     * (parent_id = id de la OFICINA CENTRAL). Esta regla debe preservarse
     * en todas las corridas ETL.
     *
     * Además, el usuario EIDEL (id 1) es SUPERADMIN y pertenece a la
     * OFICINA CENTRAL. Cada corrida ETL re-asegura esta condición porque
     * migrarUsuarios() re-sincroniza roles y entidad desde legacy.
     *
     * Idempotente: se puede ejecutar todas las veces que se quiera.
     */
    public function migrarJerarquiaEntidades(): void
    {
        $avisos = [];

        $oficinaCentral = DB::table('entidades')
            ->where('abreviatura', 'OFICINA CENTRAL')
            ->first();

        if (! $oficinaCentral) {
            $avisos[] = 'No se encontró la entidad OFICINA CENTRAL (abreviatura OFICINA CENTRAL)';

            $this->reporte['jerarquia_entidades'] = [
                'legacy' => 0,
                'nueva' => 0,
                'avisos' => $avisos,
            ];

            return;
        }

        // 1) La OFICINA CENTRAL es la matriz.
        DB::table('entidades')
            ->where('id', $oficinaCentral->id)
            ->update([
                'es_matriz' => true,
                'parent_id' => null,
                'updated_at' => now(),
            ]);

        // 2) Todas las demás entidades le están subordinadas.
        DB::table('entidades')
            ->where('id', '!=', $oficinaCentral->id)
            ->where(function ($q) use ($oficinaCentral) {
                $q->whereNull('parent_id')
                    ->orWhere('parent_id', '!=', $oficinaCentral->id);
            })
            ->update([
                'parent_id' => $oficinaCentral->id,
                'updated_at' => now(),
            ]);

        // 3) EIDEL es SUPERADMIN y pertenece a la OFICINA CENTRAL.
        $eidel = User::find(1);
        if ($eidel) {
            $eidel->forceFill(['id_entidad' => $oficinaCentral->id])->save();
            if (Role::where('name', 'SUPERADMIN')->exists()) {
                $eidel->syncRoles(['SUPERADMIN']);
            }
        } else {
            $avisos[] = 'No se encontró el usuario EIDEL (id 1)';
        }

        $this->reporte['jerarquia_entidades'] = [
            'legacy' => (int) DB::table('entidades')->count(),
            'nueva' => (int) DB::table('entidades')->count(),
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de control de lubricantes CT-7: tec_controllubricante → control_lubricantes.
     *
     * El legacy registra el consumo por sistema del vehículo (motor, transmisión,
     * dirección, hidráulico, frenos, agua, grasas rollete/copillas) con el tipo de
     * lubricante de cada sistema (FK a lubricantes) y un tipo de operación
     * (RELLENO/MTTO/O.CAUSAS). Los ids de lubricantes se preservan (1:1).
     */
    public function migrarControlLubricantes(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;
        $legacy = DB::connection('legacy');

        $lubIds = DB::table('lubricantes')->pluck('id')->flip();
        $lubFk = function ($v) use ($lubIds): ?int {
            $v = (int) $v;

            return $v && isset($lubIds[$v]) ? $v : null;
        };

        $legacy->table('tec_controllubricante')
            ->orderBy('idcontrollubricante')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $lubFk) {
                foreach ($filas as $fila) {
                    $fecha = $fila->fconfeccion;
                    if (is_string($fecha) && str_starts_with($fecha, '0000-00-00')) {
                        $fecha = null;
                    }

                    DB::table('control_lubricantes')->updateOrInsert(
                        ['id' => $fila->idcontrollubricante],
                        [
                            'id_tractivo' => (int) $fila->idtractivos ?: null,
                            'id_unidad' => (int) $fila->idunidad ?: null,
                            'fecha_cambio' => $fecha,
                            'tipo_operacion' => $fila->tipooperacion ?: 'RELLENO',
                            'litros_motor' => $fila->litrosmotor ?: 0,
                            'litros_transmision' => $fila->litrostransmision ?: 0,
                            'litros_direccion' => $fila->litrosdireccion ?: 0,
                            'litros_hidraulico' => $fila->litroshidraulico ?: 0,
                            'liquido_freno' => $fila->liquidofreno ?: 0,
                            'agua_refrigerada' => $fila->aguarefrigerada ?: 0,
                            'grasa_rollete' => $fila->grasarollete ?: 0,
                            'grasa_copillas' => $fila->grasacopillas ?: 0,
                            'id_lub_motor' => $lubFk($fila->idlubmotor),
                            'id_lub_transmision' => $lubFk($fila->idlubtransmision),
                            'id_lub_hidraulico' => $lubFk($fila->idlubhidraulico),
                            'id_lub_direccion' => $lubFk($fila->idlubdireccion),
                            'id_grasa_rollete' => $lubFk($fila->idgrasar),
                            'id_grasa_copillas' => $lubFk($fila->idgrasac),
                            'id_liquido_freno' => $lubFk($fila->idliqfreno),
                            'id_agua' => $lubFk($fila->idagua),
                            'id_entidad' => (int) $fila->idunidad ?: null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                    $procesados++;
                }
            });

        $this->reporte['control_lubricantes'] = [
            'legacy' => (int) $legacy->table('tec_controllubricante')->count(),
            'nueva' => (int) DB::table('control_lubricantes')->count(),
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de movimientos de neumáticos: tec_neumaticosmov → neumaticos_movimientos.
     *
     * Cada cambio de vehículo/posición/destino de un neumático genera un movimiento
     * (montaje → retiro). El cabezal `neumaticos` ya migró en migrarNeumaticos().
     */
    public function migrarNeumaticosMovimientos(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;
        $legacy = DB::connection('legacy');

        $legacy->table('tec_neumaticosmov')
            ->orderBy('idneumaticosmov')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos) {
                foreach ($filas as $fila) {
                    $fmontado = $fila->fmontado;
                    if (is_string($fmontado) && str_starts_with($fmontado, '0000-00-00')) {
                        $fmontado = null;
                    }
                    $fretirado = $fila->fretirado;
                    if (is_string($fretirado) && str_starts_with($fretirado, '0000-00-00')) {
                        $fretirado = null;
                    }

                    if (! DB::table('neumaticos')->where('id', $fila->idneumaticos)->exists()) {
                        $avisos[] = "movneum#{$fila->idneumaticosmov}: neumático legacy {$fila->idneumaticos} inexistente, omitido";

                        continue;
                    }

                    DB::table('neumaticos_movimientos')->updateOrInsert(
                        ['id' => $fila->idneumaticosmov],
                        [
                            'id_neumatico' => $fila->idneumaticos,
                            'id_tractivo' => (int) $fila->idtractivos ?: null,
                            'fecha_montaje' => $fmontado,
                            'fecha_retiro' => $fretirado,
                            'km_instalado' => $fila->kminstalado ?: 0,
                            'km_retirado' => $fila->kmretirado ?: null,
                            'posicion' => (int) $fila->idposicion ?: null,
                            'id_destino' => (int) $fila->iddestagregados ?: null,
                            'observaciones' => trim((string) ($fila->observacion ?? '')) ?: null,
                            'id_entidad' => (int) $fila->idunidad ?: null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                    $procesados++;
                }
            });

        $this->reporte['neumaticos_movimientos'] = [
            'legacy' => (int) $legacy->table('tec_neumaticosmov')->count(),
            'nueva' => (int) DB::table('neumaticos_movimientos')->count(),
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL completo de órdenes de taller: tec_ordentaller → ordenes_taller.
     *
     * Amplía el ETL previo (solo año 2026 con tipo de mantenimiento válido) para
     * migrar TODAS las órdenes con los campos de la cabecera legacy (horas,
     * motivo de entrada, clasificación, plan de mantenimiento, prueba de motor,
     * paralización, taller exterior). Se preservan los ids legacy.
     */
    public function migrarOrdenesTallerCompleto(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;
        $legacy = DB::connection('legacy');

        // Lookups de ids válidos en las tablas destino (los FKs legacy pueden
        // apuntar a ids que no existen o que cambiaron).
        $ids = [
            'bolsa' => DB::table('bolsa')->pluck('id')->flip(),
            'motores' => DB::table('motores')->pluck('id')->flip(),
            'talleres' => DB::table('talleres')->pluck('id')->flip(),
            'entidades' => DB::table('entidades')->pluck('id')->flip(),
            'motivos_entrada' => DB::table('motivos_entrada_taller')->pluck('id')->flip(),
            'clasificaciones' => DB::table('clasificaciones_ordenes_taller')->pluck('id')->flip(),
            'tipos_mantenimiento' => DB::table('tipos_mantenimiento')->pluck('id')->flip(),
            'tractivos' => DB::table('tractivos')->pluck('id')->flip(),
        ];
        $fk = function (string $key, $v) use ($ids): ?int {
            $v = (int) $v;

            return $v && isset($ids[$key][$v]) ? $v : null;
        };

        $legacy->table('tec_ordentaller')
            ->orderBy('idordentaller')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $fk) {
                foreach ($filas as $fila) {
                    $fentrada = $fila->fentrada;
                    if (is_string($fentrada) && str_starts_with($fentrada, '0000-00-00')) {
                        $fentrada = null;
                    }
                    $fsalida = $fila->fsalida;
                    if (is_string($fsalida) && str_starts_with($fsalida, '0000-00-00')) {
                        $fsalida = null;
                    }
                    $idEntidad = $fk('entidades', $fila->idunidad);

                    DB::table('ordenes_taller')->updateOrInsert(
                        ['id' => $fila->idordentaller],
                        [
                            'numero' => $fila->ordentaller,
                            'id_tractivo' => $fk('tractivos', $fila->idtractivos),
                            'id_tipo_mantenimiento' => $fk('tipos_mantenimiento', $fila->idtipomtto),
                            'fecha_ingreso' => $fentrada,
                            'hora_ingreso' => $fila->hentrada ?: null,
                            'fecha_salida' => $fsalida,
                            'hora_salida' => $fila->hsalida ?: null,
                            'ottiempo' => $fila->ottiempo ?: 0,
                            'id_user' => $fk('bolsa', $fila->iduser),
                            'id_motivo_entrada' => $fk('motivos_entrada', $fila->idmotentrada),
                            'id_clasificacion' => $fk('clasificaciones', $fila->idtipoclasificacion),
                            'cant_clasificacion' => $fila->cantclasif ?: null,
                            'id_reporte' => $fk('bolsa', $fila->idreportado),
                            'id_confeccionado' => $fk('bolsa', $fila->idconfeccionado),
                            'id_operario' => $fk('bolsa', $fila->idoperario),
                            'notas' => trim((string) ($fila->notas ?? '')) ?: null,
                            'cancelada' => (bool) $fila->cancelada,
                            'tipo_mtto' => $fila->tipomtto ?: null,
                            'km_mtto' => (int) $fila->kmmtto ?: null,
                            'planificacion' => (int) $fila->planificacion ?: null,
                            'km_mtto_prox' => (int) $fila->kmmttoprox ?: null,
                            'ot_paralizado' => $fila->otparalizado ?: null,
                            'ot_rotura_en_linea' => $fila->otroturaenlinea ?: null,
                            'ot_largo_plazo' => $fila->otlargoplazo ?: null,
                            'comb_taller' => $fila->combtaller ?: 0,
                            'id_motor' => $fk('motores', $fila->idmotores),
                            'id_taller' => $fk('talleres', $fila->idtalleres),
                            'id_unidad' => $idEntidad,
                            'id_entidad' => $idEntidad,
                            'pl_cons_comb' => $fila->plconscomb ?: null,
                            'pl_cons_aceite' => $fila->plconsaceite ?: null,
                            'pl_cil1' => $fila->plcil1 ?: null,
                            'pl_cil2' => $fila->plcil2 ?: null,
                            'pl_cil3' => $fila->plcil3 ?: null,
                            'pl_cil4' => $fila->plcil4 ?: null,
                            'pl_cil5' => $fila->plcil5 ?: null,
                            'pl_cil6' => $fila->plcil6 ?: null,
                            'pl_cil7' => $fila->plcil7 ?: null,
                            'pl_cil8' => $fila->plcil8 ?: null,
                            'pl_presion_aceite_baja' => $fila->plpresionaceitebaja ?: null,
                            'pl_presion_aceite_alta' => $fila->plpresionaceitealta ?: null,
                            'pl_temp_agua' => $fila->pltempagua ?: null,
                            'pl_temp_aceite' => $fila->pltempaceite ?: null,
                            'pl_observacion' => $fila->plobservacion ?: null,
                            'fecha_salida_estimada' => $fsalida,
                            'fecha_salida_real' => $fsalida,
                            'kilometraje' => $fila->kmmtto ?: null,
                            'estado' => $fila->cancelada ? 'cancelada' : ($fsalida ? 'cerrada' : 'abierta'),
                            'diagnostico' => null,
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
            'nueva' => (int) DB::table('ordenes_taller')->count(),
            'avisos' => $avisos,
        ];
    }

    /**
     * ETL de gastos/piezas de almacén por OT: tec_otgasto → gastos_orden.
     *
     * El legacy registra los recursos de almacén (piezas) de cada orden de taller:
     * vale, código de pieza, tipo de agregado, nombre, cantidad, motivo y el motor
     * del tractivo (para trazabilidad de agregados por motor).
     */
    public function migrarGastosOrden(int $chunk = 1000): void
    {
        $avisos = [];
        $procesados = 0;
        $legacy = DB::connection('legacy');

        $ids = [
            'motores' => DB::table('motores')->pluck('id')->flip(),
            'tipos_agregados' => DB::table('tipos_agregados')->pluck('id')->flip(),
            'ordenes' => DB::table('ordenes_taller')->pluck('id')->flip(),
            'entidades' => DB::table('entidades')->pluck('id')->flip(),
        ];
        $fk = function (string $key, $v) use ($ids): ?int {
            $v = (int) $v;

            return $v && isset($ids[$key][$v]) ? $v : null;
        };

        $legacy->table('tec_otgasto')
            ->orderBy('idotgasto')
            ->chunk($chunk, function ($filas) use (&$procesados, &$avisos, $fk) {
                foreach ($filas as $fila) {
                    if (! $fk('ordenes', $fila->idordentaller)) {
                        $avisos[] = "otgasto#{$fila->idotgasto}: OT legacy {$fila->idordentaller} inexistente, omitido";

                        continue;
                    }

                    DB::table('gastos_orden')->updateOrInsert(
                        ['id' => $fila->idotgasto],
                        [
                            'id_orden_taller' => $fila->idordentaller,
                            'importe_me' => $fila->importeme ?: 0,
                            'vale' => $fila->otgastovale ?: null,
                            'id_tipo_agregado' => $fk('tipos_agregados', $fila->idtipoagregados),
                            'nombre' => $fila->otgastonombre ?: null,
                            'cantidad' => $fila->otgastocant ?: 0,
                            'codigo_pieza' => $fila->otgastocodpieza ?: null,
                            'motivo' => $fila->otgastomotivo ?: null,
                            'id_motor' => $fk('motores', $fila->idmotores),
                            'id_entidad' => $fk('entidades', $fila->idunidad),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                    $procesados++;
                }
            });

        $this->reporte['gastos_orden'] = [
            'legacy' => (int) $legacy->table('tec_otgasto')->count(),
            'nueva' => (int) DB::table('gastos_orden')->count(),
            'avisos' => $avisos,
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
        // tractivos grupo ARRASTRES (idgrupo=8), ya unificados en `tractivos`.
        $legacyArrastres = (int) DB::connection('legacy')->table('tec_tractivos')
            ->where('idgrupo', 8)
            ->count();
        $resultado['arrastres'] = [
            'legacy' => $legacyArrastres,
            'nueva' => (int) DB::table('tractivos')->where('id_grupo', 8)->count(),
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
        $resultado['plantilla'] = [
            'legacy' => (int) DB::connection('legacy')->table('rh_plantilla')->count(),
            'nueva' => (int) DB::table('plantilla')->count(),
        ];
        $resultado['salarios_administrativos'] = [
            'legacy' => (int) DB::connection('legacy')->table('rh_saladmin')->count(),
            'nueva' => (int) DB::table('salarios_administrativos')->count(),
        ];
        $resultado['facturas'] = [
            'legacy' => (int) DB::connection('legacy')->table('com_rfactura')
                ->whereYear('ffactura', 2026)->count(),
            'nueva' => (int) DB::table('facturas')->count(),
        ];
        $resultado['aforos'] = [
            'legacy' => (int) DB::connection('legacy')->table('com_aforo')
                ->whereYear('fparte', 2026)->count(),
            'nueva' => (int) DB::table('aforos')->count(),
        ];
        $resultado['tarifas'] = [
            'legacy' => (int) DB::connection('legacy')->table('com_tarifas46')->count()
                + (int) DB::connection('legacy')->table('com_tarifas')->count(),
            'nueva' => (int) DB::table('tarifas')->count(),
        ];
        $resultado['configuraciones_tarifa'] = [
            'legacy' => (int) DB::connection('legacy')->table('com_tarconfigcarga')->count(),
            'nueva' => (int) DB::table('configuraciones_tarifa')->count(),
        ];
        $resultado['tarifas_acuerdos'] = [
            'legacy' => (int) DB::connection('legacy')->table('com_taracuerdos')->count(),
            'nueva' => (int) DB::table('tarifas_acuerdos')->count(),
        ];

        // Combustible (solo año de negocio 2026)
        $legacy = DB::connection('legacy');
        $resultado['tarjetas'] = [
            'legacy' => (int) $legacy->table('cont_tarjetas')->count(),
            'nueva' => (int) DB::table('tarjetas')->count(),
        ];
        $resultado['combustible_cargas'] = [
            'legacy' => (int) $legacy->table('cont_combcarga')->whereYear('fcarga', 2026)->count(),
            'nueva' => (int) DB::table('combustible_cargas')->count(),
        ];
        $resultado['detalles_carga_combustible'] = [
            'legacy' => (int) $legacy->table('cont_combdetallecarga')->whereYear('fcarga', 2026)->count(),
            'nueva' => (int) DB::table('detalles_carga_combustible')->count(),
        ];
        $resultado['combustible_descargas'] = [
            'legacy' => (int) $legacy->table('cont_combdescarga')->whereYear('fdescarga', 2026)->count(),
            'nueva' => (int) DB::table('combustible_descargas')->count(),
        ];
        $resultado['cierre_tarjetas'] = [
            'legacy' => (int) $legacy->table('cont_htarjetas')->whereYear('ftrabajo', 2026)->count(),
            'nueva' => (int) DB::table('cierre_tarjetas')->count(),
        ];
        $resultado['dietas'] = [
            'legacy' => (int) $legacy->table('cont_dietas')->whereYear('fcostodietas', 2026)->count(),
            'nueva' => (int) DB::table('dietas')->count(),
        ];
        $resultado['indirectos_mensuales'] = [
            'legacy' => (int) $legacy->table('cont_contabilidad')->count(),
            'nueva' => (int) DB::table('indirectos_mensuales')->count(),
        ];

        return $resultado;
    }
}
