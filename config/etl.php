<?php

/*
 * Mapeo ETL legacy (CodeIgniter) → nuevo esquema (Laravel). Fase 3.
 *
 * Cada entrada de 'tablas' define:
 *   - legacy:    nombre de la tabla origen (con prefijo de módulo)
 *   - columnas:  mapa columna_legacy => columna_nueva (solo las migradas)
 *   - defaults:  valores fijos para columnas nuevas sin origen
 *
 * Las tablas con transformación especial (usuarios) tienen handler
 * dedicado en EtlService. Los campos legacy sin columna destino se
 * descartan de forma consciente (esquema nuevo simplificado, D4).
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
            ],
        ],
    ],
];
