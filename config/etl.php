<?php

/*
 * Mapeo ETL legacy (CodeIgniter) → nuevo esquema (Laravel). Fase 3.
 *
 * REGENERADO y verificado contra el dump real (database/etl/legacy_schema.json)
 * y contra el esquema nuevo (migraciones). El test EtlMapeoTest lo mantiene
 * verde en CI: cualquier divergencia futura falla la suite.
 *
 * Cada entrada de 'tablas' define:
 *   - legacy:    tabla origen (con prefijo de módulo)
 *   - pk:        columna pk legacy para ordenar/preservar id (null si no tiene)
 *   - clave:     columnas de upsert cuando la tabla nueva no tiene `id`
 *   - columnas:  mapa columna_legacy => columna_nueva (solo las migradas)
 *   - defaults:  valores fijos para columnas nuevas sin origen
 *
 * Los ids legacy se preservan como ids nuevos. Los campos legacy sin
 * columna destino se descartan de forma consciente (D4); el detalle por
 * tabla está en database/etl/REPORTE_MAPEO.md.
 */

return [
    /*
     * Mapeo idperfil (rh_perfiles legacy) => rol spatie.
     * DIRECTIVOS (5) no existe como rol en la plataforma nueva:
     * se mapea a ADMIN (decisión abierta, confirmar con el cliente).
     */
    'mapeo_perfiles' => [
        1 => 'RECHUM',      // RECURSOS HUMANOS
        2 => 'TECNICA',     // TECNICA
        3 => 'COMERCIAL',   // COMERCIAL
        4 => 'CONTABILIDAD', // CONTABILIDAD
        5 => 'SUPERADMIN',       // DIRECTIVOS → SUPERADMIN (provisional)
        6 => 'SUPERADMIN',       // ADMINISTRADOR
        7 => 'OPERATIVOS',  // OPERATIVOS
    ],

    /*
     * Tablas de negocio/transaccionales: solo estructura, sin migrar datos.
     * Los catálogos/tipos sí se migran completos.
     * Los datos faltantes se depuran sobre la BD nueva directamente.
     */
    'excluir_datos' => [
        'clientes', 'configuraciones_modelo', 'demandas', 'distancias',
        'prefacturas', 'tarifas', 'tarifas_config_carga', 'tarifas_config_contenedor',
        'amortizaciones', 'combustibles_lubricantes',
        'balances_electricos', 'control_lubricantes',
        'diferenciales', 'gastos_orden', 'historial_tractivos',
        'lecturas_medidores', 'locales_electricos', 'motores',
        'movimientos_taller', 'motores_movimientos', 'neumaticos',
        'neumaticos_movimientos', 'ordenes_operaciones', 'ordenes_taller',
        'otros_agregados', 'lineas_mantenimiento', 'cierres_cdt',
        'planes_mantenimiento', 'tarjetas',
    ],

    'tablas' => [

        /*
         * ================================================================
         * SEGURIDAD
         * ================================================================
         */

        /*
         * ================================================================
         * COMERCIALES
         * ================================================================
         */

        'clientes' => [
            'legacy' => 'com_clientes',
            'pk' => 'idcliente',
            'columnas' => [
                'codcliente' => 'codigo',
                'nombcliente' => 'nombre',
                'nit' => 'nit',
                'dircliente' => 'direccion',
                'email' => 'email',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'clientes_seleccion' => [
            'legacy' => 'com_clientes_seleccion',
            'pk' => 'idclientesel',
            'columnas' => [
                'nombclientesel' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'configuraciones_modelo' => [
            'legacy' => 'com_configmodelo',
            'pk' => 'idptomodelo',
            'columnas' => [
                'nombpto' => 'nombre',
                'idtipomodelo' => 'id_tipo_modelo',
                'setx' => 'set_x',
                'sety' => 'set_y',
                'letra' => 'letra',
            ],
        ],

        'demandas' => [
            'legacy' => 'com_demandas',
            'pk' => 'iddemandas',
            'columnas' => [
                'fdemanda' => 'fecha_demanda',
                'idcliente' => 'id_cliente',
                'idproducto' => 'id_producto',
                'idorigen' => 'id_origen',
                'iddestino' => 'id_destino',
                'idembalaje' => 'id_embalaje',
            ],
            'defaults' => [
                'viajes' => 0,
                'kms_totales' => 0,
                'kms_carga' => 0,
                'tiempo_demanda' => 0,
                'tiempo_aceptacion' => 0,
                'estado' => 'activa',
            ],
        ],

        'distancias' => [
            'legacy' => 'com_distancias',
            'pk' => 'iddistancia',
            'columnas' => [
                'idorigen' => 'id_lugar_origen',
                'iddestino' => 'id_lugar_destino',
                'kms' => 'distancia_km',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'embalajes' => [
            'legacy' => 'com_embalajes',
            'pk' => 'idembalaje',
            'columnas' => [
                'nombembalaje' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'lugares' => [
            'legacy' => 'com_lugares',
            'pk' => 'idlugar',
            'columnas' => [
                'nomblugar' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'monedas' => [
            'legacy' => 'com_monedas',
            'pk' => 'idmonedas',
            'columnas' => [
                'monedas' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'prefacturas' => [
            'legacy' => 'com_prefactura',
            'pk' => 'idfactura',
            'columnas' => [
                'ffactura' => 'fecha',
                'factura' => 'numero',
                'idcliente' => 'id_cliente',
                'iduser' => 'id_user',
            ],
            'defaults' => [
                'flete_mt' => 0,
                'flete_mlc' => 0,
                'estado' => 'pendiente',
            ],
        ],

        'productos' => [
            'legacy' => 'com_productos',
            'pk' => 'idproducto',
            'columnas' => [
                'nombproducto' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tarifas' => [
            'legacy' => 'com_tarifas',
            'pk' => 'idtarifas',
            'columnas' => [
                'idtipocargas' => 'id_tipo_carga',
                'kms' => 'kms',
            ],
            'defaults' => [
                'version' => 'normal',
            ],
        ],

        'tarifas_config_carga' => [
            'legacy' => 'com_tarconfigcarga',
            'pk' => 'idtarconfigcarga',
            'defaults' => [
                'version' => 'carga',
            ],
        ],

        'tarifas_config_contenedor' => [
            'legacy' => 'com_tarconfigcont',
            'pk' => 'idtarconfigcont',
            'defaults' => [
                'version' => 'contenedor',
            ],
        ],

        'tipo_ingresos' => [
            'legacy' => 'com_tipoingresos',
            'pk' => 'idtipoingresos',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_cargas' => [
            'legacy' => 'com_tipocargas',
            'pk' => 'idtipocargas',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_cargas_reporte' => [
            'legacy' => 'com_tipocargasreporte',
            'pk' => 'idtipocargasreporte',
        ],

        'tipos_catalogo_lugares' => [
            'legacy' => 'com_tipocatlugares',
            'pk' => 'idtipocatlugares',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_estados' => [
            'legacy' => 'com_tipoestados',
            'pk' => 'idtipoestados',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_indicadores' => [
            'legacy' => 'com_tipoindicadores',
            'pk' => 'idtipoindicadores',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_modelo' => [
            'legacy' => 'com_tipomodelo',
            'pk' => 'idtipomod',
            'columnas' => [
                'modelo' => 'modelo',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_servicios' => [
            'legacy' => 'com_tiposervicios',
            'pk' => 'idtiposervicios',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'turnos_comerciales' => [
            'legacy' => 'com_turnos',
            'pk' => 'idturno',
            'columnas' => [
                'nombturno' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tarjetas' => [
            'legacy' => 'cont_tarjetas',
            'pk' => 'idtarjeta',
            'columnas' => [
                'codtm' => 'numero',
                'saldoactualmon' => 'saldo_actual',
            ],
            'defaults' => [
                'descripcion' => '',
                'id_cliente' => 0,
            ],
        ],

        /*
         * ================================================================
         * CONTABILIDAD
         * ================================================================
         */

        'amortizaciones' => [
            'legacy' => 'cont_amortizacion',
            'pk' => 'idamortizacion',
            'columnas' => [
                'idtractivos' => 'id_tractivo',
                'amortizacionmn' => 'amortizacion_mn',
            ],
            'defaults' => [
                'fecha' => '1970-01-01',
            ],
        ],

        'combustibles_lubricantes' => [
            'legacy' => 'cont_comblubricantes',
            'pk' => 'idlubricantes',
            'columnas' => [
                'flubricantes' => 'fecha',
                'idtractivos' => 'id_tractivo',
                'folio' => 'folio',
                'cantidad' => 'cantidad',
            ],
            'defaults' => [
                'importe_mn' => 0,
            ],
        ],

        'servicentros' => [
            'legacy' => 'cont_servicentros',
            'pk' => 'idservicentros',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_conceptos' => [
            'legacy' => 'cont_tipoconceptos',
            'pk' => 'idtipoconcepto',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_documentos' => [
            'legacy' => 'cont_tipodocumentos',
            'pk' => 'idtipodoc',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_gastos' => [
            'legacy' => 'cont_tipogastos',
            'pk' => 'idtipogastos',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        /*
         * ================================================================
         * RRHH
         * ================================================================
         */

        /*
         * Entidades organizativas (empresa filial, UEBs).
         * Se preserva el id legacy (identidades) porque users.id_entidad
         * y las tablas operativas con idunidad apuntan a él.
         * El codigo legacy ('151' repetido) no se migra: no es único.
         */
        'entidades' => [
            'legacy' => 'rh_entidades',
            'pk' => 'identidades',
            'columnas' => [
                'nombentidad' => 'nombre',
                'abreviatura' => 'abreviatura',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'categorias_cargo' => [
            'legacy' => 'rh_tipocatcargos',
            'pk' => 'idtipocatcargos',
            'columnas' => [
                'nombcatcargo' => 'nombre',
                'abreviatura' => 'abreviatura',
                'perfeccionamiento' => 'perfeccionamiento',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'firmas' => [
            'legacy' => 'rh_firmas',
            'pk' => 'idfirmas',
            'columnas' => [
                'nombfirma' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'fondos_tiempo' => [
            'legacy' => 'rh_fondotiempo',
            'pk' => 'idfondotiempo',
            'columnas' => [
                'fondotiempo' => 'fondo_tiempo',
            ],
        ],

        'grupos_escala' => [
            'legacy' => 'rh_gruposescala',
            'pk' => 'idgruposescala',
            'columnas' => [
                'nombgrupoescala' => 'nombre',
                'tarifa' => 'tarifa',
                'salario' => 'salario',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'medios_proteccion' => [
            'legacy' => 'rh_mediosproteccion',
            'pk' => 'idmediosproteccion',
            'columnas' => [
                'nombmediosproteccion' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'meses' => [
            'legacy' => 'rh_meses',
            'pk' => 'idmes',
            'columnas' => [
                'nombmes' => 'nombre',
                'codigo' => 'codigo',
            ],
        ],

        'municipios' => [
            'legacy' => 'rh_municipios',
            'pk' => 'idmunicipios',
            'columnas' => [
                'nombmunicipio' => 'nombre',
                'idprovincias' => 'id_provincia',
            ],
        ],

        'organismos' => [
            'legacy' => 'rh_organismos',
            'pk' => 'idorganismos',
            'columnas' => [
                'nomborganismo' => 'nombre',
                'abreviatura' => 'abreviatura',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'osdes' => [
            'legacy' => 'rh_osdes',
            'pk' => 'idosdes',
            'columnas' => [
                'codigo' => 'codigo',
                'nombosde' => 'nombre',
                'siglas' => 'siglas',
                'idorganismos' => 'id_organismo',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'perfiles_rh' => [
            'legacy' => 'rh_perfiles',
            'pk' => 'idperfil',
            'columnas' => [
                'nombperfil' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'provincias' => [
            'legacy' => 'rh_provincias',
            'pk' => 'idprovincias',
            'columnas' => [
                'nombprovincia' => 'nombre',
            ],
        ],

        'tipos_calificadores' => [
            'legacy' => 'rh_tipocalificadores',
            'pk' => 'idtipocalificadores',
            'columnas' => [
                'nombcalificador' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_causas_baja' => [
            'legacy' => 'rh_tipocausabaja',
            'pk' => 'idtipocausabaja',
            'columnas' => [
                'nombcausabaja' => 'nombre',
                'idtipocausalab' => 'id_tipo_causa_laboral',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_causas_laborales' => [
            'legacy' => 'rh_tipocausalab',
            'pk' => 'idtipocausalab',
            'columnas' => [
                'nombcausalab' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_causas_movimiento' => [
            'legacy' => 'rh_tipocausamov',
            'pk' => 'idtipocausamov',
            'columnas' => [
                'nombcausamov' => 'nombre',
                'idtipocausalab' => 'id_tipo_causa_laboral',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_clasificacion_laboral' => [
            'legacy' => 'rh_tipoclasflaboral',
            'pk' => 'idtipoclasflaboral',
            'columnas' => [
                'nombclasflaboral' => 'nombre',
                'designado' => 'designado',
                'cuadro' => 'cuadro',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_color_piel' => [
            'legacy' => 'rh_tipocolorpiel',
            'pk' => 'idcolorpiel',
            'columnas' => [
                'nombcolorpiel' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_contratos' => [
            'legacy' => 'rh_tipocontratos',
            'pk' => 'idtipocontratos',
            'columnas' => [
                'nombtipocontrato' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_deducciones' => [
            'legacy' => 'rh_tipodeducciones',
            'pk' => 'idtipodeducciones',
            'columnas' => [
                'tipodeducciones' => 'nombre',
                'descripcion' => 'descripcion',
                'clave' => 'clave',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_especialidad' => [
            'legacy' => 'rh_tipoespecialidad',
            'pk' => 'idtiposespecialidad',
            'columnas' => [
                'nombespecialidad' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_estado_civil' => [
            'legacy' => 'rh_tipoestadocivil',
            'pk' => 'idtipoestadocivil',
            'columnas' => [
                'nombestadocivil' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_grupo_horario' => [
            'legacy' => 'rh_tipogrupohorario',
            'pk' => 'idtipogrupohorario',
            'columnas' => [
                'grupohorario' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_incidencias' => [
            'legacy' => 'rh_tipoincidencias',
            'pk' => 'idtipoincidencias',
            'columnas' => [
                'nombincidencias' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_integracion_politica' => [
            'legacy' => 'rh_tipointpolitica',
            'pk' => 'idtipointpolitica',
            'columnas' => [
                'nombintpolitica' => 'nombre',
                'politica' => 'politica',
                'abreviatura' => 'abreviatura',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_medios_cargo' => [
            'legacy' => 'rh_tipomedioscargo',
            'pk' => 'idtipomedioscargo',
            'columnas' => [
                'idmediosproteccion' => 'id_medio_proteccion',
                'idcargos' => 'id_cargo',
            ],
        ],

        'tipos_medios_proteccion' => [
            'legacy' => 'rh_tipomediosproteccion',
            'pk' => 'idtipomediosproteccion',
            'columnas' => [
                'nombtipomediosproteccion' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_nivel_educacion' => [
            'legacy' => 'rh_tiponiveducacion',
            'pk' => 'idtiponiveducacion',
            'columnas' => [
                'nombniveducacion' => 'nombre',
                'abreviatura' => 'abreviatura',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_pagos_adicionales' => [
            'legacy' => 'rh_tipopagosadicionales',
            'pk' => 'idtipopagosadicionales',
            'columnas' => [
                'nombpagosadicionales' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_penalizaciones' => [
            'legacy' => 'rh_tipopenalizaciones',
            'pk' => 'idtipopenalizaciones',
            'columnas' => [
                'nombpenalizacion' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_plantillas' => [
            'legacy' => 'rh_tipoplantillas',
            'pk' => 'idtipoplantillas',
            'columnas' => [
                'nombtipoplantillas' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_sexo' => [
            'legacy' => 'rh_tiposexo',
            'pk' => 'idtiposexo',
            'columnas' => [
                'sexo' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_sistemas_pago' => [
            'legacy' => 'rh_tiposistemaspago',
            'pk' => 'idtiposistemapago',
            'columnas' => [
                'nombsistemapago' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_tallas' => [
            'legacy' => 'rh_tipotallas',
            'pk' => 'idtipotallas',
            'columnas' => [
                'nombtipotallas' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_tasas' => [
            'legacy' => 'rh_tipotasas',
            'pk' => 'idtasa',
            'columnas' => [
                'nombtasa' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_ubicacion_defensa' => [
            'legacy' => 'rh_tipoubicdefensa',
            'pk' => 'idtipoubicdefensa',
            'columnas' => [
                'nombubicdefensa' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'acciones_hotkeys' => [
            'legacy' => 'tec_hotkeyacciones',
            'pk' => 'idaccion',
            'columnas' => [
                'codigo' => 'codigo',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'arrastres' => [
            'legacy' => 'tec_naves',
            'pk' => 'idnave',
            'defaults' => [
                'activo' => true,
            ],
        ],

        'balances_electricos' => [
            'legacy' => 'tec_electbalance',
            'pk' => 'idelectbalance',
            'columnas' => [
                'idelectlocales' => 'id_local',
                'idelectequipos' => 'id_equipo',
                'cantidad' => 'consumo',
            ],
            'defaults' => [
                'fecha' => '1970-01-01',
            ],
        ],

        'clasificaciones_ordenes_taller' => [
            'legacy' => 'tec_tipoclasificacion',
            'pk' => 'idtipoclasificacion',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'colores' => [
            'legacy' => 'tec_colores',
            'pk' => 'idcolores',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'consecutivos' => [
            'legacy' => 'tec_consecutivos',
            'pk' => 'idconsecutivos',
            'defaults' => [
                'descripcion' => '',
            ],
        ],

        'control_lubricantes' => [
            'legacy' => 'tec_controllubricante',
            'pk' => 'idcontrollubricante',
            'columnas' => [
                'fconfeccion' => 'fecha_cambio',
                'litrosmotor' => 'cantidad_litros',
                'idtractivos' => 'id_tractivo',
            ],
            'defaults' => [
                'id_lubricante' => 0,
                'kilometraje' => 0,
            ],
        ],

        'destinos_agregados' => [
            'legacy' => 'tec_destagregados',
            'pk' => 'iddestagregados',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'diferenciales' => [
            'legacy' => 'tec_diferenciales',
            'pk' => 'iddiferenciales',
            'columnas' => [
                'codigo' => 'codigo',
                'idtractivos' => 'id_tractivo',
            ],
            'defaults' => [
                'estado' => 'disponible',
                'descripcion' => '',
            ],
        ],

        'equipos_electricos' => [
            'legacy' => 'tec_electequipos',
            'pk' => 'idelectequipos',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'equipos_garaje' => [
            'legacy' => 'tec_equiposgaraje',
            'pk' => 'idequiposgaraje',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'estados_componentes' => [
            'legacy' => 'tec_tipoestados',
            'pk' => 'idtipoestados',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'gastos_orden' => [
            'legacy' => 'tec_otgasto',
            'pk' => 'idotgasto',
            'columnas' => [
                'idordentaller' => 'id_orden_taller',
            ],
            'defaults' => [
                'nombre' => '',
                'cantidad' => 0,
            ],
        ],

        'grupos' => [
            'legacy' => 'tec_grupo',
            'pk' => 'idgrupo',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'historial_tractivos' => [
            'legacy' => 'tec_htractivos',
            'pk' => 'idhtractivos',
            'columnas' => [
                'idtractivo' => 'id_tractivo',
                'idgrupo' => 'id_grupo',
                'idcaja' => 'id_caja',
                'idmotor' => 'id_motor',
                'iddiferencial' => 'id_diferencial',
                'fcierre' => 'fecha_cierre',
                'kmhistorico' => 'km_historico',
                'kmmotor' => 'km_motor',
                'kmcaja' => 'km_caja',
                'kmdiferencial' => 'km_diferencial',
            ],
        ],

        'hotkeys' => [
            'legacy' => 'tec_hotkeys',
            'pk' => 'idhotkeys',
            'columnas' => [
                'idaccion' => 'id_accion',
                'tipo' => 'tipo',
            ],
            'defaults' => [
                'activo' => true,
                'combinacion' => '',
            ],
        ],

        'lecturas_medidores' => [
            'legacy' => 'tec_electlecturas',
            'pk' => 'ideleclectura',
            'columnas' => [
                'flectura' => 'fecha_lectura',
                'lecturainicial' => 'lectura_inicial',
                'lecturafinal' => 'lectura_final',
                'consumo' => 'consumo',
            ],
            'defaults' => [
                'id_medidor' => 0,
            ],
        ],

        'locales_electricos' => [
            'legacy' => 'tec_electlocales',
            'pk' => 'idelectlocales',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'lubricantes' => [
            'legacy' => 'tec_lubricantes',
            'pk' => 'idlubricantes',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'marcas' => [
            'legacy' => 'tec_marca',
            'pk' => 'idmarca',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'medidas_neumaticos' => [
            'legacy' => 'tec_neumaticosmedidas',
            'pk' => 'idneumaticosmedidas',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'medidores' => [
            'legacy' => 'tec_electdatos',
            'pk' => 'idelectdatos',
            'columnas' => [
                'codigo' => 'codigo',
                'rutafolio' => 'ruta_folio',
                'metro' => 'metro',
                'prepago' => 'prepago',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'modelos' => [
            'legacy' => 'tec_modelo',
            'pk' => 'idmodelo',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
                'id_marca' => 0,
            ],
        ],

        'motivos_baja_bateria' => [
            'legacy' => 'tec_motbajabat',
            'pk' => 'idmotbajabat',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'motivos_entrada_taller' => [
            'legacy' => 'tec_motentrada',
            'pk' => 'idmotentrada',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'motores' => [
            'legacy' => 'tec_motores',
            'pk' => 'idmotores',
            'columnas' => [
                'nroserie' => 'numero_serie',
                'idtractivos' => 'id_tractivo',
            ],
            'defaults' => [
                'estado' => 'disponible',
                'descripcion' => '',
            ],
        ],

        'movimientos_taller' => [
            'legacy' => 'tec_movimientostaller',
            'pk' => 'idmovimientotaller',
            'columnas' => [
                'idordentaller' => 'id_orden_taller',
                'idnave' => 'id_nave',
                'idvalla' => 'id_valla',
                'finicio' => 'fecha_inicio',
                'ffinal' => 'fecha_final',
            ],
        ],

        'motores_movimientos' => [
            'legacy' => 'tec_motmovimientos',
            'pk' => 'idmotmovimientos',
            'columnas' => [
                'motmovimientos' => 'observaciones',
            ],
            'defaults' => [
                'fecha_movimiento' => '1970-01-01',
                'tipo' => 'instalacion',
                'id_motor' => 0,
            ],
        ],

        'neumaticos' => [
            'legacy' => 'tec_neumaticos',
            'pk' => 'idneumaticos',
            'columnas' => [
                'idtractivos' => 'id_tractivo',
            ],
            'defaults' => [
                'estado' => 'activo',
                'folio' => '',
            ],
        ],

        'neumaticos_movimientos' => [
            'legacy' => 'tec_neumaticosmov',
            'pk' => 'idneumaticosmov',
            'columnas' => [
                'fmontado' => 'fecha_montaje',
                'fretirado' => 'fecha_retiro',
                'observacion' => 'observaciones',
                'idneumaticos' => 'id_neumatico',
                'idtractivos' => 'id_tractivo',
                'kminstalado' => 'km_instalado',
                'kmretirado' => 'km_retirado',
                'idposicion' => 'posicion',
            ],
        ],

        'ordenes_operaciones' => [
            'legacy' => 'tec_otoperaciones',
            'pk' => 'idotoperaciones',
            'columnas' => [
                'idordentaller' => 'id_orden_taller',
            ],
            'defaults' => [
                'costo_mano_obra' => 0,
                'costo_repuestos' => 0,
                'costo_total' => 0,
                'estado' => 'pendiente',
                'id_tipo_operacion' => 0,
                'id_subsistema' => 0,
                'descripcion' => '',
            ],
        ],

        'ordenes_taller' => [
            'legacy' => 'tec_ordentaller',
            'pk' => 'idordentaller',
            'columnas' => [
                'fentrada' => 'fecha_ingreso',
                'fsalida' => 'fecha_salida_estimada',
                'ordentaller' => 'numero',
                'idtractivos' => 'id_tractivo',
                'idtipomtto' => 'id_tipo_mantenimiento',
            ],
            'defaults' => [
                'estado' => 'abierta',
            ],
        ],

        'otros_agregados' => [
            'legacy' => 'tec_otrosagregados',
            'pk' => 'idotrosagregados',
            'columnas' => [
                'idmarca' => 'id_marca',
            ],
            'defaults' => [
                'descripcion' => '',
            ],
        ],

        'paises' => [
            'legacy' => 'tec_paises',
            'pk' => 'idpaises',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'posiciones_neumaticos' => [
            'legacy' => 'tec_neumaticosposicion',
            'pk' => 'idposicion',
            'columnas' => [
                'nombposicion' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'subsistemas' => [
            'legacy' => 'tec_tiposubsistemas',
            'pk' => 'idtiposubsistemas',
            'columnas' => [
                'tiposubsistemas' => 'nombre',
                'codigo' => 'codigo',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'talleres' => [
            'legacy' => 'tec_talleres',
            'pk' => 'idtalleres',
            'columnas' => [
                'taller' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_agregados' => [
            'legacy' => 'tec_tipoagregados',
            'pk' => 'idtipoagregados',
            'columnas' => [
                'codigo' => 'codigo',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_arrastres' => [
            'legacy' => 'tec_tipoarrastres',
            'pk' => 'idtipoarrastres',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_causas' => [
            'legacy' => 'tec_motbajaneum',
            'pk' => 'idmotbajaneum',
            'defaults' => [
                'tipo' => 'baja',
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_combustibles' => [
            'legacy' => 'tec_tipocombustibles',
            'pk' => 'idtipocombustibles',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_equipos' => [
            'legacy' => 'tec_tipoequipos',
            'pk' => 'idtipoequipos',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_lubricantes' => [
            'legacy' => 'tec_tipolubricantes',
            'pk' => 'idtipolubricantes',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_mantenimiento' => [
            'legacy' => 'tec_tipomtto',
            'pk' => 'idtipomtto',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_neumaticos' => [
            'legacy' => 'tec_tiponeumaticos',
            'pk' => 'idtiponeumaticos',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_operaciones' => [
            'legacy' => 'tec_tipooperaciones',
            'pk' => 'idtipooperaciones',
            'columnas' => [
                'codigo' => 'codigo',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_roturas' => [
            'legacy' => 'tec_neumaticostiporoturas',
            'pk' => 'idneumtiporoturas',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_sistemas' => [
            'legacy' => 'tec_tiposistemas',
            'pk' => 'idtiposistemas',
            'columnas' => [
                'tiposistemas' => 'nombre',
                'codigo' => 'codigo',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_suspension' => [
            'legacy' => 'tec_tiposuspension',
            'pk' => 'idtiposuspension',
            'columnas' => [
                'tiposuspension' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_tractivos' => [
            'legacy' => 'tec_tipotractivos',
            'pk' => 'idtipotractivos',
            'columnas' => [
                'idmarca' => 'id_marca',
                'idmodelo' => 'id_modelo',
                'idpaises' => 'id_pais',
                'fabricacion' => 'fabricacion',
                'bat_cant' => 'bat_cant',
                'bat_amp' => 'bat_amp',
                'dif_cant' => 'dif_cant',
                'dif_relacion' => 'dif_relacion',
                'dif_ancho' => 'dif_ancho',
                'neum_del_cant' => 'neum_del_cant',
                'neum_tras_cant' => 'neum_tras_cant',
                'neum_resp_cant' => 'neum_resp_cant',
                'ejes_cant' => 'ejes_cant',
                'eject_trac' => 'eject_trac',
                'idtipocombustibles' => 'id_tipo_combustible',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'vallas' => [
            'legacy' => 'tec_vallas',
            'pk' => 'idvalla',
            'columnas' => [
                'idnave' => 'id_nave',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'lineas_mantenimiento' => [
            'legacy' => 'tec_tipomttolineas',
            'pk' => null,
            'columnas' => [
                'idtipomtto' => 'id_tipo_mantenimiento',
                'km' => 'kilometraje',
            ],
        ],

        /*
         * ================================================================
         * MISC
         * ================================================================
         */

        'reportes_legacy' => [
            'legacy' => 'reportes',
            'pk' => 'idreporte',
            'columnas' => [
                'nombreporte' => 'nombre',
                'controlador' => 'controlador',
                'tipo' => 'tipo',
                'variable' => 'variable',
            ],
        ],

        /*
         * ================================================================
         * TALLER
         * ================================================================
         */

        'mantenimiento_ciclos' => [
            'legacy' => 'tec_ciclomantenimiento2000',
            'pk' => null,
            'columnas' => [
                'km' => 'km',
                'tipo' => 'tipo',
            ],
        ],

        'motivos_espera' => [
            'legacy' => 'tec_motespera',
            'pk' => 'idmotespera',
            'columnas' => [
                'motespera' => 'nombre',
            ],
        ],

        'cierres_cdt' => [
            'legacy' => 'tec_cierrecdt',
            'pk' => 'id',
            'columnas' => [
                'fecha' => 'fecha',
                'tiempogeneral' => 'tiempo_general',
                'tiempotaller' => 'tiempo_taller',
                'porciento' => 'porcentaje',
            ],
        ],

        'sub_tipos_roturas' => [
            'legacy' => 'tec_neumaticosroturas',
            'pk' => 'idneumroturas',
            'columnas' => [
                'idneumtiporoturas' => 'id_tipo_rotura',
                'neumaticosroturas' => 'nombre',
                'codigo' => 'codigo',
            ],
        ],

        'planes_mantenimiento' => [
            'legacy' => 'tec_otmtto',
            'pk' => null,
            'columnas' => [
                'idOrdenTaller' => 'id_orden_taller',
                'fMtto' => 'fecha_mantenimiento',
                'idTipoMantenimiento' => 'id_tipo_mantenimiento',
                'kmsMtto' => 'kms_mantenimiento',
                'kmsDisponible' => 'kms_disponible',
            ],
        ],
    ],
];
