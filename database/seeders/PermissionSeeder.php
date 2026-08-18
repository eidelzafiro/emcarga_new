<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Roles heredados del sistema legacy (rh_perfiles) y sus permisos
     * por módulo (modulo.accion). Al migrar cada módulo nuevo en la
     * Fase 5, sus permisos se agregan a esta lista y a su rol.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = [
            'dashboard.ver',

            'tractivos.ver', 'tractivos.crear', 'tractivos.editar', 'tractivos.eliminar',

            'motores.ver', 'motores.crear', 'motores.editar', 'motores.eliminar',

            'cajas.ver', 'cajas.crear', 'cajas.editar', 'cajas.eliminar',

            'diferenciales.ver', 'diferenciales.crear', 'diferenciales.editar', 'diferenciales.eliminar',

            'baterias.ver', 'baterias.crear', 'baterias.editar', 'baterias.eliminar',

            'neumaticos.ver', 'neumaticos.crear', 'neumaticos.editar', 'neumaticos.eliminar',

            'lubricantes.ver', 'lubricantes.crear', 'lubricantes.editar', 'lubricantes.eliminar',

            'control-lubricante.ver', 'control-lubricante.crear', 'control-lubricante.editar', 'control-lubricante.eliminar',

            'otros-agregados.ver', 'otros-agregados.crear', 'otros-agregados.editar', 'otros-agregados.eliminar',

            'energia.ver', 'energia.crear', 'energia.editar', 'energia.eliminar',

            'taller.ver', 'taller.crear', 'taller.editar', 'taller.eliminar',

            'clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.eliminar',

            'lugares.ver', 'lugares.crear', 'lugares.editar', 'lugares.eliminar',

            'distancias.ver', 'distancias.crear', 'distancias.editar', 'distancias.eliminar',

            'acuerdos.ver', 'acuerdos.crear', 'acuerdos.editar', 'acuerdos.eliminar',

            'solicitudes.ver', 'solicitudes.crear', 'solicitudes.editar', 'solicitudes.eliminar',

            'carta-porte.ver', 'carta-porte.crear', 'carta-porte.editar', 'carta-porte.eliminar',

            'facturas.ver', 'facturas.crear', 'facturas.editar', 'facturas.eliminar',
            'facturas.cancelar', 'facturas.refacturar', 'facturas.firmar', 'facturas.cobrar',

            'prefacturas.ver', 'prefacturas.crear', 'prefacturas.editar', 'prefacturas.eliminar',


            'bolsa.ver', 'bolsa.crear', 'bolsa.editar', 'bolsa.eliminar',

            'historial-movimientos.ver', 'historial-movimientos.crear', 'historial-movimientos.editar', 'historial-movimientos.eliminar',



            'meses.ver', 'meses.crear', 'meses.editar', 'meses.eliminar',

            'tipos-contratos.ver', 'tipos-contratos.crear', 'tipos-contratos.editar', 'tipos-contratos.eliminar',



            'tipos-tasas.ver', 'tipos-tasas.crear', 'tipos-tasas.editar', 'tipos-tasas.eliminar',

            'conciliaciones.ver', 'conciliaciones.crear', 'conciliaciones.editar', 'conciliaciones.eliminar',

            'tipos-conceptos.ver', 'tipos-conceptos.crear', 'tipos-conceptos.editar', 'tipos-conceptos.eliminar',

            'otros-gastos.ver', 'otros-gastos.crear', 'otros-gastos.editar', 'otros-gastos.eliminar',

            'combustible-cargas.ver', 'combustible-cargas.crear', 'combustible-cargas.editar', 'combustible-cargas.eliminar',

            'tarjetas.ver', 'tarjetas.crear', 'tarjetas.editar', 'tarjetas.eliminar',

            'combustible-descargas.ver', 'combustible-descargas.crear', 'combustible-descargas.editar', 'combustible-descargas.eliminar',

            'inventario.ver', 'inventario.crear', 'inventario.editar', 'inventario.eliminar',

            'vales.ver', 'vales.crear', 'vales.editar', 'vales.eliminar',

            // Catálogos y configuración (Fase 5.7)
            'marcas.ver', 'marcas.crear', 'marcas.editar', 'marcas.eliminar',
            'modelos.ver', 'modelos.crear', 'modelos.editar', 'modelos.eliminar',
            'naves.ver', 'naves.crear', 'naves.editar', 'naves.eliminar',
            'vallas.ver', 'vallas.crear', 'vallas.editar', 'vallas.eliminar',
            'destinos-agregados.ver', 'destinos-agregados.crear', 'destinos-agregados.editar', 'destinos-agregados.eliminar',
            'medidas-neumaticos.ver', 'medidas-neumaticos.crear', 'medidas-neumaticos.editar', 'medidas-neumaticos.eliminar',
            'consecutivos.ver', 'consecutivos.crear', 'consecutivos.editar', 'consecutivos.eliminar',
            'grupos.ver', 'grupos.crear', 'grupos.editar', 'grupos.eliminar',
            'colores.ver', 'colores.crear', 'colores.editar', 'colores.eliminar',
            'talleres.ver', 'talleres.crear', 'talleres.editar', 'talleres.eliminar',
            'posiciones-neumaticos.ver', 'posiciones-neumaticos.crear', 'posiciones-neumaticos.editar', 'posiciones-neumaticos.eliminar',
            'embalajes.ver', 'embalajes.crear', 'embalajes.editar', 'embalajes.eliminar',
            'navieras.ver', 'navieras.crear', 'navieras.editar', 'navieras.eliminar',
            'organismos.ver', 'organismos.crear', 'organismos.editar', 'organismos.eliminar',
            'categorias-cargo.ver', 'categorias-cargo.crear', 'categorias-cargo.editar', 'categorias-cargo.eliminar',
            'grupos-escala.ver', 'grupos-escala.crear', 'grupos-escala.editar', 'grupos-escala.eliminar',
            'cargos.ver', 'cargos.crear', 'cargos.editar', 'cargos.eliminar',
            'areas.ver', 'areas.crear', 'areas.editar', 'areas.eliminar',
            'entidades.ver', 'entidades.crear', 'entidades.editar', 'entidades.eliminar',

            'reportes.ver', 'reportes.generar',

            'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
            'usuarios.desbloquear', 'usuarios.restablecer',

            'perfiles.ver', 'perfiles.editar',

            'menus.ver', 'menus.crear', 'menus.editar', 'menus.eliminar', 'menus.admin',

            'catalogo.ver', 'catalogo.crear', 'catalogo.editar', 'catalogo.eliminar',

            'servicentros.ver', 'servicentros.crear', 'servicentros.editar', 'servicentros.eliminar',
            'tipos-documentos.ver', 'tipos-documentos.crear', 'tipos-documentos.editar', 'tipos-documentos.eliminar',
            'firmas-autorizadas.ver', 'firmas-autorizadas.crear', 'firmas-autorizadas.editar', 'firmas-autorizadas.eliminar',
            'reportes-costos.ver', 'reportes-costos.crear', 'reportes-costos.editar', 'reportes-costos.eliminar',
            'estados-tarjetas.ver', 'estados-tarjetas.crear', 'estados-tarjetas.editar', 'estados-tarjetas.eliminar',
            'combustibles-lubricantes.ver', 'combustibles-lubricantes.crear', 'combustibles-lubricantes.editar', 'combustibles-lubricantes.eliminar',
            'pagos.ver', 'pagos.crear', 'pagos.editar', 'pagos.eliminar',

            // RRHH - Tablas faltantes
            'osdes.ver', 'osdes.crear', 'osdes.editar', 'osdes.eliminar',
            'firmas.ver', 'firmas.crear', 'firmas.editar', 'firmas.eliminar',
            'fondos-tiempo.ver', 'fondos-tiempo.crear', 'fondos-tiempo.editar', 'fondos-tiempo.eliminar',
            'medios-proteccion.ver', 'medios-proteccion.crear', 'medios-proteccion.editar', 'medios-proteccion.eliminar',
            'tipos-medios-cargo.ver', 'tipos-medios-cargo.crear', 'tipos-medios-cargo.editar', 'tipos-medios-cargo.eliminar',
            'salarios.ver', 'salarios.crear', 'salarios.editar', 'salarios.eliminar',
            'salarios-administrativos.ver', 'salarios-administrativos.crear', 'salarios-administrativos.editar', 'salarios-administrativos.eliminar',
            'tipos-clasificacion-laboral.ver', 'tipos-clasificacion-laboral.crear', 'tipos-clasificacion-laboral.editar', 'tipos-clasificacion-laboral.eliminar',
            'tipos-medios-proteccion.ver', 'tipos-medios-proteccion.crear', 'tipos-medios-proteccion.editar', 'tipos-medios-proteccion.eliminar',

            // Comercial - Tablas faltantes
            'tipos-catalogo-lugares.ver', 'tipos-catalogo-lugares.crear', 'tipos-catalogo-lugares.editar', 'tipos-catalogo-lugares.eliminar',
            'configuraciones-modelo.ver', 'configuraciones-modelo.crear', 'configuraciones-modelo.editar', 'configuraciones-modelo.eliminar',
            'tipos-cargas-reporte.ver', 'tipos-cargas-reporte.crear', 'tipos-cargas-reporte.editar', 'tipos-cargas-reporte.eliminar',
            'turnos-comerciales.ver', 'turnos-comerciales.crear', 'turnos-comerciales.editar', 'turnos-comerciales.eliminar',
            'hojas-ruta.ver', 'hojas-ruta.crear', 'hojas-ruta.editar', 'hojas-ruta.eliminar',
            'alertas.ver', 'alertas.crear', 'alertas.editar', 'alertas.eliminar',
            'indicadores.ver', 'indicadores.crear', 'indicadores.editar', 'indicadores.eliminar',
            'demandas.ver', 'demandas.crear', 'demandas.editar', 'demandas.eliminar',
            'pizarra-tractivos.ver', 'pizarra-tractivos.crear', 'pizarra-tractivos.editar', 'pizarra-tractivos.eliminar',
            'tarifas.ver', 'tarifas.crear', 'tarifas.editar', 'tarifas.eliminar',
            'otros-ingresos-pre.ver', 'otros-ingresos-pre.crear', 'otros-ingresos-pre.editar', 'otros-ingresos-pre.eliminar',

            // Técnica - Tablas faltantes
            'arrastres.ver', 'arrastres.crear', 'arrastres.editar', 'arrastres.eliminar',
            'tipos-tractivos.ver', 'tipos-tractivos.crear', 'tipos-tractivos.editar', 'tipos-tractivos.eliminar',
            'balances-electricos.ver', 'balances-electricos.crear', 'balances-electricos.editar', 'balances-electricos.eliminar',
            'historial-tractivos.ver', 'historial-tractivos.crear', 'historial-tractivos.editar', 'historial-tractivos.eliminar',
            'motivos-baja-bateria.ver', 'motivos-baja-bateria.crear', 'motivos-baja-bateria.editar', 'motivos-baja-bateria.eliminar',
            'motivos-entrada-taller.ver', 'motivos-entrada-taller.crear', 'motivos-entrada-taller.editar', 'motivos-entrada-taller.eliminar',
            'clasificaciones-ordenes-taller.ver', 'clasificaciones-ordenes-taller.crear', 'clasificaciones-ordenes-taller.editar', 'clasificaciones-ordenes-taller.eliminar',
            'locales-electricos.ver', 'locales-electricos.crear', 'locales-electricos.editar', 'locales-electricos.eliminar',

            // ATM - Inventario/Tarjetero
            'tarjetero.ver', 'tarjetero.crear', 'tarjetero.editar', 'tarjetero.eliminar',

            'movimientos-inventario.ver', 'movimientos-inventario.crear', 'movimientos-inventario.editar', 'movimientos-inventario.eliminar',

            // RRHH - Tablas faltantes
            'centros-costos.ver', 'centros-costos.crear', 'centros-costos.editar', 'centros-costos.eliminar',
            'pagos-adicionales-cargo.ver', 'pagos-adicionales-cargo.crear', 'pagos-adicionales-cargo.editar', 'pagos-adicionales-cargo.eliminar',

            // Comercial - Tablas faltantes
            'contenedores.ver', 'contenedores.crear', 'contenedores.editar', 'contenedores.eliminar',
            'categorias-productos.ver', 'categorias-productos.crear', 'categorias-productos.editar', 'categorias-productos.eliminar',

            // Misc
            'elementos-gasto.ver', 'elementos-gasto.crear', 'elementos-gasto.editar', 'elementos-gasto.eliminar',
            'choferes.ver', 'choferes.crear', 'choferes.editar', 'choferes.eliminar',
            'empleados.ver', 'empleados.crear', 'empleados.editar', 'empleados.eliminar',
            'devoluciones.ver', 'devoluciones.crear', 'devoluciones.editar', 'devoluciones.eliminar',
            'descuentos-empleados.ver', 'descuentos-empleados.crear', 'descuentos-empleados.editar', 'descuentos-empleados.eliminar',

            'vacaciones.ver', 'vacaciones.crear', 'vacaciones.editar', 'vacaciones.eliminar',
            'estadisticas-explotacion.ver', 'estadisticas-explotacion.crear', 'estadisticas-explotacion.editar', 'estadisticas-explotacion.eliminar',
            'registro-ordenes-taller.ver', 'registro-ordenes-taller.crear', 'registro-ordenes-taller.editar', 'registro-ordenes-taller.eliminar',

            // Nómina (2026-08-18): incidencias, penalizaciones y dietas
            'incidencias.ver', 'incidencias.crear', 'incidencias.editar', 'incidencias.eliminar',
            'penalizaciones.ver', 'penalizaciones.crear', 'penalizaciones.editar', 'penalizaciones.eliminar',
            'dietas.ver', 'dietas.crear', 'dietas.editar', 'dietas.eliminar',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // Asignación de permisos por rol (perfiles legacy)
        $asignacion = [
            'SUPERADMIN' => $permisos,
            'DIRECTIVOS' => [
                'dashboard.ver',
                'bolsa.ver',
            ],
            'TECNICA' => [
                'dashboard.ver',
                'catalogo.ver',
                'tractivos.ver', 'tractivos.crear', 'tractivos.editar', 'tractivos.eliminar',
                'motores.ver', 'motores.crear', 'motores.editar', 'motores.eliminar',
                'cajas.ver', 'cajas.crear', 'cajas.editar', 'cajas.eliminar',
                'diferenciales.ver', 'diferenciales.crear', 'diferenciales.editar', 'diferenciales.eliminar',
                'baterias.ver', 'baterias.crear', 'baterias.editar', 'baterias.eliminar',
                'neumaticos.ver', 'neumaticos.crear', 'neumaticos.editar', 'neumaticos.eliminar',
            'lubricantes.ver', 'lubricantes.crear', 'lubricantes.editar', 'lubricantes.eliminar',

            'control-lubricante.ver', 'control-lubricante.crear', 'control-lubricante.editar', 'control-lubricante.eliminar',

                'otros-agregados.ver', 'otros-agregados.crear', 'otros-agregados.editar', 'otros-agregados.eliminar',
                'energia.ver', 'energia.crear', 'energia.editar', 'energia.eliminar',

                'reportes.ver', 'reportes.generar',
                'taller.ver', 'taller.crear', 'taller.editar', 'taller.eliminar',
                // Catálogos técnicos
                'marcas.ver', 'marcas.crear', 'marcas.editar', 'marcas.eliminar',
                'modelos.ver', 'modelos.crear', 'modelos.editar', 'modelos.eliminar',
                'naves.ver', 'naves.crear', 'naves.editar', 'naves.eliminar',
                'vallas.ver', 'vallas.crear', 'vallas.editar', 'vallas.eliminar',
                'destinos-agregados.ver', 'destinos-agregados.crear', 'destinos-agregados.editar', 'destinos-agregados.eliminar',
                'medidas-neumaticos.ver', 'medidas-neumaticos.crear', 'medidas-neumaticos.editar', 'medidas-neumaticos.eliminar',
                'consecutivos.ver', 'consecutivos.crear', 'consecutivos.editar', 'consecutivos.eliminar',
                'grupos.ver', 'grupos.crear', 'grupos.editar', 'grupos.eliminar',
                'talleres.ver', 'talleres.crear', 'talleres.editar', 'talleres.eliminar',
                'posiciones-neumaticos.ver', 'posiciones-neumaticos.crear', 'posiciones-neumaticos.editar', 'posiciones-neumaticos.eliminar',
                // Técnica - Tablas faltantes
                                 'arrastres.ver', 'arrastres.crear', 'arrastres.editar', 'arrastres.eliminar',
                'tipos-tractivos.ver', 'tipos-tractivos.crear', 'tipos-tractivos.editar', 'tipos-tractivos.eliminar',
                'balances-electricos.ver', 'balances-electricos.crear', 'balances-electricos.editar', 'balances-electricos.eliminar',

                'historial-tractivos.ver', 'historial-tractivos.crear', 'historial-tractivos.editar', 'historial-tractivos.eliminar',
                'motivos-baja-bateria.ver', 'motivos-baja-bateria.crear', 'motivos-baja-bateria.editar', 'motivos-baja-bateria.eliminar',
                'motivos-entrada-taller.ver', 'motivos-entrada-taller.crear', 'motivos-entrada-taller.editar', 'motivos-entrada-taller.eliminar',
                'clasificaciones-ordenes-taller.ver', 'clasificaciones-ordenes-taller.crear', 'clasificaciones-ordenes-taller.editar', 'clasificaciones-ordenes-taller.eliminar',
                'locales-electricos.ver', 'locales-electricos.crear', 'locales-electricos.editar', 'locales-electricos.eliminar',

                'tarjetero.ver', 'tarjetero.crear', 'tarjetero.editar', 'tarjetero.eliminar',

                'movimientos-inventario.ver', 'movimientos-inventario.crear', 'movimientos-inventario.editar', 'movimientos-inventario.eliminar',

                'choferes.ver', 'choferes.crear', 'choferes.editar', 'choferes.eliminar',
                'estadisticas-explotacion.ver', 'estadisticas-explotacion.crear', 'estadisticas-explotacion.editar', 'estadisticas-explotacion.eliminar',
                'registro-ordenes-taller.ver', 'registro-ordenes-taller.crear', 'registro-ordenes-taller.editar', 'registro-ordenes-taller.eliminar',
            ],
            'COMERCIAL' => [
                'dashboard.ver',
                'catalogo.ver',
                'clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.eliminar',
                'lugares.ver', 'lugares.crear', 'lugares.editar', 'lugares.eliminar',
                'distancias.ver', 'distancias.crear', 'distancias.editar', 'distancias.eliminar',
                'acuerdos.ver', 'acuerdos.crear', 'acuerdos.editar', 'acuerdos.eliminar',
                'solicitudes.ver', 'solicitudes.crear', 'solicitudes.editar', 'solicitudes.eliminar',
                'carta-porte.ver', 'carta-porte.crear', 'carta-porte.editar', 'carta-porte.eliminar',
                'facturas.ver', 'facturas.crear', 'facturas.editar', 'facturas.eliminar',
                'facturas.cancelar', 'facturas.refacturar', 'facturas.firmar', 'facturas.cobrar',
                'prefacturas.ver', 'prefacturas.crear', 'prefacturas.editar', 'prefacturas.eliminar',
                // Catálogos comerciales
                'embalajes.ver', 'embalajes.crear', 'embalajes.editar', 'embalajes.eliminar',

                'navieras.ver', 'navieras.crear', 'navieras.editar', 'navieras.eliminar',
                // Comercial - Tablas faltantes
                'tipos-catalogo-lugares.ver', 'tipos-catalogo-lugares.crear', 'tipos-catalogo-lugares.editar', 'tipos-catalogo-lugares.eliminar',
                'configuraciones-modelo.ver', 'configuraciones-modelo.crear', 'configuraciones-modelo.editar', 'configuraciones-modelo.eliminar',
                'tipos-cargas-reporte.ver', 'tipos-cargas-reporte.crear', 'tipos-cargas-reporte.editar', 'tipos-cargas-reporte.eliminar',
                'turnos-comerciales.ver', 'turnos-comerciales.crear', 'turnos-comerciales.editar', 'turnos-comerciales.eliminar',
                'hojas-ruta.ver', 'hojas-ruta.crear', 'hojas-ruta.editar', 'hojas-ruta.eliminar',
                'alertas.ver', 'alertas.crear', 'alertas.editar', 'alertas.eliminar',
                'indicadores.ver', 'indicadores.crear', 'indicadores.editar', 'indicadores.eliminar',
                'demandas.ver', 'demandas.crear', 'demandas.editar', 'demandas.eliminar',
                'pizarra-tractivos.ver', 'pizarra-tractivos.crear', 'pizarra-tractivos.editar', 'pizarra-tractivos.eliminar',
                'tarifas.ver', 'tarifas.crear', 'tarifas.editar', 'tarifas.eliminar',
                'otros-ingresos-pre.ver', 'otros-ingresos-pre.crear', 'otros-ingresos-pre.editar', 'otros-ingresos-pre.eliminar',
                // Comercial - Tablas faltantes parte 2

                'contenedores.ver', 'contenedores.crear', 'contenedores.editar', 'contenedores.eliminar',
                'categorias-productos.ver', 'categorias-productos.crear', 'categorias-productos.editar', 'categorias-productos.eliminar',

                'devoluciones.ver', 'devoluciones.crear', 'devoluciones.editar', 'devoluciones.eliminar',

            ],
            'RECHUM' => [
                'dashboard.ver',
                'catalogo.ver',
                'bolsa.ver', 'bolsa.crear', 'bolsa.editar', 'bolsa.eliminar',
                'historial-movimientos.ver', 'historial-movimientos.crear', 'historial-movimientos.editar', 'historial-movimientos.eliminar',
                'tipos-contratos.ver', 'tipos-contratos.crear', 'tipos-contratos.editar', 'tipos-contratos.eliminar',
                'tipos-tasas.ver', 'tipos-tasas.crear', 'tipos-tasas.editar', 'tipos-tasas.eliminar',
                // Catálogos RRHH
                'organismos.ver', 'organismos.crear', 'organismos.editar', 'organismos.eliminar',
                'categorias-cargo.ver', 'categorias-cargo.crear', 'categorias-cargo.editar', 'categorias-cargo.eliminar',
                'grupos-escala.ver', 'grupos-escala.crear', 'grupos-escala.editar', 'grupos-escala.eliminar',
                'cargos.ver', 'cargos.crear', 'cargos.editar', 'cargos.eliminar',
                'areas.ver', 'areas.crear', 'areas.editar', 'areas.eliminar',
                // RRHH - Tablas faltantes
                'osdes.ver', 'osdes.crear', 'osdes.editar', 'osdes.eliminar',
                'firmas.ver', 'firmas.crear', 'firmas.editar', 'firmas.eliminar',
                'fondos-tiempo.ver', 'fondos-tiempo.crear', 'fondos-tiempo.editar', 'fondos-tiempo.eliminar',
                'medios-proteccion.ver', 'medios-proteccion.crear', 'medios-proteccion.editar', 'medios-proteccion.eliminar',
                'tipos-medios-cargo.ver', 'tipos-medios-cargo.crear', 'tipos-medios-cargo.editar', 'tipos-medios-cargo.eliminar',
                'salarios.ver', 'salarios.crear', 'salarios.editar', 'salarios.eliminar',
                'salarios-administrativos.ver', 'salarios-administrativos.crear', 'salarios-administrativos.editar', 'salarios-administrativos.eliminar',
                'meses.ver', 'meses.crear', 'meses.editar', 'meses.eliminar',
                'tipos-clasificacion-laboral.ver', 'tipos-clasificacion-laboral.crear', 'tipos-clasificacion-laboral.editar', 'tipos-clasificacion-laboral.eliminar',
                'tipos-medios-proteccion.ver', 'tipos-medios-proteccion.crear', 'tipos-medios-proteccion.editar', 'tipos-medios-proteccion.eliminar',

                // RRHH - Tablas faltantes parte 2
                'centros-costos.ver', 'centros-costos.crear', 'centros-costos.editar', 'centros-costos.eliminar',
                'pagos-adicionales-cargo.ver', 'pagos-adicionales-cargo.crear', 'pagos-adicionales-cargo.editar', 'pagos-adicionales-cargo.eliminar',
                'empleados.ver', 'empleados.crear', 'empleados.editar', 'empleados.eliminar',
                'descuentos-empleados.ver', 'descuentos-empleados.crear', 'descuentos-empleados.editar', 'descuentos-empleados.eliminar',
                'vacaciones.ver', 'vacaciones.crear', 'vacaciones.editar', 'vacaciones.eliminar',
                // Nómina (2026-08-18)
                'incidencias.ver', 'incidencias.crear', 'incidencias.editar', 'incidencias.eliminar',
                'penalizaciones.ver', 'penalizaciones.crear', 'penalizaciones.editar', 'penalizaciones.eliminar',
                'dietas.ver', 'dietas.crear', 'dietas.editar', 'dietas.eliminar',
            ],
            'CONTABILIDAD' => [
                'dashboard.ver',
                'catalogo.ver',
                'conciliaciones.ver', 'conciliaciones.crear', 'conciliaciones.editar', 'conciliaciones.eliminar',
                'tipos-conceptos.ver', 'tipos-conceptos.crear', 'tipos-conceptos.editar', 'tipos-conceptos.eliminar',
                'otros-gastos.ver', 'otros-gastos.crear', 'otros-gastos.editar', 'otros-gastos.eliminar',
                'combustible-cargas.ver', 'combustible-cargas.crear', 'combustible-cargas.editar', 'combustible-cargas.eliminar',
                'tarjetas.ver', 'tarjetas.crear', 'tarjetas.editar', 'tarjetas.eliminar',
                'combustible-descargas.ver', 'combustible-descargas.crear', 'combustible-descargas.editar', 'combustible-descargas.eliminar',
                'inventario.ver', 'inventario.crear', 'inventario.editar', 'inventario.eliminar',
                'vales.ver', 'vales.crear', 'vales.editar', 'vales.eliminar',
                'servicentros.ver', 'servicentros.crear', 'servicentros.editar', 'servicentros.eliminar',
                'tipos-documentos.ver', 'tipos-documentos.crear', 'tipos-documentos.editar', 'tipos-documentos.eliminar',
                'firmas-autorizadas.ver', 'firmas-autorizadas.crear', 'firmas-autorizadas.editar', 'firmas-autorizadas.eliminar',
                'reportes-costos.ver', 'reportes-costos.crear', 'reportes-costos.editar', 'reportes-costos.eliminar',
                'estados-tarjetas.ver', 'estados-tarjetas.crear', 'estados-tarjetas.editar', 'estados-tarjetas.eliminar',

                'combustibles-lubricantes.ver', 'combustibles-lubricantes.crear', 'combustibles-lubricantes.editar', 'combustibles-lubricantes.eliminar',
                'pagos.ver', 'pagos.crear', 'pagos.editar', 'pagos.eliminar',
                'elementos-gasto.ver', 'elementos-gasto.crear', 'elementos-gasto.editar', 'elementos-gasto.eliminar',
            ],
            'OPERATIVOS' => [
                'dashboard.ver',
                'choferes.ver', 'choferes.crear', 'choferes.editar', 'choferes.eliminar',

                'vacaciones.ver', 'vacaciones.crear', 'vacaciones.editar', 'vacaciones.eliminar',
                'estadisticas-explotacion.ver', 'estadisticas-explotacion.crear', 'estadisticas-explotacion.editar', 'estadisticas-explotacion.eliminar',
            ],

            'CONFIGURACIONES' => [
                'dashboard.ver',

                'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
                'usuarios.desbloquear', 'usuarios.restablecer',

                'perfiles.ver', 'perfiles.editar',

                'menus.ver', 'menus.crear', 'menus.editar', 'menus.eliminar',
                'catalogo.ver', 'catalogo.crear', 'catalogo.editar', 'catalogo.eliminar',

                'entidades.ver', 'entidades.crear', 'entidades.editar', 'entidades.eliminar',

                'configuraciones-modelo.ver', 'configuraciones-modelo.crear', 'configuraciones-modelo.editar', 'configuraciones-modelo.eliminar',
            ],
        ];

        foreach ($asignacion as $nombreRol => $permisosRol) {
            $rol = Role::firstOrCreate(['name' => $nombreRol]);
            $rol->syncPermissions($permisosRol);
        }
    }
}
