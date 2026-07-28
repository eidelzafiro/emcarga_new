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

            'otros-agregados.ver', 'otros-agregados.crear', 'otros-agregados.editar', 'otros-agregados.eliminar',

            'energia.ver', 'energia.crear', 'energia.editar', 'energia.eliminar',

            'taller.ver', 'taller.crear', 'taller.editar', 'taller.eliminar',

            'clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.eliminar',

            'lugares.ver', 'lugares.crear', 'lugares.editar', 'lugares.eliminar',

            'distancias.ver', 'distancias.crear', 'distancias.editar', 'distancias.eliminar',

            'acuerdos.ver', 'acuerdos.crear', 'acuerdos.editar', 'acuerdos.eliminar',

            'solicitudes.ver', 'solicitudes.crear', 'solicitudes.editar', 'solicitudes.eliminar',

            'giros.ver', 'giros.crear', 'giros.editar', 'giros.eliminar',

            'facturas.ver', 'facturas.crear', 'facturas.editar', 'facturas.eliminar',
            'facturas.cancelar', 'facturas.refacturar', 'facturas.firmar', 'facturas.cobrar',

            'prefacturas.ver', 'prefacturas.crear', 'prefacturas.editar', 'prefacturas.eliminar',

            'tipo-ingresos.ver', 'tipo-ingresos.crear', 'tipo-ingresos.editar', 'tipo-ingresos.eliminar',

            'bolsa.ver', 'bolsa.crear', 'bolsa.editar', 'bolsa.eliminar',

            'plantilla.ver', 'plantilla.crear', 'plantilla.editar', 'plantilla.eliminar',

            'historial-movimientos.ver', 'historial-movimientos.crear', 'historial-movimientos.editar', 'historial-movimientos.eliminar',

            'tipos-incidencias.ver', 'tipos-incidencias.crear', 'tipos-incidencias.editar', 'tipos-incidencias.eliminar',

            'tipos-penalizaciones.ver', 'tipos-penalizaciones.crear', 'tipos-penalizaciones.editar', 'tipos-penalizaciones.eliminar',

            'tipos-contratos.ver', 'tipos-contratos.crear', 'tipos-contratos.editar', 'tipos-contratos.eliminar',

            'tipos-sistemas-pago.ver', 'tipos-sistemas-pago.crear', 'tipos-sistemas-pago.editar', 'tipos-sistemas-pago.eliminar',

            'tipos-pagos-adicionales.ver', 'tipos-pagos-adicionales.crear', 'tipos-pagos-adicionales.editar', 'tipos-pagos-adicionales.eliminar',

            'tipos-tasas.ver', 'tipos-tasas.crear', 'tipos-tasas.editar', 'tipos-tasas.eliminar',

            'conciliaciones.ver', 'conciliaciones.crear', 'conciliaciones.editar', 'conciliaciones.eliminar',

            'tipos-conceptos.ver', 'tipos-conceptos.crear', 'tipos-conceptos.editar', 'tipos-conceptos.eliminar',

            'otros-gastos.ver', 'otros-gastos.crear', 'otros-gastos.editar', 'otros-gastos.eliminar',

            'combustible-cargas.ver', 'combustible-cargas.crear', 'combustible-cargas.editar', 'combustible-cargas.eliminar',

            'combustible-descargas.ver', 'combustible-descargas.crear', 'combustible-descargas.editar', 'combustible-descargas.eliminar',

            'inventario.ver', 'inventario.crear', 'inventario.editar', 'inventario.eliminar',

            'vales.ver', 'vales.crear', 'vales.editar', 'vales.eliminar',

            // Catálogos y configuración (Fase 5.7)
            'marcas.ver', 'marcas.crear', 'marcas.editar', 'marcas.eliminar',
            'modelos.ver', 'modelos.crear', 'modelos.editar', 'modelos.eliminar',
            'paises.ver', 'paises.crear', 'paises.editar', 'paises.eliminar',
            'naves.ver', 'naves.crear', 'naves.editar', 'naves.eliminar',
            'vallas.ver', 'vallas.crear', 'vallas.editar', 'vallas.eliminar',
            'destinos-agregados.ver', 'destinos-agregados.crear', 'destinos-agregados.editar', 'destinos-agregados.eliminar',
            'medidas-neumaticos.ver', 'medidas-neumaticos.crear', 'medidas-neumaticos.editar', 'medidas-neumaticos.eliminar',
            'tipos-combustibles.ver', 'tipos-combustibles.crear', 'tipos-combustibles.editar', 'tipos-combustibles.eliminar',
            'consecutivos.ver', 'consecutivos.crear', 'consecutivos.editar', 'consecutivos.eliminar',
            'tipos-servicios.ver', 'tipos-servicios.crear', 'tipos-servicios.editar', 'tipos-servicios.eliminar',
            'tipos-gastos.ver', 'tipos-gastos.crear', 'tipos-gastos.editar', 'tipos-gastos.eliminar',
            'grupos.ver', 'grupos.crear', 'grupos.editar', 'grupos.eliminar',
            'colores.ver', 'colores.crear', 'colores.editar', 'colores.eliminar',
            'talleres.ver', 'talleres.crear', 'talleres.editar', 'talleres.eliminar',
            'tipos-equipos.ver', 'tipos-equipos.crear', 'tipos-equipos.editar', 'tipos-equipos.eliminar',
            'tipos-agregados.ver', 'tipos-agregados.crear', 'tipos-agregados.editar', 'tipos-agregados.eliminar',
            'tipos-neumaticos.ver', 'tipos-neumaticos.crear', 'tipos-neumaticos.editar', 'tipos-neumaticos.eliminar',
            'posiciones-neumaticos.ver', 'posiciones-neumaticos.crear', 'posiciones-neumaticos.editar', 'posiciones-neumaticos.eliminar',
            'embalajes.ver', 'embalajes.crear', 'embalajes.editar', 'embalajes.eliminar',
            'navieras.ver', 'navieras.crear', 'navieras.editar', 'navieras.eliminar',
            'organismos.ver', 'organismos.crear', 'organismos.editar', 'organismos.eliminar',
            'categorias-cargo.ver', 'categorias-cargo.crear', 'categorias-cargo.editar', 'categorias-cargo.eliminar',
            'grupos-escala.ver', 'grupos-escala.crear', 'grupos-escala.editar', 'grupos-escala.eliminar',
            'entidades.ver', 'entidades.crear', 'entidades.editar', 'entidades.eliminar',

            'reportes.ver', 'reportes.generar',

            'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
            'usuarios.desbloquear', 'usuarios.restablecer',

            'perfiles.ver', 'perfiles.editar',

            'menus.ver', 'menus.crear', 'menus.editar', 'menus.eliminar',

            'catalogo.ver', 'catalogo.crear', 'catalogo.editar', 'catalogo.eliminar',

            'servicentros.ver', 'servicentros.crear', 'servicentros.editar', 'servicentros.eliminar',
            'tipos-documentos.ver', 'tipos-documentos.crear', 'tipos-documentos.editar', 'tipos-documentos.eliminar',
            'firmas-autorizadas.ver', 'firmas-autorizadas.crear', 'firmas-autorizadas.editar', 'firmas-autorizadas.eliminar',
            'reportes-costos.ver', 'reportes-costos.crear', 'reportes-costos.editar', 'reportes-costos.eliminar',
            'estados-tarjetas.ver', 'estados-tarjetas.crear', 'estados-tarjetas.editar', 'estados-tarjetas.eliminar',
            'combustibles-lubricantes.ver', 'combustibles-lubricantes.crear', 'combustibles-lubricantes.editar', 'combustibles-lubricantes.eliminar',
            'pagos.ver', 'pagos.crear', 'pagos.editar', 'pagos.eliminar',

            // RRHH - Tablas faltantes
            'provincias.ver', 'provincias.crear', 'provincias.editar', 'provincias.eliminar',
            'municipios.ver', 'municipios.crear', 'municipios.editar', 'municipios.eliminar',
            'osdes.ver', 'osdes.crear', 'osdes.editar', 'osdes.eliminar',
            'firmas.ver', 'firmas.crear', 'firmas.editar', 'firmas.eliminar',
            'meses.ver', 'meses.crear', 'meses.editar', 'meses.eliminar',
            'fondos-tiempo.ver', 'fondos-tiempo.crear', 'fondos-tiempo.editar', 'fondos-tiempo.eliminar',
            'medios-proteccion.ver', 'medios-proteccion.crear', 'medios-proteccion.editar', 'medios-proteccion.eliminar',
            'tipos-medios-cargo.ver', 'tipos-medios-cargo.crear', 'tipos-medios-cargo.editar', 'tipos-medios-cargo.eliminar',
            'salarios.ver', 'salarios.crear', 'salarios.editar', 'salarios.eliminar',
            'salarios-administrativos.ver', 'salarios-administrativos.crear', 'salarios-administrativos.editar', 'salarios-administrativos.eliminar',
            'tipos-calificadores.ver', 'tipos-calificadores.crear', 'tipos-calificadores.editar', 'tipos-calificadores.eliminar',
            'tipos-causas-laborales.ver', 'tipos-causas-laborales.crear', 'tipos-causas-laborales.editar', 'tipos-causas-laborales.eliminar',
            'tipos-causas-baja.ver', 'tipos-causas-baja.crear', 'tipos-causas-baja.editar', 'tipos-causas-baja.eliminar',
            'tipos-causas-movimiento.ver', 'tipos-causas-movimiento.crear', 'tipos-causas-movimiento.editar', 'tipos-causas-movimiento.eliminar',
            'tipos-clasificacion-laboral.ver', 'tipos-clasificacion-laboral.crear', 'tipos-clasificacion-laboral.editar', 'tipos-clasificacion-laboral.eliminar',
            'tipos-color-piel.ver', 'tipos-color-piel.crear', 'tipos-color-piel.editar', 'tipos-color-piel.eliminar',
            'tipos-deducciones.ver', 'tipos-deducciones.crear', 'tipos-deducciones.editar', 'tipos-deducciones.eliminar',
            'tipos-especialidad.ver', 'tipos-especialidad.crear', 'tipos-especialidad.editar', 'tipos-especialidad.eliminar',
            'tipos-estado-civil.ver', 'tipos-estado-civil.crear', 'tipos-estado-civil.editar', 'tipos-estado-civil.eliminar',
            'tipos-grupo-horario.ver', 'tipos-grupo-horario.crear', 'tipos-grupo-horario.editar', 'tipos-grupo-horario.eliminar',
            'tipos-integracion-politica.ver', 'tipos-integracion-politica.crear', 'tipos-integracion-politica.editar', 'tipos-integracion-politica.eliminar',
            'tipos-medios-proteccion.ver', 'tipos-medios-proteccion.crear', 'tipos-medios-proteccion.editar', 'tipos-medios-proteccion.eliminar',
            'tipos-nivel-educacion.ver', 'tipos-nivel-educacion.crear', 'tipos-nivel-educacion.editar', 'tipos-nivel-educacion.eliminar',
            'tipos-plantillas.ver', 'tipos-plantillas.crear', 'tipos-plantillas.editar', 'tipos-plantillas.eliminar',
            'tipos-sexo.ver', 'tipos-sexo.crear', 'tipos-sexo.editar', 'tipos-sexo.eliminar',
            'tipos-tallas.ver', 'tipos-tallas.crear', 'tipos-tallas.editar', 'tipos-tallas.eliminar',
            'tipos-ubicacion-defensa.ver', 'tipos-ubicacion-defensa.crear', 'tipos-ubicacion-defensa.editar', 'tipos-ubicacion-defensa.eliminar',

            // Comercial - Tablas faltantes
            'tipos-catalogo-lugares.ver', 'tipos-catalogo-lugares.crear', 'tipos-catalogo-lugares.editar', 'tipos-catalogo-lugares.eliminar',
            'tipos-modelo.ver', 'tipos-modelo.crear', 'tipos-modelo.editar', 'tipos-modelo.eliminar',
            'configuraciones-modelo.ver', 'configuraciones-modelo.crear', 'configuraciones-modelo.editar', 'configuraciones-modelo.eliminar',
            'tipos-estados.ver', 'tipos-estados.crear', 'tipos-estados.editar', 'tipos-estados.eliminar',
            'tipos-cargas-reporte.ver', 'tipos-cargas-reporte.crear', 'tipos-cargas-reporte.editar', 'tipos-cargas-reporte.eliminar',
            'clientes-seleccion.ver', 'clientes-seleccion.crear', 'clientes-seleccion.editar', 'clientes-seleccion.eliminar',
            'turnos-comerciales.ver', 'turnos-comerciales.crear', 'turnos-comerciales.editar', 'turnos-comerciales.eliminar',
            'movil-web.ver', 'movil-web.crear', 'movil-web.editar', 'movil-web.eliminar',
            'alertas.ver', 'alertas.crear', 'alertas.editar', 'alertas.eliminar',
            'indicadores.ver', 'indicadores.crear', 'indicadores.editar', 'indicadores.eliminar',
            'demandas.ver', 'demandas.crear', 'demandas.editar', 'demandas.eliminar',
            'pizarra-tractivos.ver', 'pizarra-tractivos.crear', 'pizarra-tractivos.editar', 'pizarra-tractivos.eliminar',
            'tarifas.ver', 'tarifas.crear', 'tarifas.editar', 'tarifas.eliminar',
            'otros-ingresos-pre.ver', 'otros-ingresos-pre.crear', 'otros-ingresos-pre.editar', 'otros-ingresos-pre.eliminar',

            // Técnica - Tablas faltantes
            'arrastres.ver', 'arrastres.crear', 'arrastres.editar', 'arrastres.eliminar',
            'balances-electricos.ver', 'balances-electricos.crear', 'balances-electricos.editar', 'balances-electricos.eliminar',
            'historial-tractivos.ver', 'historial-tractivos.crear', 'historial-tractivos.editar', 'historial-tractivos.eliminar',
            'motivos-baja-bateria.ver', 'motivos-baja-bateria.crear', 'motivos-baja-bateria.editar', 'motivos-baja-bateria.eliminar',
            'motivos-entrada-taller.ver', 'motivos-entrada-taller.crear', 'motivos-entrada-taller.editar', 'motivos-entrada-taller.eliminar',
            'tipos-roturas.ver', 'tipos-roturas.crear', 'tipos-roturas.editar', 'tipos-roturas.eliminar',
            'clasificaciones-ordenes-taller.ver', 'clasificaciones-ordenes-taller.crear', 'clasificaciones-ordenes-taller.editar', 'clasificaciones-ordenes-taller.eliminar',
            'tipos-sistemas.ver', 'tipos-sistemas.crear', 'tipos-sistemas.editar', 'tipos-sistemas.eliminar',
            'tipos-suspension.ver', 'tipos-suspension.crear', 'tipos-suspension.editar', 'tipos-suspension.eliminar',
            'locales-electricos.ver', 'locales-electricos.crear', 'locales-electricos.editar', 'locales-electricos.eliminar',

            // ATM - Inventario/Tarjetero
            'tarjetero.ver', 'tarjetero.crear', 'tarjetero.editar', 'tarjetero.eliminar',

            'movimientos-inventario.ver', 'movimientos-inventario.crear', 'movimientos-inventario.editar', 'movimientos-inventario.eliminar',


            // RRHH - Tablas faltantes
            'centros-costos.ver', 'centros-costos.crear', 'centros-costos.editar', 'centros-costos.eliminar',
            'tipos-articulos-bolsa.ver', 'tipos-articulos-bolsa.crear', 'tipos-articulos-bolsa.editar', 'tipos-articulos-bolsa.eliminar',

            'tipos-jefe-grupo.ver', 'tipos-jefe-grupo.crear', 'tipos-jefe-grupo.editar', 'tipos-jefe-grupo.eliminar',
            'pagos-adicionales-cargo.ver', 'pagos-adicionales-cargo.crear', 'pagos-adicionales-cargo.editar', 'pagos-adicionales-cargo.eliminar',


            // Comercial - Tablas faltantes
            'contenedores.ver', 'contenedores.crear', 'contenedores.editar', 'contenedores.eliminar',
            'categorias-productos.ver', 'categorias-productos.crear', 'categorias-productos.editar', 'categorias-productos.eliminar',

            // Misc
            'tipos-aceites.ver', 'tipos-aceites.crear', 'tipos-aceites.editar', 'tipos-aceites.eliminar',
            'tipos-entidad.ver', 'tipos-entidad.crear', 'tipos-entidad.editar', 'tipos-entidad.eliminar',
            'elementos-gasto.ver', 'elementos-gasto.crear', 'elementos-gasto.editar', 'elementos-gasto.eliminar',
            'choferes.ver', 'choferes.crear', 'choferes.editar', 'choferes.eliminar',
            'empleados.ver', 'empleados.crear', 'empleados.editar', 'empleados.eliminar',
            'devoluciones.ver', 'devoluciones.crear', 'devoluciones.editar', 'devoluciones.eliminar',
            'descuentos-empleados.ver', 'descuentos-empleados.crear', 'descuentos-empleados.editar', 'descuentos-empleados.eliminar',

            'vacaciones.ver', 'vacaciones.crear', 'vacaciones.editar', 'vacaciones.eliminar',
            'estadisticas-explotacion.ver', 'estadisticas-explotacion.crear', 'estadisticas-explotacion.editar', 'estadisticas-explotacion.eliminar',
            'registro-ordenes-taller.ver', 'registro-ordenes-taller.crear', 'registro-ordenes-taller.editar', 'registro-ordenes-taller.eliminar',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // Asignación de permisos por rol (perfiles legacy)
        $asignacion = [
            'SUPERADMIN' => $permisos,
            'TECNICA' => [
                'dashboard.ver',
                'tractivos.ver', 'tractivos.crear', 'tractivos.editar', 'tractivos.eliminar',
                'motores.ver', 'motores.crear', 'motores.editar', 'motores.eliminar',
                'cajas.ver', 'cajas.crear', 'cajas.editar', 'cajas.eliminar',
                'diferenciales.ver', 'diferenciales.crear', 'diferenciales.editar', 'diferenciales.eliminar',
                'baterias.ver', 'baterias.crear', 'baterias.editar', 'baterias.eliminar',
                'neumaticos.ver', 'neumaticos.crear', 'neumaticos.editar', 'neumaticos.eliminar',
                'lubricantes.ver', 'lubricantes.crear', 'lubricantes.editar', 'lubricantes.eliminar',
                'otros-agregados.ver', 'otros-agregados.crear', 'otros-agregados.editar', 'otros-agregados.eliminar',
                'energia.ver', 'energia.crear', 'energia.editar', 'energia.eliminar',

                'reportes.ver', 'reportes.generar',
                'taller.ver', 'taller.crear', 'taller.editar', 'taller.eliminar',
                // Catálogos técnicos
                'marcas.ver', 'marcas.crear', 'marcas.editar', 'marcas.eliminar',
                'modelos.ver', 'modelos.crear', 'modelos.editar', 'modelos.eliminar',
                'paises.ver', 'paises.crear', 'paises.editar', 'paises.eliminar',
                'naves.ver', 'naves.crear', 'naves.editar', 'naves.eliminar',
                'vallas.ver', 'vallas.crear', 'vallas.editar', 'vallas.eliminar',
                'destinos-agregados.ver', 'destinos-agregados.crear', 'destinos-agregados.editar', 'destinos-agregados.eliminar',
                'medidas-neumaticos.ver', 'medidas-neumaticos.crear', 'medidas-neumaticos.editar', 'medidas-neumaticos.eliminar',
                'tipos-combustibles.ver', 'tipos-combustibles.crear', 'tipos-combustibles.editar', 'tipos-combustibles.eliminar',
                'consecutivos.ver', 'consecutivos.crear', 'consecutivos.editar', 'consecutivos.eliminar',
                'tipos-gastos.ver', 'tipos-gastos.crear', 'tipos-gastos.editar', 'tipos-gastos.eliminar',
                'grupos.ver', 'grupos.crear', 'grupos.editar', 'grupos.eliminar',
                'colores.ver', 'colores.crear', 'colores.editar', 'colores.eliminar',
                'talleres.ver', 'talleres.crear', 'talleres.editar', 'talleres.eliminar',
                'tipos-equipos.ver', 'tipos-equipos.crear', 'tipos-equipos.editar', 'tipos-equipos.eliminar',
                'tipos-agregados.ver', 'tipos-agregados.crear', 'tipos-agregados.editar', 'tipos-agregados.eliminar',
                'tipos-neumaticos.ver', 'tipos-neumaticos.crear', 'tipos-neumaticos.editar', 'tipos-neumaticos.eliminar',
                'posiciones-neumaticos.ver', 'posiciones-neumaticos.crear', 'posiciones-neumaticos.editar', 'posiciones-neumaticos.eliminar',
                // Técnica - Tablas faltantes
                'arrastres.ver', 'arrastres.crear', 'arrastres.editar', 'arrastres.eliminar',
                'balances-electricos.ver', 'balances-electricos.crear', 'balances-electricos.editar', 'balances-electricos.eliminar',

                'historial-tractivos.ver', 'historial-tractivos.crear', 'historial-tractivos.editar', 'historial-tractivos.eliminar',
                'motivos-baja-bateria.ver', 'motivos-baja-bateria.crear', 'motivos-baja-bateria.editar', 'motivos-baja-bateria.eliminar',
                'motivos-entrada-taller.ver', 'motivos-entrada-taller.crear', 'motivos-entrada-taller.editar', 'motivos-entrada-taller.eliminar',
                'tipos-roturas.ver', 'tipos-roturas.crear', 'tipos-roturas.editar', 'tipos-roturas.eliminar',
                'clasificaciones-ordenes-taller.ver', 'clasificaciones-ordenes-taller.crear', 'clasificaciones-ordenes-taller.editar', 'clasificaciones-ordenes-taller.eliminar',
                'tipos-sistemas.ver', 'tipos-sistemas.crear', 'tipos-sistemas.editar', 'tipos-sistemas.eliminar',
                'tipos-suspension.ver', 'tipos-suspension.crear', 'tipos-suspension.editar', 'tipos-suspension.eliminar',
                'locales-electricos.ver', 'locales-electricos.crear', 'locales-electricos.editar', 'locales-electricos.eliminar',

                'tarjetero.ver', 'tarjetero.crear', 'tarjetero.editar', 'tarjetero.eliminar',

                'movimientos-inventario.ver', 'movimientos-inventario.crear', 'movimientos-inventario.editar', 'movimientos-inventario.eliminar',

                'tipos-aceites.ver', 'tipos-aceites.crear', 'tipos-aceites.editar', 'tipos-aceites.eliminar',
                'choferes.ver', 'choferes.crear', 'choferes.editar', 'choferes.eliminar',
                'estadisticas-explotacion.ver', 'estadisticas-explotacion.crear', 'estadisticas-explotacion.editar', 'estadisticas-explotacion.eliminar',
                'registro-ordenes-taller.ver', 'registro-ordenes-taller.crear', 'registro-ordenes-taller.editar', 'registro-ordenes-taller.eliminar',
            ],
            'COMERCIAL' => [
                'dashboard.ver',
                'clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.eliminar',
                'lugares.ver', 'lugares.crear', 'lugares.editar', 'lugares.eliminar',
                'distancias.ver', 'distancias.crear', 'distancias.editar', 'distancias.eliminar',
                'acuerdos.ver', 'acuerdos.crear', 'acuerdos.editar', 'acuerdos.eliminar',
                'solicitudes.ver', 'solicitudes.crear', 'solicitudes.editar', 'solicitudes.eliminar',
                'giros.ver', 'giros.crear', 'giros.editar', 'giros.eliminar',
                'facturas.ver', 'facturas.crear', 'facturas.editar', 'facturas.eliminar',
                'facturas.cancelar', 'facturas.refacturar', 'facturas.firmar', 'facturas.cobrar',
                'prefacturas.ver', 'prefacturas.crear', 'prefacturas.editar', 'prefacturas.eliminar',
                'tipo-ingresos.ver', 'tipo-ingresos.crear', 'tipo-ingresos.editar', 'tipo-ingresos.eliminar',
                // Catálogos comerciales
                'tipos-servicios.ver', 'tipos-servicios.crear', 'tipos-servicios.editar', 'tipos-servicios.eliminar',
                'embalajes.ver', 'embalajes.crear', 'embalajes.editar', 'embalajes.eliminar',

                'navieras.ver', 'navieras.crear', 'navieras.editar', 'navieras.eliminar',
                // Comercial - Tablas faltantes
                'tipos-catalogo-lugares.ver', 'tipos-catalogo-lugares.crear', 'tipos-catalogo-lugares.editar', 'tipos-catalogo-lugares.eliminar',
                'tipos-modelo.ver', 'tipos-modelo.crear', 'tipos-modelo.editar', 'tipos-modelo.eliminar',
                'configuraciones-modelo.ver', 'configuraciones-modelo.crear', 'configuraciones-modelo.editar', 'configuraciones-modelo.eliminar',
                'tipos-estados.ver', 'tipos-estados.crear', 'tipos-estados.editar', 'tipos-estados.eliminar',
                'tipos-cargas-reporte.ver', 'tipos-cargas-reporte.crear', 'tipos-cargas-reporte.editar', 'tipos-cargas-reporte.eliminar',
                'clientes-seleccion.ver', 'clientes-seleccion.crear', 'clientes-seleccion.editar', 'clientes-seleccion.eliminar',
                'turnos-comerciales.ver', 'turnos-comerciales.crear', 'turnos-comerciales.editar', 'turnos-comerciales.eliminar',
                'movil-web.ver', 'movil-web.crear', 'movil-web.editar', 'movil-web.eliminar',
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
                'bolsa.ver', 'bolsa.crear', 'bolsa.editar', 'bolsa.eliminar',
                'plantilla.ver', 'plantilla.crear', 'plantilla.editar', 'plantilla.eliminar',
                'historial-movimientos.ver', 'historial-movimientos.crear', 'historial-movimientos.editar', 'historial-movimientos.eliminar',
                'tipos-incidencias.ver', 'tipos-incidencias.crear', 'tipos-incidencias.editar', 'tipos-incidencias.eliminar',
                'tipos-penalizaciones.ver', 'tipos-penalizaciones.crear', 'tipos-penalizaciones.editar', 'tipos-penalizaciones.eliminar',
                'tipos-contratos.ver', 'tipos-contratos.crear', 'tipos-contratos.editar', 'tipos-contratos.eliminar',
                'tipos-sistemas-pago.ver', 'tipos-sistemas-pago.crear', 'tipos-sistemas-pago.editar', 'tipos-sistemas-pago.eliminar',
                'tipos-pagos-adicionales.ver', 'tipos-pagos-adicionales.crear', 'tipos-pagos-adicionales.editar', 'tipos-pagos-adicionales.eliminar',
                'tipos-tasas.ver', 'tipos-tasas.crear', 'tipos-tasas.editar', 'tipos-tasas.eliminar',
                // Catálogos RRHH
                'organismos.ver', 'organismos.crear', 'organismos.editar', 'organismos.eliminar',
                'categorias-cargo.ver', 'categorias-cargo.crear', 'categorias-cargo.editar', 'categorias-cargo.eliminar',
                'grupos-escala.ver', 'grupos-escala.crear', 'grupos-escala.editar', 'grupos-escala.eliminar',
                // RRHH - Tablas faltantes
                'provincias.ver', 'provincias.crear', 'provincias.editar', 'provincias.eliminar',
                'municipios.ver', 'municipios.crear', 'municipios.editar', 'municipios.eliminar',
                'osdes.ver', 'osdes.crear', 'osdes.editar', 'osdes.eliminar',
                'firmas.ver', 'firmas.crear', 'firmas.editar', 'firmas.eliminar',
                'meses.ver', 'meses.crear', 'meses.editar', 'meses.eliminar',
                'fondos-tiempo.ver', 'fondos-tiempo.crear', 'fondos-tiempo.editar', 'fondos-tiempo.eliminar',
                'medios-proteccion.ver', 'medios-proteccion.crear', 'medios-proteccion.editar', 'medios-proteccion.eliminar',
                'tipos-medios-cargo.ver', 'tipos-medios-cargo.crear', 'tipos-medios-cargo.editar', 'tipos-medios-cargo.eliminar',
                'salarios.ver', 'salarios.crear', 'salarios.editar', 'salarios.eliminar',
                'salarios-administrativos.ver', 'salarios-administrativos.crear', 'salarios-administrativos.editar', 'salarios-administrativos.eliminar',
                'tipos-calificadores.ver', 'tipos-calificadores.crear', 'tipos-calificadores.editar', 'tipos-calificadores.eliminar',
                'tipos-causas-laborales.ver', 'tipos-causas-laborales.crear', 'tipos-causas-laborales.editar', 'tipos-causas-laborales.eliminar',
                'tipos-causas-baja.ver', 'tipos-causas-baja.crear', 'tipos-causas-baja.editar', 'tipos-causas-baja.eliminar',
                'tipos-causas-movimiento.ver', 'tipos-causas-movimiento.crear', 'tipos-causas-movimiento.editar', 'tipos-causas-movimiento.eliminar',
                'tipos-clasificacion-laboral.ver', 'tipos-clasificacion-laboral.crear', 'tipos-clasificacion-laboral.editar', 'tipos-clasificacion-laboral.eliminar',
                'tipos-color-piel.ver', 'tipos-color-piel.crear', 'tipos-color-piel.editar', 'tipos-color-piel.eliminar',
                'tipos-deducciones.ver', 'tipos-deducciones.crear', 'tipos-deducciones.editar', 'tipos-deducciones.eliminar',
                'tipos-especialidad.ver', 'tipos-especialidad.crear', 'tipos-especialidad.editar', 'tipos-especialidad.eliminar',
                'tipos-estado-civil.ver', 'tipos-estado-civil.crear', 'tipos-estado-civil.editar', 'tipos-estado-civil.eliminar',
                'tipos-grupo-horario.ver', 'tipos-grupo-horario.crear', 'tipos-grupo-horario.editar', 'tipos-grupo-horario.eliminar',
                'tipos-integracion-politica.ver', 'tipos-integracion-politica.crear', 'tipos-integracion-politica.editar', 'tipos-integracion-politica.eliminar',
                'tipos-medios-proteccion.ver', 'tipos-medios-proteccion.crear', 'tipos-medios-proteccion.editar', 'tipos-medios-proteccion.eliminar',
                'tipos-nivel-educacion.ver', 'tipos-nivel-educacion.crear', 'tipos-nivel-educacion.editar', 'tipos-nivel-educacion.eliminar',
                'tipos-plantillas.ver', 'tipos-plantillas.crear', 'tipos-plantillas.editar', 'tipos-plantillas.eliminar',
                'tipos-sexo.ver', 'tipos-sexo.crear', 'tipos-sexo.editar', 'tipos-sexo.eliminar',
                'tipos-tallas.ver', 'tipos-tallas.crear', 'tipos-tallas.editar', 'tipos-tallas.eliminar',
                'tipos-ubicacion-defensa.ver', 'tipos-ubicacion-defensa.crear', 'tipos-ubicacion-defensa.editar', 'tipos-ubicacion-defensa.eliminar',

                // RRHH - Tablas faltantes parte 2
                'centros-costos.ver', 'centros-costos.crear', 'centros-costos.editar', 'centros-costos.eliminar',
                'tipos-articulos-bolsa.ver', 'tipos-articulos-bolsa.crear', 'tipos-articulos-bolsa.editar', 'tipos-articulos-bolsa.eliminar',

                'tipos-jefe-grupo.ver', 'tipos-jefe-grupo.crear', 'tipos-jefe-grupo.editar', 'tipos-jefe-grupo.eliminar',
                'pagos-adicionales-cargo.ver', 'pagos-adicionales-cargo.crear', 'pagos-adicionales-cargo.editar', 'pagos-adicionales-cargo.eliminar',

                'tipos-entidad.ver', 'tipos-entidad.crear', 'tipos-entidad.editar', 'tipos-entidad.eliminar',
                'empleados.ver', 'empleados.crear', 'empleados.editar', 'empleados.eliminar',
                'descuentos-empleados.ver', 'descuentos-empleados.crear', 'descuentos-empleados.editar', 'descuentos-empleados.eliminar',
                'vacaciones.ver', 'vacaciones.crear', 'vacaciones.editar', 'vacaciones.eliminar',
            ],
            'CONTABILIDAD' => [
                'dashboard.ver',
                'conciliaciones.ver', 'conciliaciones.crear', 'conciliaciones.editar', 'conciliaciones.eliminar',
                'tipos-conceptos.ver', 'tipos-conceptos.crear', 'tipos-conceptos.editar', 'tipos-conceptos.eliminar',
                'otros-gastos.ver', 'otros-gastos.crear', 'otros-gastos.editar', 'otros-gastos.eliminar',
                'combustible-cargas.ver', 'combustible-cargas.crear', 'combustible-cargas.editar', 'combustible-cargas.eliminar',
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
