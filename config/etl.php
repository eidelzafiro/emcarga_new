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
        5 => 'ADMIN',       // DIRECTIVOS → ADMIN (provisional)
        6 => 'ADMIN',       // ADMINISTRADOR
        7 => 'OPERATIVOS',  // OPERATIVOS
    ],

    'tablas' => [

        /*
         * ================================================================
         * SEGURIDAD
         * ================================================================
         */

        'bitacora' => [
            'legacy' => 'user_bitacora',
            'pk' => null,
            'columnas' => [
                'operacion' => 'accion',
                'foperacion' => 'fecha_accion',
                'iduser' => 'user_id',
                'tabla' => 'tabla',
            ],
        ],

        /*
         * ================================================================
         * COMERCIALES
         * ================================================================
         */

        'acuerdos' => [
            'legacy' => 'com_taracuerdos',
            'pk' => 'idtaracuerdos',
            'columnas' => [
                'idcliente' => 'id_cliente',
                'fletemtt' => 'tarifa_base',
            ],
            'defaults' => [
                'id_cliente' => null,
                'activo' => true,
                'codigo' => '',
                'descripcion' => '',
                'fecha_inicio' => '1970-01-01',
            ],
        ],

        'aforos' => [
            'legacy' => 'com_aforo',
            'pk' => 'idcartaporte',
            'columnas' => [
                'idcartaporte' => 'id_carta_porte',
                'fparte' => 'fecha_parte',
            ],
            'defaults' => [
                'flete_mt' => 0,
                'flete_mlc' => 0,
                'flete_demora' => 0,
                'otros_mt' => 0,
                'ingreso_mt' => 0,
                'descuento' => 0,
                'refactura' => false,
            ],
        ],

        'ajustes' => [
            'legacy' => 'com_ajustes',
            'pk' => 'idajustes',
            'columnas' => [
                'idcartaporte' => 'id_giro',
                'fajustes' => 'created_at',
            ],
            'defaults' => [
                'concepto' => '',
                'monto' => 0,
                'tipo' => 'descuento',
            ],
        ],

        'alertas' => [
            'legacy' => 'com_alertas',
            'pk' => 'idalertas',
            'columnas' => [
                'alerta' => 'mensaje',
                'femision' => 'fecha_emision',
                'fvence' => 'fecha_vencimiento',
                'iduser' => 'id_user',
                'idperfil' => 'id_perfil',
                'vencida' => 'vencida',
            ],
        ],

        'buques' => [
            'legacy' => 'com_buques',
            'pk' => 'idbuque',
            'columnas' => [
                'nombbuque' => 'nombre',
            ],
            'defaults' => [
                'codigo' => '',
                'activo' => true,
            ],
        ],

        'categorias_productos' => [
            'legacy' => 'com_productos_categoria',
            'pk' => 'idcatproducto',
            'columnas' => [
                'nombcatproducto' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'codigo' => '',
            ],
        ],

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
                'codigo' => '',
                'activo' => true,
            ],
        ],

        'facturas' => [
            'legacy' => 'com_rfactura',
            'pk' => 'idfactura',
            'columnas' => [
                'ffactura' => 'fecha_emision',
                'factura' => 'numero',
                'idcliente' => 'id_cliente',
                'idunidad' => 'id_unidad',
                'iduser' => 'id_user',
            ],
            'defaults' => [
                'flete_mt' => 0,
                'flete_mlc' => 0,
                'cancelada' => false,
                'refacturada' => false,
                'estado' => 'emitida',
            ],
        ],

        'giros' => [
            'legacy' => 'com_girado',
            'pk' => 'idcartaporte',
            'columnas' => [
                'nrocp' => 'numero_carta_porte',
                'femision' => 'fecha_parte',
                'idhojaruta' => 'id_solicitud',
                'idtractivos' => 'id_tractivo',
                'idcliente' => 'id_cliente',
                'idorigen' => 'id_lugar_origen',
                'iddestino' => 'id_lugar_destino',
                'idproducto1' => 'id_producto',
                'idtipocarga1' => 'id_tipo_carga',
                'iduser' => 'id_user',
            ],
            'defaults' => [
                'ingreso_mt' => 0,
                'flete_mt' => 0,
                'estado' => 'activo',
            ],
        ],

        'hojas_ruta' => [
            'legacy' => 'com_hojaruta',
            'pk' => 'idhojaruta',
            'columnas' => [
                'nrohr' => 'numero',
                'femision' => 'fecha_salida',
                'fcierre' => 'fecha_llegada_real',
                'idtractivos' => 'id_tractivo',
            ],
            'defaults' => [
                'id_solicitud' => 0,
                'id_cliente' => 0,
                'estado' => 'en_transito',
            ],
        ],

        'indicadores' => [
            'legacy' => 'com_indicadores',
            'pk' => 'idcartaporte',
            'clave' => ['id_carta_porte'],
            'columnas' => [
                'idcartaporte' => 'id_carta_porte',
            ],
            'defaults' => [
                'tn_pos_3' => 0,
                'tn_real_3' => 0,
                'km_carga_3' => 0,
                'km_vacio_3' => 0,
                'kms_total_3' => 0,
            ],
        ],

        'indicadores_planes' => [
            'legacy' => 'com_indicadores_plan',
            'pk' => 'idplan',
            'columnas' => [
                'idtipoindicadores' => 'id_tipo_indicador',
                'periodo' => 'periodo',
                'plan_periodo_actual' => 'plan_periodo',
                'ajuste_periodo_actual' => 'ajuste_periodo',
                'real_periodo_anterior' => 'real_periodo_anterior',
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
                'codigo' => '',
            ],
        ],

        'monedas' => [
            'legacy' => 'com_monedas',
            'pk' => 'idmonedas',
            'columnas' => [
                'monedas' => 'nombre',
            ],
            'defaults' => [
                'codigo' => '',
                'activo' => true,
            ],
        ],

        'movil_web' => [
            'legacy' => 'com_movilweb',
            'pk' => null,
            'columnas' => [
                'fparte' => 'fecha',
                'hojaruta' => 'hoja_ruta',
                'km' => 'km',
                'comb' => 'combustible',
            ],
        ],

        'navieras' => [
            'legacy' => 'com_navieras',
            'pk' => 'idnavieras',
            'columnas' => [
                'nombnavieras' => 'nombre',
            ],
            'defaults' => [
                'codigo' => '',
                'activo' => true,
            ],
        ],

        'otros_ingresos' => [
            'legacy' => 'com_otrosingresos',
            'pk' => 'idotrosingresos',
            'columnas' => [
                'impmn' => 'monto',
                'idcartaporte' => 'id_giro',
            ],
            'defaults' => [
                'concepto' => '',
                'fecha' => '1970-01-01',
            ],
        ],

        'otros_ingresos_pre' => [
            'legacy' => 'com_otrosingresos_pre',
            'pk' => 'idotrosingresos',
            'columnas' => [
                'idcartaporte' => 'id_carta_porte',
                'idtipoingresos' => 'id_tipo_ingreso',
                'cantidad' => 'cantidad',
            ],
        ],

        'pizarra_tractivos' => [
            'legacy' => 'com_pizarra_tractivos',
            'pk' => null,
            'columnas' => [
                'mes' => 'mes',
                'idtractivos' => 'id_tractivo',
            ],
            'defaults' => [
                'ano' => 0,
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
                'codigo' => '',
            ],
        ],

        'solicitudes' => [
            'legacy' => 'com_solicitudes',
            'pk' => 'idsolicitud',
            'columnas' => [
                'fsolicitud' => 'fecha_solicitud',
                'fplanificado' => 'fecha_requerida',
                'idsolicitud' => 'numero',
                'peso1' => 'toneladas_solicitadas',
                'idcliente' => 'id_cliente',
                'idorigen' => 'id_lugar_origen',
                'iddestino' => 'id_lugar_destino',
            ],
            'defaults' => [
                'toneladas_solicitadas' => 0,
                'estado' => 'pendiente',
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
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'tipos_cargas' => [
            'legacy' => 'com_tipocargas',
            'pk' => 'idtipocargas',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
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
                'codigo' => '',
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
                'codigo' => '',
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

        'combustible_cargas' => [
            'legacy' => 'cont_combcarga',
            'pk' => 'idcarga',
            'columnas' => [
                'folio' => 'numero',
                'saldocargado' => 'cantidad_litros',
                'idresponsable' => 'id_bolsa',
                'idtipocombustibles' => 'tipo_combustible',
                'notas' => 'observaciones',
                'fcarga' => 'fecha_carga',
            ],
            'defaults' => [
                'estado' => 'registrada',
                'precio_litro' => 0,
                'total' => 0,
            ],
        ],

        'combustible_descargas' => [
            'legacy' => 'cont_combdescarga',
            'pk' => 'iddescarga',
            'columnas' => [
                'idtarjeta' => 'id_tractivo',
                'fdescarga' => 'fecha_descarga',
            ],
            'defaults' => [
                'estado' => 'registrada',
                'cantidad_litros' => 0,
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

        'conciliaciones' => [
            'legacy' => 'cont_conciliaciones',
            'pk' => 'idconciliacion',
            'columnas' => [
                'fconciliacion' => 'fecha_conciliacion',
                'idcliente' => 'id_factura',
            ],
            'defaults' => [
                'tipo' => 'cliente',
                'estado' => 'pendiente',
                'numero' => '',
                'monto' => 0,
            ],
        ],

        'costos_taller' => [
            'legacy' => 'cont_costotaller',
            'pk' => 'idcostotaller',
            'columnas' => [
                'fcostotaller' => 'fecha',
                'idtractivo' => 'id_tractivo',
                'horastaller' => 'horas_taller',
            ],
        ],

        'detalles_carga_combustible' => [
            'legacy' => 'cont_combdetallecarga',
            'pk' => 'idmovimiento',
            'columnas' => [
                'fcarga' => 'fecha_movimiento',
                'folio' => 'comprobante',
                'saldomon' => 'importe_mn',
                'saldolts' => 'importe_mlc',
                'idcarga' => 'id_carga',
                'idtarjeta' => 'id_tractivo',
            ],
        ],

        'dietas' => [
            'legacy' => 'cont_dietas',
            'pk' => 'idcostodietas',
            'columnas' => [
                'fcostodietas' => 'fecha',
                'total' => 'monto',
                'idempleado' => 'id_bolsa',
                'idhojaruta' => 'id_hoja_ruta',
            ],
            'defaults' => [
                'tipo_dieta' => 'normal',
                'estado' => 'pendiente',
            ],
        ],

        'estados_tarjetas' => [
            'legacy' => 'cont_etarjetas',
            'pk' => 'idetarjeta',
            'columnas' => [
                'fmovimiento' => 'fecha_movimiento',
                'saldomon' => 'saldo_mn',
                'idcomprobante' => 'comprobante',
                'idtarjeta' => 'id_tarjeta',
                'identrega' => 'id_entrega',
                'idrecibe' => 'id_recibe',
            ],
        ],

        'firmas_autorizadas' => [
            'legacy' => 'cont_firmaaut',
            'pk' => 'idfirmaaut',
            'defaults' => [
                'activo' => true,
                'nombre' => '',
            ],
        ],

        'inventario' => [
            'legacy' => 'cont_inventario',
            'pk' => null,
            'columnas' => [
                'nombre' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'codigo' => '',
            ],
        ],

        'movimientos_tarjetas' => [
            'legacy' => 'cont_htarjetas',
            'pk' => 'idhtarjeta',
            'columnas' => [
                'ftrabajo' => 'fecha_movimiento',
                'saldoinicialmon' => 'saldo_anterior',
                'saldoactualmon' => 'saldo_posterior',
                'saldocargadomon' => 'monto',
                'idtarjeta' => 'id_tarjeta',
            ],
            'defaults' => [
                'tipo_movimiento' => 'carga',
                'saldo_anterior' => 0,
                'saldo_posterior' => 0,
            ],
        ],

        'otros_gastos' => [
            'legacy' => 'cont_ogastos',
            'pk' => 'idotrosgastos',
            'columnas' => [
                'fotrosgastos' => 'fecha',
                'importemn' => 'monto_mn',
                'folio' => 'concepto',
                'idtractivos' => 'id_tractivo',
            ],
            'defaults' => [
                'monto_mlc' => 0,
                'estado' => 'pendiente',
            ],
        ],

        'pagos' => [
            'legacy' => 'cont_pagos',
            'pk' => 'idpago',
            'columnas' => [
                'fpago' => 'fecha_pago',
            ],
            'defaults' => [
                'estado' => 'pendiente',
            ],
        ],

        'piezas' => [
            'legacy' => 'cont_piezas',
            'pk' => 'idpiezas',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'reembolsos' => [
            'legacy' => 'cont_dietasreembolso',
            'pk' => 'idreembolso',
            'columnas' => [
                'freembolso' => 'fecha',
                'importe' => 'monto',
            ],
            'defaults' => [
                'estado' => 'pendiente',
                'id_bolsa' => 0,
                'concepto' => '',
            ],
        ],

        'reportes_costos' => [
            'legacy' => 'cont_costoreporte',
            'pk' => 'idcostoreporte',
            'columnas' => [
                'fcostoreporte' => 'fecha_reporte',
                'utilidad' => 'utilidad_mlc',
                'costo' => 'costo_mlc',
                'salariotot' => 'salario_total',
                'ogastosmn' => 'otros_gastos_mn',
                'costotnkms' => 'costo_tn_kms',
                'idtractivos' => 'id_tractivo',
            ],
            'defaults' => [
                'estado' => 'borrador',
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
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'tipos_documentos' => [
            'legacy' => 'cont_tipodocumentos',
            'pk' => 'idtipodoc',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'tipos_gastos' => [
            'legacy' => 'cont_tipogastos',
            'pk' => 'idtipogastos',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        /*
         * ================================================================
         * RRHH
         * ================================================================
         */

        'areas' => [
            'legacy' => 'rh_areas',
            'pk' => 'idareas',
            'columnas' => [
                'nombarea' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'codigo' => '',
            ],
        ],

        'bolsa' => [
            'legacy' => 'rh_bolsa',
            'pk' => 'idbolsa',
            'columnas' => [
                'nombrecompleto' => 'nombre',
                'direccion' => 'direccion',
                'telefono' => 'telefono',
            ],
            'defaults' => [
                'activo' => true,
                'ci' => '',
                'apellidos' => '',
                'id_cargo' => 0,
                'id_entidad' => 0,
            ],
        ],

        'cargos' => [
            'legacy' => 'rh_cargos',
            'pk' => 'idcargos',
            'columnas' => [
                'nombcargo' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'codigo' => '',
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
                'codigo' => '',
                'activo' => true,
            ],
        ],

        'entidades' => [
            'legacy' => 'rh_entidades',
            'pk' => 'identidades',
            'columnas' => [
                'codigo' => 'codigo',
                'nombentidad' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
                'id_area' => 0,
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
                'codigo' => '',
            ],
        ],

        'historial_movimientos' => [
            'legacy' => 'rh_hmovimientos',
            'pk' => 'idmovimientos',
            'columnas' => [
                'tipomov' => 'tipo',
                'idmovimientos' => 'id_movimiento',
                'idbolsa' => 'id_bolsa',
                'iduser' => 'id_user',
            ],
            'defaults' => [
                'fecha' => '1970-01-01',
            ],
        ],

        'incidencias' => [
            'legacy' => 'rh_incidencias',
            'pk' => 'idincidencias',
            'columnas' => [
                'inicio' => 'fecha_inicio',
                'final' => 'fecha_fin',
                'idtipoincidencias' => 'tipo_incidencia',
            ],
            'defaults' => [
                'tipo_incidencia' => '',
                'estado' => 'pendiente',
                'id_bolsa' => 0,
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
            ],
            'defaults' => [
                'codigo' => '',
            ],
        ],

        'movimientos' => [
            'legacy' => 'rh_movimientos',
            'pk' => 'idmovimientos',
            'columnas' => [
                'idbolsa' => 'id_bolsa',
            ],
            'defaults' => [
                'tipo_movimiento' => '',
                'fecha_movimiento' => '1970-01-01',
                'id_entidad_origen' => 0,
                'id_entidad_destino' => 0,
                'id_cargo' => 0,
                'id_turno' => 0,
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
                'codigo' => '',
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

        'penalizaciones' => [
            'legacy' => 'rh_penalizaciones',
            'pk' => 'idpenalizaciones',
            'columnas' => [
                'fpenalizacion' => 'fecha',
                'importe' => 'monto',
            ],
            'defaults' => [
                'tipo_penalizacion' => '',
                'estado' => 'pendiente',
                'id_bolsa' => 0,
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

        'plantilla' => [
            'legacy' => 'rh_plantilla',
            'pk' => 'idplantilla',
            'columnas' => [
                'idcargos' => 'id_cargo',
            ],
            'defaults' => [
                'plazas' => 1,
                'cubiertas' => 0,
                'activo' => true,
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'provincias' => [
            'legacy' => 'rh_provincias',
            'pk' => 'idprovincias',
            'columnas' => [
                'nombprovincia' => 'nombre',
            ],
        ],

        'salarios' => [
            'legacy' => 'rh_salarios',
            'pk' => 'idsalario',
            'columnas' => [
                'idbolsa' => 'id_bolsa',
                'mes' => 'mes',
                'ano' => 'ano',
            ],
            'defaults' => [
                'salario_base' => 0,
                'estado' => 'borrador',
            ],
        ],

        'salarios_administrativos' => [
            'legacy' => 'rh_saladmin',
            'pk' => 'idsaladmin',
            'columnas' => [
                'fsaladmin' => 'fecha',
                'idmovimientos' => 'id_movimiento',
            ],
            'defaults' => [
                'estado' => 'borrador',
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
                'codigo' => '',
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
                'codigo' => '',
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
                'codigo' => '',
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
                'codigo' => '',
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
                'codigo' => '',
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
                'codigo' => '',
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
                'codigo' => '',
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

        'arrastre_tractivo' => [
            'legacy' => 'tec_asociaciones',
            'pk' => 'idasociaciones',
            'clave' => ['id_tractivo', 'id_arrastre'],
            'columnas' => [
                'idtractivos' => 'id_tractivo',
                'idarrastres' => 'id_arrastre',
            ],
        ],

        'arrastres' => [
            'legacy' => 'tec_naves',
            'pk' => 'idnave',
            'defaults' => [
                'codigo' => '',
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

        'baterias' => [
            'legacy' => 'tec_baterias',
            'pk' => 'idbaterias',
            'columnas' => [
                'finstalada' => 'fecha_instalacion',
                'fbaja' => 'fecha_retiro',
                'codigo' => 'folio',
                'idmarca' => 'marca',
                'idtractivos' => 'id_tractivo',
            ],
            'defaults' => [
                'estado' => 'activa',
            ],
        ],

        'baterias_movimientos' => [
            'legacy' => 'tec_bateriasmov',
            'pk' => 'idbateriasmov',
            'columnas' => [
                'idbaterias' => 'id_bateria',
                'idtractivos' => 'id_tractivo',
                'fmovimiento' => 'fecha_movimiento',
            ],
            'defaults' => [
                'tipo' => '',
            ],
        ],

        'cajas' => [
            'legacy' => 'tec_cajas',
            'pk' => 'idcajas',
            'columnas' => [
                'nroserie' => 'numero_serie',
                'idtractivos' => 'id_tractivo',
            ],
            'defaults' => [
                'estado' => 'disponible',
                'codigo' => '',
                'descripcion' => '',
            ],
        ],

        'clasificaciones_ordenes_taller' => [
            'legacy' => 'tec_tipoclasificacion',
            'pk' => 'idtipoclasificacion',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'colores' => [
            'legacy' => 'tec_colores',
            'pk' => 'idcolores',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'consecutivos' => [
            'legacy' => 'tec_consecutivos',
            'pk' => 'idconsecutivos',
            'defaults' => [
                'codigo' => '',
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
                'codigo' => '',
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
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'equipos_garaje' => [
            'legacy' => 'tec_equiposgaraje',
            'pk' => 'idequiposgaraje',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'estados_componentes' => [
            'legacy' => 'tec_tipoestados',
            'pk' => 'idtipoestados',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
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
                'codigo' => '',
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
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'lubricantes' => [
            'legacy' => 'tec_lubricantes',
            'pk' => 'idlubricantes',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'marcas' => [
            'legacy' => 'tec_marca',
            'pk' => 'idmarca',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'medidas_neumaticos' => [
            'legacy' => 'tec_neumaticosmedidas',
            'pk' => 'idneumaticosmedidas',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
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
                'codigo' => '',
                'nombre' => '',
                'id_marca' => 0,
            ],
        ],

        'motivos_baja_bateria' => [
            'legacy' => 'tec_motbajabat',
            'pk' => 'idmotbajabat',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'motivos_entrada_taller' => [
            'legacy' => 'tec_motentrada',
            'pk' => 'idmotentrada',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
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
                'codigo' => '',
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
                'codigo' => '',
                'descripcion' => '',
            ],
        ],

        'paises' => [
            'legacy' => 'tec_paises',
            'pk' => 'idpaises',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
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
                'codigo' => '',
            ],
        ],

        'subsistemas' => [
            'legacy' => 'tec_tiposubsistemas',
            'pk' => 'idtiposubsistemas',
            'columnas' => [
                'tiposubsistemas' => 'nombre',
            ],
            'defaults' => [
                'codigo' => '',
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
                'codigo' => '',
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
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'tipos_causas' => [
            'legacy' => 'tec_motbajaneum',
            'pk' => 'idmotbajaneum',
            'defaults' => [
                'tipo' => 'baja',
                'activo' => true,
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'tipos_combustibles' => [
            'legacy' => 'tec_tipocombustibles',
            'pk' => 'idtipocombustibles',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'tipos_equipos' => [
            'legacy' => 'tec_tipoequipos',
            'pk' => 'idtipoequipos',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'tipos_lubricantes' => [
            'legacy' => 'tec_tipolubricantes',
            'pk' => 'idtipolubricantes',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'tipos_mantenimiento' => [
            'legacy' => 'tec_tipomtto',
            'pk' => 'idtipomtto',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'tipos_neumaticos' => [
            'legacy' => 'tec_tiponeumaticos',
            'pk' => 'idtiponeumaticos',
            'defaults' => [
                'activo' => true,
                'codigo' => '',
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
                'codigo' => '',
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
                'codigo' => '',
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
                'codigo' => '',
                'nombre' => '',
            ],
        ],

        'tractivos' => [
            'legacy' => 'tec_tractivos',
            'pk' => 'idtractivos',
            'columnas' => [
                'codtractivo' => 'codigo',
                'chapa' => 'placa',
                'chassis' => 'numero_chasis',
                'falta' => 'fecha_alta',
                'kmsacum' => 'kilometraje_actual',
            ],
            'defaults' => [
                'descripcion' => '',
                'estado' => 'activo',
                'id_tipo_vehiculo' => 0,
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
                'codigo' => '',
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

        'elementos_gasto' => [
            'legacy' => 'cielem',
            'pk' => null,
            'columnas' => [
                'subeleme' => 'codigo',
                'nombelem' => 'nombre',
            ],
            'defaults' => [
                'activo' => true,
            ],
        ],
    ],
];
