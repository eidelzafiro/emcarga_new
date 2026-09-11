<template>
  <AppLayout :title="title">
    <div class="space-y-6">
      <!-- Encabezado -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard de Comercial</h1>
          <p class="text-gray-500 dark:text-gray-400 text-sm mt-1 flex items-center gap-2">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800 dark:bg-cyan-900/50 dark:text-cyan-300">
              Comercial
            </span>
            {{ entidadNombre }}
          </p>
        </div>
        <div class="text-right">
          <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
            {{ mesLabel }}
          </span>
          <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">Operaciones: {{ fechaOperaciones }}</p>
        </div>
      </div>

      <!-- KPIs Totales -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ingresos Facturados</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(totales.ingresos_mt, 2) }} <span class="text-sm font-normal text-gray-400">MT</span></p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatNumber(totales.total_facturas) }} facturas</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-cyan-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-dollar text-white text-lg" />
            </div>
          </div>
        </div>
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cartas de Porte</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(totales.cp_del_mes) }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatNumber(totales.cp_facturadas) }} facturadas</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-file text-white text-lg" />
            </div>
          </div>
        </div>
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tráfico Producido</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(totales.traf_real) }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatNumber(totales.toneladas) }} toneladas</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-chart-line text-white text-lg" />
            </div>
          </div>
        </div>
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Solicitudes</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(totales.solicitudes_total) }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ formatNumber(totales.hr_cerradas) }} hojas de ruta cerradas</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-violet-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-shopping-cart text-white text-lg" />
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ DOCUMENTOS DEL MES ═══════════════════ -->
      <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-blue-500 flex items-center justify-center">
              <i class="pi pi-file text-white text-sm" />
            </div>
            <div>
              <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Cartas de Porte por Cliente — {{ mesLabel }}</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">Emitidas, recepcionadas, aforadas y facturadas</p>
            </div>
          </div>
          <div class="p-5">
            <div v-if="documentosPorCliente.length" class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300">Cliente</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Emitidas</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Recepcionadas</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Aforadas</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Facturadas</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center font-bold">Total</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr v-for="fila in documentosPorCliente" :key="fila.cliente" class="hover:bg-gray-50 dark:hover:bg-gray-700/80">
                    <td class="table-cell font-medium dark:text-gray-200">{{ fila.cliente }}</td>
                    <td class="table-cell text-center font-mono text-blue-600 dark:text-blue-400">{{ formatNumber(fila.emitidas) }}</td>
                    <td class="table-cell text-center font-mono text-amber-600 dark:text-amber-400">{{ formatNumber(fila.recepcionadas) }}</td>
                    <td class="table-cell text-center font-mono text-cyan-600 dark:text-cyan-400">{{ formatNumber(fila.aforadas) }}</td>
                    <td class="table-cell text-center font-mono text-emerald-600 dark:text-emerald-400">{{ formatNumber(fila.facturadas) }}</td>
                    <td class="table-cell text-center font-mono font-bold dark:text-gray-100">{{ formatNumber(fila.total) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="text-sm text-gray-500 dark:text-gray-400">No hay cartas de porte en el mes de operaciones.</p>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-violet-500 flex items-center justify-center">
              <i class="pi pi-truck text-white text-sm" />
            </div>
            <div>
              <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Hojas de Ruta por Chofer — {{ mesLabel }}</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">Emitidas y cerradas en el mes</p>
            </div>
          </div>
          <div class="p-5">
            <div v-if="hrPorChofer.length" class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300">Chofer</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Emitidas</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Cerradas</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center font-bold">Total</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr v-for="fila in hrPorChofer" :key="fila.chofer" class="hover:bg-gray-50 dark:hover:bg-gray-700/80">
                    <td class="table-cell font-medium dark:text-gray-200">{{ fila.chofer }}</td>
                    <td class="table-cell text-center font-mono text-blue-600 dark:text-blue-400">{{ formatNumber(fila.emitidas) }}</td>
                    <td class="table-cell text-center font-mono text-emerald-600 dark:text-emerald-400">{{ formatNumber(fila.cerradas) }}</td>
                    <td class="table-cell text-center font-mono font-bold dark:text-gray-100">{{ formatNumber(fila.total) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="text-sm text-gray-500 dark:text-gray-400">No hay hojas de ruta en el mes de operaciones.</p>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ SECCIÓN 2: SOLICITUDES DEL MES ═══════════════════ -->
      <div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center">
              <i class="pi pi-shopping-cart text-white text-sm" />
            </div>
            <div>
              <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Solicitudes por Cliente — {{ mesLabel }}</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">Pendientes, en proceso y ejecutadas</p>
            </div>
          </div>
          <div class="p-5">
            <div v-if="solicitudesPorCliente.length" class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300">Cliente</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Pendientes</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">En proceso</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Ejecutadas</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center font-bold">Total</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr v-for="fila in solicitudesPorCliente" :key="fila.cliente" class="hover:bg-gray-50 dark:hover:bg-gray-700/80">
                    <td class="table-cell font-medium dark:text-gray-200">{{ fila.cliente }}</td>
                    <td class="table-cell text-center font-mono text-amber-600 dark:text-amber-400">{{ formatNumber(fila.pendientes) }}</td>
                    <td class="table-cell text-center font-mono text-cyan-600 dark:text-cyan-400">{{ formatNumber(fila.en_proceso) }}</td>
                    <td class="table-cell text-center font-mono text-emerald-600 dark:text-emerald-400">{{ formatNumber(fila.ejecutadas) }}</td>
                    <td class="table-cell text-center font-mono font-bold dark:text-gray-100">{{ formatNumber(fila.total) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="text-sm text-gray-500 dark:text-gray-400">No hay solicitudes en el mes de operaciones.</p>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ SECCIÓN 3: FACTURACIÓN ═══════════════════ -->
      <div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center">
              <i class="pi pi-dollar text-white text-sm" />
            </div>
            <div>
              <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Facturación</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">Ingresos por concepto y principales clientes</p>
            </div>
          </div>

          <div class="p-5 space-y-5">
            <div v-if="ingresosPorConcepto.length">
              <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Ingresos por concepto</h4>
              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead>
                    <tr>
                      <th class="table-header dark:bg-gray-700 dark:text-gray-300">Concepto</th>
                      <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Facturas</th>
                      <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Total MT</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <tr v-for="(fila, i) in ingresosPorConcepto" :key="i" class="hover:bg-gray-50 dark:hover:bg-gray-700/80">
                      <td class="table-cell font-medium dark:text-gray-200">{{ fila.concepto }}</td>
                      <td class="table-cell text-center dark:text-gray-400">{{ fila.cantidad }}</td>
                      <td class="table-cell text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ formatNumber(fila.total_mt, 2) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div v-if="facturacionPorCliente.length">
              <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Principales clientes</h4>
              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead>
                    <tr>
                      <th class="table-header dark:bg-gray-700 dark:text-gray-300">#</th>
                      <th class="table-header dark:bg-gray-700 dark:text-gray-300">Cliente</th>
                      <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Facturas</th>
                      <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Total MT</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <tr v-for="(fila, i) in facturacionPorCliente" :key="i" class="hover:bg-gray-50 dark:hover:bg-gray-700/80">
                      <td class="table-cell text-gray-400 dark:text-gray-500 font-mono text-xs">{{ i + 1 }}</td>
                      <td class="table-cell font-medium dark:text-gray-200">{{ fila.cliente }}</td>
                      <td class="table-cell text-center dark:text-gray-400">{{ fila.cantidad_facturas }}</td>
                      <td class="table-cell text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ formatNumber(fila.total_mt, 2) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div v-if="!ingresosPorConcepto.length && !facturacionPorCliente.length" class="text-center py-8">
              <i class="pi pi-inbox text-3xl text-gray-300 dark:text-gray-600 block mb-2" />
              <p class="text-sm text-gray-400 dark:text-gray-500">No hay datos de facturación para este período</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ SECCIÓN 4: TRÁFICO ═══════════════════ -->
      <div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-cyan-500 flex items-center justify-center">
              <i class="pi pi-chart-line text-white text-sm" />
            </div>
            <div>
              <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Tráfico por Fecha de Parte — {{ mesLabel }}</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">Toneladas, kilómetros, tráfico e ingresos del mes</p>
            </div>
          </div>
          <div class="p-5">
            <div v-if="traficoPorFecha.length" class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300">Fecha</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Toneladas</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Kms Carga</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Kms Vacío</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Tráfico Posible</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Tráfico Producido</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Ingresos</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr v-for="fila in traficoPorFecha" :key="fila.fecha" class="hover:bg-gray-50 dark:hover:bg-gray-700/80">
                    <td class="table-cell font-medium dark:text-gray-200">{{ fila.etiqueta }}</td>
                    <td class="table-cell text-right font-mono dark:text-gray-300">{{ formatNumber(fila.toneladas) }}</td>
                    <td class="table-cell text-right font-mono dark:text-gray-300">{{ formatNumber(fila.km_carga) }}</td>
                    <td class="table-cell text-right font-mono dark:text-gray-300">{{ formatNumber(fila.km_vacio) }}</td>
                    <td class="table-cell text-right font-mono dark:text-gray-300">{{ formatNumber(fila.traf_pos) }}</td>
                    <td class="table-cell text-right font-mono dark:text-gray-300">{{ formatNumber(fila.traf_real) }}</td>
                    <td class="table-cell text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ formatNumber(fila.ingreso, 2) }}</td>
                  </tr>
                  <tr class="font-bold bg-gray-50 dark:bg-gray-700/60">
                    <td class="table-cell dark:text-gray-100">TOTAL</td>
                    <td class="table-cell text-right font-mono dark:text-gray-100">{{ formatNumber(traficoTotal.toneladas) }}</td>
                    <td class="table-cell text-right font-mono dark:text-gray-100">{{ formatNumber(traficoTotal.km_carga) }}</td>
                    <td class="table-cell text-right font-mono dark:text-gray-100">{{ formatNumber(traficoTotal.km_vacio) }}</td>
                    <td class="table-cell text-right font-mono dark:text-gray-100">{{ formatNumber(traficoTotal.traf_pos) }}</td>
                    <td class="table-cell text-right font-mono dark:text-gray-100">{{ formatNumber(traficoTotal.traf_real) }}</td>
                    <td class="table-cell text-right font-mono text-emerald-600 dark:text-emerald-400">{{ formatNumber(traficoTotal.ingreso, 2) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="text-sm text-gray-500 dark:text-gray-400">No hay aforos en el mes de operaciones.</p>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ SECCIÓN 5: TABLERO DE FLOTA ═══════════════════ -->
      <div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center">
                <i class="pi pi-truck text-white text-sm" />
              </div>
              <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Tablero de Flota</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Vehículos por tipo de equipo</p>
              </div>
            </div>
            <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
              <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500" /> Activo</span>
              <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500" /> En taller (OT abierta)</span>
            </div>
          </div>
          <div class="p-5">
            <div v-if="flotaPorTipo.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
              <div v-for="tipo in flotaPorTipo" :key="tipo.nombre" class="rounded-xl border-2 overflow-hidden bg-white dark:bg-gray-800 shadow-sm">
                <div class="p-4 flex items-center gap-3 border-b border-gray-100 dark:border-gray-700"
                     :class="tipo.tallerCount > 0 ? 'bg-gradient-to-r from-amber-50 to-white dark:from-amber-900/20 dark:to-gray-800' : 'bg-gradient-to-r from-emerald-50 to-white dark:from-emerald-900/20 dark:to-gray-800'">
                  <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0"
                       :class="tipo.tallerCount > 0 ? 'bg-amber-100 dark:bg-amber-900/40' : 'bg-emerald-100 dark:bg-emerald-900/40'">
                    <i :class="tipo.esArrastre ? 'pi pi-box' : 'pi pi-truck'" class="text-lg"
                       :style="{ color: tipo.tallerCount > 0 ? '#d97706' : '#059669' }" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <h3 class="font-bold text-gray-900 dark:text-gray-100 truncate">{{ tipo.nombre }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      {{ tipo.activosCount }} activo{{ tipo.activosCount !== 1 ? 's' : '' }}
                      <span v-if="tipo.tallerCount > 0" class="text-amber-600 dark:text-amber-400"> · {{ tipo.tallerCount }} en taller</span>
                    </p>
                  </div>
                  <span class="text-2xl font-extrabold" :class="tipo.tallerCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'">
                    {{ tipo.total }}
                  </span>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[400px] overflow-y-auto">
                  <template v-if="tipo.activos.length">
                    <div v-for="v in tipo.activos" :key="v.id" class="px-4 py-2.5 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/80 transition">
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-mono font-bold text-gray-900 dark:text-gray-100">{{ v.codigo }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ v.marca }}</p>
                      </div>
                      <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0 ml-2" />
                    </div>
                  </template>
                  <template v-if="tipo.taller.length">
                    <div v-for="v in tipo.taller" :key="v.id" class="px-4 py-2.5 flex items-center justify-between bg-amber-50/50 dark:bg-amber-900/10 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition">
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-mono font-bold text-gray-900 dark:text-gray-100">{{ v.codigo }}</p>
                        <p class="text-xs text-amber-600 dark:text-amber-400 truncate">
                          <i class="pi pi-wrench mr-1" />OT {{ v.ot?.numero ?? '—' }} · {{ v.ot?.dias ?? '' }} días
                        </p>
                      </div>
                      <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse shrink-0 ml-2" />
                    </div>
                  </template>
                  <div v-if="!tipo.activos.length && !tipo.taller.length" class="px-4 py-3 text-center text-xs text-gray-400 dark:text-gray-500">
                    Sin vehículos
                  </div>
                </div>
              </div>
            </div>
            <p v-else class="text-sm text-gray-400 dark:text-gray-500 py-6 text-center">Sin vehículos en la flota</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  title: { type: String, default: 'Dashboard Comercial' },
  fechaOperaciones: { type: String, default: '' },
  entidadNombre: { type: String, default: '' },
  entidad: { type: Object, default: null },

  totales: { type: Object, default: () => ({}) },

  // Sección 1
  documentosPorCliente: { type: Array, default: () => [] },
  hrPorChofer: { type: Array, default: () => [] },
  // Sección 2
  solicitudesPorCliente: { type: Array, default: () => [] },
  // Sección 3 (paridad Contabilidad)
  ingresosPorConcepto: { type: Array, default: () => [] },
  facturacionPorConcepto: { type: Array, default: () => [] },
  facturacionPorCliente: { type: Array, default: () => [] },
  // Sección 4
  traficoPorFecha: { type: Array, default: () => [] },
  traficoTotal: { type: Object, default: () => ({}) },
  // Sección 5
  flotaPorTipo: { type: Array, default: () => [] },
  // Serie
  serie: { type: Array, default: () => [] },
});

const mesLabel = computed(() => {
  if (!props.fechaOperaciones) return '';
  const d = new Date(props.fechaOperaciones + 'T00:00:00');
  return d.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
});

function formatNumber(valor, decimales = 0) {
  const num = Number(valor) || 0;
  return num.toLocaleString('es-CU', {
    minimumFractionDigits: decimales,
    maximumFractionDigits: decimales,
  });
}
</script>
