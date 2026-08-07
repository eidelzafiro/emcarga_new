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
        'clientes', 'demandas',
        'prefacturas', 'tarifas_config_carga', 'tarifas_config_contenedor',
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

        // NOTA: clientes se migra con EtlService::migrarClientes() (dedicado):
        // campos legacy completos, idunidad→id_entidad, activo desde cancelado
        // y sufijo '-{idunidad}' en codigos duplicados. Esta entrada queda
        // solo para los conteos de --validar.
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
                'idtipomodelo' => 'codigo_tipo_modelo',
                'setx' => 'set_x',
                'sety' => 'set_y',
                'letra' => 'letra',
                'idunidad' => 'id_entidad',
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
                'tarmt' => 'tarifa_mt',
            ],
            'defaults' => [
                'version' => 'normal',
            ],
        ],

        // NOTA: com_tarconfigcarga y com_tarconfigcont ya migraron fusionadas
        // en la migración 2026_07_29_224241_create_configuraciones_tarifa_table
        // (una sola fila en configuraciones_tarifa). No van por ETL.

        'tipo_ingresos' => [
            'legacy' => 'com_tipoingresos',
            'pk' => 'idtipoingresos',
            'columnas' => [
                'tipoingresos' => 'nombre',
                'siglas' => 'siglas',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_cargas' => [
            'legacy' => 'com_tipocargas',
            'pk' => 'idtipocargas',
            'columnas' => [
                'tipocargas' => 'nombre',
            ],
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
            'columnas' => [
                'tipocatlugares' => 'nombre',
                'abreviatura' => 'abreviatura',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_estados' => [
            'legacy' => 'com_tipoestados',
            'pk' => 'idtipoestados',
            'columnas' => [
                'tipoestados' => 'nombre',
                'imgtipoestados' => 'imagen',
                'siglas' => 'siglas',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_indicadores' => [
            'legacy' => 'com_tipoindicadores',
            'pk' => 'idtipoindicadores',
            'columnas' => [
                'tipoindicadores' => 'nombre',
                'um' => 'unidad',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_modelo' => [
            'legacy' => 'com_tipomodelo',
            'pk' => 'idtipomod',
            'columnas' => [
                'idtipomodelo' => 'codigo',
                'modelo' => 'nombre',
                'idunidad' => 'id_entidad',
                'ancho' => 'ancho',
                'alto' => 'alto',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'tipos_servicios' => [
            'legacy' => 'com_tiposervicios',
            'pk' => 'idtiposervicios',
            'columnas' => [
                'tiposervicios' => 'nombre',
            ],
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

        // Tarjetas de combustible (legacy cont_tarjetas): campos originales
        // completos. id_cliente queda NULL (legacy relaciona con empleado/
        // tractivo). idunidad → id_entidad. estado queda 'activa' por default
        // (cancelado/inactiva se conservan como columnas originales).
        'tarjetas' => [
            'legacy' => 'cont_tarjetas',
            'pk' => 'idtarjeta',
            'columnas' => [
                'codtm' => 'numero',
                'saldoactualmon' => 'saldo_actual',
                'fcompra' => 'fcompra',
                'fvence' => 'fvence',
                'saldoinicialmon' => 'saldoinicialmon',
                'saldoiniciallts' => 'saldoiniciallts',
                'saldoactuallts' => 'saldoactuallts',
                'saldotransferenciamon' => 'saldotransferenciamon',
                'saldotransferencialts' => 'saldotransferencialts',
                'idmonedas' => 'idmonedas',
                'idtipocombustibles' => 'idtipocombustibles',
                'idempleado' => 'idempleado',
                'idtractivos' => 'idtractivos',
                'idchofer' => 'idchofer',
                'cancelado' => 'cancelado',
                'inactiva' => 'inactiva',
                'fmovimiento' => 'fmovimiento',
                'fcancelado' => 'fcancelado',
                'fcierre' => 'fcierre',
                'idunidad' => 'id_entidad',
            ],
            'defaults' => [
                'descripcion' => '',
            ],
            'cero_a_null' => ['id_entidad'],
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
            'columnas' => [
                'servicentros' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_conceptos' => [
            'legacy' => 'cont_tipoconceptos',
            'pk' => 'idtipoconcepto',
            'columnas' => [
                'tipoconcepto' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_documentos' => [
            'legacy' => 'cont_tipodocumentos',
            'pk' => 'idtipodoc',
            'columnas' => [
                'tipodocumentos' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_gastos' => [
            'legacy' => 'cont_tipogastos',
            'pk' => 'idtipogastos',
            'columnas' => [
                'tipogastos' => 'nombre',
            ],
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
                'codigo' => 'codigo',
                'abreviatura' => 'abreviatura',
                'nit' => 'nit',
                'direccion' => 'direccion',
                'idprovincias' => 'id_provincia',
                'idmunicipios' => 'id_municipio',
                'ctaunica' => 'cta_unica',
                'ctamn' => 'cta_mn',
                'ctame' => 'cta_me',
                'foliofact' => 'folio_fact',
                'agencia' => 'agencia',
                'notasfact' => 'notas_fact',
                'moradias' => 'mora_dias',
                'moraporciento' => 'mora_porciento',
                'clientefincimexmn' => 'cliente_fincimex_mn',
                'talonversat' => 'talon_versat',
                'idsistema' => 'id_sistema',
                'idcajera' => 'id_cajera',
                'passdias' => 'pass_dias',
                'passcanth' => 'pass_cant_h',
                'almacenaje' => 'almacenaje',
                'interruptos' => 'interruptos',
                'minutos' => 'minutos',
                'lugares' => 'lugares',
                'opercarga' => 'oper_carga',
                'disponible' => 'disponible',
                'tipoplanificacion' => 'tipo_planificacion',
                'tasasaforo' => 'tasas_aforo',
                'requisitos' => 'requisitos',
                'matriz' => 'matriz',
                'vidabateria' => 'vida_bateria',
                'vidaneumnuevo' => 'vida_neum_nuevo',
                'vidaneumrec' => 'vida_neum_rec',
                'vidaneumadmin' => 'vida_neum_admin',
                'desactivar_disp' => 'desactivar_disp',
                'alertas_mtto' => 'alertas_mtto',
                'foliofact' => 'folio_fact',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'cargos' => [
            'legacy' => 'rh_cargos',
            'pk' => 'idcargos',
            'columnas' => [
                'nombcargo' => 'nombre',
                'idcargos' => 'codigo',
                'idunidad' => 'id_entidad',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],

        'areas' => [
            'legacy' => 'rh_areas',
            'pk' => 'idareas',
            'columnas' => [
                'nombarea' => 'nombre',
                'idareas' => 'codigo',
                'idunidad' => 'id_entidad',
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
                'confnombre' => 'confecciona_nombre',
                'confcargo' => 'confecciona_cargo',
                'revnombre' => 'revisa_nombre',
                'revcargo' => 'revisa_cargo',
                'aprobnombre' => 'aprueba_nombre',
                'aprobcargo' => 'aprueba_cargo',
                'idunidad' => 'id_entidad',
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
                'dias' => 'dias',
                'dlaborables' => 'dias_laborables',
                'dlab2' => 'dias_laborables_sin_sabado',
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

        // NOTA: tipos_calificadores, tipos_causas_baja, tipos_causas_laborales,
        // tipos_causas_movimiento, tipos_especialidad, tipos_plantillas y
        // tipos_tallas fueron eliminadas intencionalmente del nuevo esquema
        // (migración 2026_07_31_010000_drop_unused_legacy_catalog_tables).

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
                'idtipodeducciones' => 'id_tipo_deducciones',
                'tsuma' => 'tsuma',
                'impsuma' => 'impsuma',
            ],
            'defaults' => [
                'activo' => true,
            ],
            'cero_a_null' => [
                'id_tipo_deducciones',
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
                'idareas' => 'area_id',
                'idtipopagosadicionales' => 'tipo_pago_adicional_id',
                'importe' => 'porcentaje',
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

        // Arrastres: el legacy NO tiene tabla de arrastres (tec_naves está vacía).
        // Los arrastres son tractivos idgrupo=8 (grupo ARRASTRES), unificados en
        // `tractivos`. Migración dedicada migrarArrastres() re-asocia tipo y entidad;
        // migrarAsociaciones() → arrastre_tractivo. No config de tabla genérica.
        'arrastres' => [
            'legacy' => 'tec_tipoarrastres',
            'pk' => 'idtipoarrastres',
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

        'cajas' => [
            'legacy' => 'tec_cajas',
            'pk' => 'idcajas',
            'columnas' => [
                'nroserie' => 'numero_serie',
                'idmarca' => 'marca',
                'idmodelo' => 'modelo',
                'idtractivos' => 'id_tractivo',
                'idunidad' => 'id_entidad',
            ],
            'defaults' => [
                'codigo' => null,
                'estado' => 'disponible',
            ],
        ],

        'clasificaciones_ordenes_taller' => [
            'legacy' => 'tec_tipoclasificacion',
            'pk' => 'idtipoclasificacion',
            'columnas' => [
                'tipoclasificacion' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'colores' => [
            'legacy' => 'tec_colores',
            'pk' => 'idcolores',
            'columnas' => [
                'colores' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'consecutivos' => [
            'legacy' => 'tec_consecutivos',
            'pk' => 'idconsecutivos',
            'columnas' => [
                'nombconsecutivo' => 'codigo',
                'valor' => 'ultimo',
                'idunidad' => 'id_entidad',
            ],
            'defaults' => [
                'descripcion' => '',  // se sobreescribe en migrarConsecutivos
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
            'columnas' => [
                'destagregados' => 'nombre',
            ],
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
            'columnas' => [
                'electequipos' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'equipos_garaje' => [
            'legacy' => 'tec_equiposgaraje',
            'pk' => 'idequiposgaraje',
            'columnas' => [
                'equiposgaraje' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'estados_componentes' => [
            'legacy' => 'tec_tipoestados',
            'pk' => 'idtipoestados',
            'columnas' => [
                'tipoestados' => 'nombre',
            ],
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
            'columnas' => [
                'grupo' => 'nombre',
            ],
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
                'idunidad' => 'id_entidad',
                'indice' => 'indice',
                'indiceac' => 'indice_acumulado',
                'plancomb' => 'plan_combustible',
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
            'columnas' => [
                'electlocales' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'lubricantes' => [
            'legacy' => 'tec_lubricantes',
            'pk' => 'idlubricantes',
            'columnas' => [
                'lubricantes' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'marcas' => [
            'legacy' => 'tec_marca',
            'pk' => 'idmarca',
            'columnas' => [
                'marca' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'medidas_neumaticos' => [
            'legacy' => 'tec_neumaticosmedidas',
            'pk' => 'idneumaticosmedidas',
            'columnas' => [
                'neumaticosmedidas' => 'nombre',
            ],
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
            'columnas' => [
                'modelo' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'motivos_baja_bateria' => [
            'legacy' => 'tec_motbajabat',
            'pk' => 'idmotbajabat',
            'columnas' => [
                'motbajabat' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'motivos_entrada_taller' => [
            'legacy' => 'tec_motentrada',
            'pk' => 'idmotentrada',
            'columnas' => [
                'motentrada' => 'nombre',
            ],
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
                'kmmtto' => 'kilometraje',
                'notas' => 'observaciones',
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
            'columnas' => [
                'paises' => 'nombre',
            ],
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
                'tipoagregados' => 'nombre',
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
            'columnas' => [
                'idmarca' => 'id_marca',
                'idmodelo' => 'id_modelo',
                'idpaises' => 'id_pais',
                'idtipoequipos' => 'id_tipo_equipo',
                'fabricacion' => 'fabricacion',
                'frecuencia' => 'frecuencia',
                'idtiposuspension' => 'id_tipo_suspension',
                'idneumaticosmedidasd' => 'id_medida_del',
                'idneumaticosmedidast' => 'id_medida_tra',
                'idneumaticosmedidasr' => 'id_medida_res',
                'neum_del_cant' => 'neum_del_cant',
                'neum_tras_cant' => 'neum_tras_cant',
                'neum_resp_cant' => 'neum_resp_cant',
                'ejes_cant' => 'ejes_cant',
                'eject_trac' => 'eject_trac',
                'dist_frente' => 'dist_frente',
                'dist_trasera' => 'dist_trasera',
                'largo_garganta' => 'largo_garganta',
                'altura_piso' => 'altura_piso',
                'altura_total' => 'altura_total',
                'largo_total' => 'largo_total',
                'ancho_total' => 'ancho_total',
                'idlubricantes' => 'id_lubricante',
                'idlubcubo' => 'id_lub_cubo',
                'idtipomtto' => 'id_tipo_mantenimiento',
                'idtipocombustibles' => 'id_tipo_combustible',
            ],
            'cero_a_null' => [
                'id_marca', 'id_modelo', 'id_pais', 'id_tipo_equipo', 'id_tipo_suspension',
                'id_medida_del', 'id_medida_tra', 'id_medida_res',
                'id_lubricante', 'id_lub_cubo', 'id_tipo_mantenimiento', 'id_tipo_combustible',
            ],
            'fk_validar' => [
                'id_marca' => 'marcas',
                'id_modelo' => 'modelos',
                'id_pais' => 'paises',
                'id_tipo_equipo' => 'tipos_equipos',
                'id_tipo_suspension' => 'tipos_suspension',
                'id_medida_del' => 'medidas_neumaticos',
                'id_medida_tra' => 'medidas_neumaticos',
                'id_medida_res' => 'medidas_neumaticos',
                'id_lubricante' => 'lubricantes',
                'id_lub_cubo' => 'lubricantes',
                'id_tipo_mantenimiento' => 'tipos_mantenimiento',
                'id_tipo_combustible' => 'tipos_combustibles',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_causas' => [
            'legacy' => 'tec_motbajaneum',
            'pk' => 'idmotbajaneum',
            'columnas' => [
                'motbajaneum' => 'nombre',
            ],
            'defaults' => [
                'tipo' => 'baja',
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_combustibles' => [
            'legacy' => 'tec_tipocombustibles',
            'pk' => 'idtipocombustibles',
            'columnas' => [
                'tipocombustibles' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_equipos' => [
            'legacy' => 'tec_tipoequipos',
            'pk' => 'idtipoequipos',
            'columnas' => [
                'tipoequipos' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_lubricantes' => [
            'legacy' => 'tec_tipolubricantes',
            'pk' => 'idtipolubricantes',
            'columnas' => [
                'tipolubricantes' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_mantenimiento' => [
            'legacy' => 'tec_tipomtto',
            'pk' => 'idtipomtto',
            'columnas' => [
                'tipomtto' => 'nombre',
                'kmsmax' => 'kms_max',
                'frecuencia' => 'frecuencia',
                'mttobase' => 'mtto_base',
                'holgura' => 'holgura',
                'mttos' => 'mttos',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_neumaticos' => [
            'legacy' => 'tec_tiponeumaticos',
            'pk' => 'idtiponeumaticos',
            'columnas' => [
                'tiponeumaticos' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'tipos_operaciones' => [
            'legacy' => 'tec_tipooperaciones',
            'pk' => 'idtipooperaciones',
            'columnas' => [
                'tipooperaciones' => 'nombre',
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
            'columnas' => [
                'tiporoturas' => 'nombre',
            ],
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

        // NOTA: tractivos se migra con EtlService::migrarTractivos() (dedicado):
        // excluye dados de baja, sufijo -entidad en duplicados, estado mapeado
        // desde idtipoestados. Esta entrada queda solo para --validar.
        'tractivos' => [
            'legacy' => 'tec_tractivos',
            'pk' => 'idtractivos',
        ],

        'tipos_tractivos' => [
            'legacy' => 'tec_tipotractivos',
            'pk' => 'idtipotractivos',
            'columnas' => [
                'idmarca' => 'id_marca',
                'idmodelo' => 'id_modelo',
                'idpaises' => 'id_pais',
                'idtipomtto' => 'id_tipo_mantenimiento',
                'fabricacion' => 'fabricacion',
                'bat_cant' => 'bat_cant',
                'bat_amp' => 'bat_amp',
                'dif_cant' => 'dif_cant',
                'dif_relacion' => 'dif_relacion',
                'dif_ancho' => 'dif_ancho',
                'idneumaticosmedidasd' => 'id_medida_del',
                'idneumaticosmedidast' => 'id_medida_tra',
                'idneumaticosmedidasr' => 'id_medida_res',
                'neum_del_cant' => 'neum_del_cant',
                'neum_tras_cant' => 'neum_tras_cant',
                'neum_resp_cant' => 'neum_resp_cant',
                'neum_tractivos' => 'neum_tractivos',
                'ejes_cant' => 'ejes_cant',
                'eject_trac' => 'eject_trac',
                'idtipocombustibles' => 'id_tipo_combustible',
                'idlubmotor' => 'id_lubricante_motor',
                'idlubruedas' => 'id_lubricante_cubo',
                'lubnorm' => 'lub_norma',
                'vlubcaja' => 'lub_caja',
                'dist_eje_inter' => 'dist_eje_inter',
                'dist_eje_tras' => 'dist_eje_tras',
                'cama_largo' => 'cama_largo',
                'cama_ancho' => 'cama_ancho',
                'cama_altura' => 'cama_altura',
            ],
            'cero_a_null' => [
                'id_marca', 'id_modelo', 'id_pais', 'id_medida_del', 'id_medida_tra', 'id_medida_res',
                'id_tipo_combustible', 'id_lubricante_motor', 'id_lubricante_cubo', 'id_tipo_mantenimiento',
            ],
            'int_or_null' => [
                'fabricacion',
            ],
            'fk_validar' => [
                'id_marca' => 'marcas',
                'id_modelo' => 'modelos',
                'id_pais' => 'paises',
                'id_medida_del' => 'medidas_neumaticos',
                'id_medida_tra' => 'medidas_neumaticos',
                'id_medida_res' => 'medidas_neumaticos',
                'id_tipo_combustible' => 'tipos_combustibles',
                'id_lubricante_motor' => 'lubricantes',
                'id_lubricante_cubo' => 'lubricantes',
                'id_tipo_mantenimiento' => 'tipos_mantenimiento',
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
                'tipomtto' => 'descripcion',
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
