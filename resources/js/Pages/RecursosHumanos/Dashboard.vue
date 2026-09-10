<template>
  <AppLayout :title="title">
    <div class="space-y-6">
      <!-- Encabezado -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard de Recursos Humanos</h1>
          <p class="text-gray-500 dark:text-gray-400 text-sm mt-1 flex items-center gap-2">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
              Recursos Humanos
            </span>
            {{ entidadNombre }}
          </p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
          Plantilla activa
        </span>
      </div>

      <!-- KPIs Totales -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Trabajadores</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(totalTrabajadores) }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Plantilla activa</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-users text-white text-lg" />
            </div>
          </div>
        </div>
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Áreas</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(totalAreas) }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Áreas activas</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-building text-white text-lg" />
            </div>
          </div>
        </div>
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cargos en Uso</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(cargosEnUso) }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">De {{ formatNumber(totalCargos) }} definidos</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-briefcase text-white text-lg" />
            </div>
          </div>
        </div>
        <div class="kpi-card dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-start justify-between">
            <div class="min-w-0">
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Choferes</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1.5">{{ formatNumber(totalChoferes) }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Personal de conducción</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-violet-500 flex items-center justify-center shrink-0 ml-3">
              <i class="pi pi-truck text-white text-lg" />
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ SECCIÓN 1: EMPRESA ACTIVA ═══════════════════ -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-blue-500 flex items-center justify-center">
            <i class="pi pi-building text-white text-sm" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Empresa Activa</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Estructura organizativa y distribución de personal</p>
          </div>
        </div>

        <div class="p-5 space-y-5">
          <!-- Salarios por grupo escala en 3 columnas -->
          <div v-if="salariosPorGrupoEscala.length">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Salarios por grupo escala</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
              <div v-for="(fila, i) in salariosPorGrupoEscala" :key="i"
                   class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-base font-bold text-gray-800 dark:text-gray-100">{{ fila.grupo_escala }}</span>
                  <span class="text-sm font-mono font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">{{ fila.trabajadores }}</span>
                </div>
                <p class="text-xl font-bold font-mono text-emerald-600 dark:text-emerald-400">{{ formatNumber(fila.salario_total, 2) }}</p>
              </div>
            </div>
          </div>

          <!-- Cobertura de plantilla por área -->
          <div v-if="plantillaPorArea.length">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Cobertura de Plantilla por Área</h4>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300">Área</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Propuesta</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Aprobada</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Cubierta</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">% Cobertura</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr v-for="(fila, i) in plantillaPorArea" :key="i" class="hover:bg-gray-50 dark:hover:bg-gray-700/80">
                    <td class="table-cell font-medium dark:text-gray-200">{{ fila.area }}</td>
                    <td class="table-cell text-center font-mono dark:text-gray-300">{{ formatNumber(fila.plazas_propuesta) }}</td>
                    <td class="table-cell text-center font-mono dark:text-gray-300">{{ formatNumber(fila.plazas_aprobadas) }}</td>
                    <td class="table-cell text-center font-mono" :class="fila.plazas_cubiertas >= fila.plazas_aprobadas ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
                      {{ formatNumber(fila.plazas_cubiertas) }}
                    </td>
                    <td class="table-cell text-right font-mono text-gray-500 dark:text-gray-400">
                      {{ fila.plazas_aprobadas > 0 ? ((fila.plazas_cubiertas / fila.plazas_aprobadas) * 100).toFixed(1) : '0.0' }}%
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="bg-gray-50 dark:bg-gray-700/60 font-semibold">
                    <td class="table-cell dark:text-gray-200">Total</td>
                    <td class="table-cell text-center font-mono dark:text-gray-300">{{ formatNumber(totalPlazasPropuesta) }}</td>
                    <td class="table-cell text-center font-mono dark:text-gray-300">{{ formatNumber(totalPlazasAprobadas) }}</td>
                    <td class="table-cell text-center font-mono text-emerald-600 dark:text-emerald-400">{{ formatNumber(totalTrabajadoresReales) }}</td>
                    <td class="table-cell text-right font-mono text-gray-500 dark:text-gray-400">
                      {{ totalPlazasAprobadas > 0 ? ((totalTrabajadoresReales / totalPlazasAprobadas) * 100).toFixed(1) : '0.0' }}%
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <div v-if="!salariosPorGrupoEscala.length && !plantillaPorArea.length" class="text-center py-8">
            <i class="pi pi-inbox text-3xl text-gray-300 dark:text-gray-600 block mb-2" />
            <p class="text-sm text-gray-400 dark:text-gray-500">No hay datos estructurales para esta entidad</p>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ SECCIÓN 2: FUERZA DE TRABAJO ═══════════════════ -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center">
            <i class="pi pi-users text-white text-sm" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Fuerza de Trabajo</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Composición de la plantilla por características demográficas</p>
          </div>
        </div>

        <div class="p-5">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- Por sexo -->
            <div v-if="porSexo.length">
              <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Por sexo</h4>
              <div class="space-y-2">
                <div v-for="(fila, i) in porSexo" :key="i" class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                       :class="fila.etiqueta === 'Masculino' ? 'bg-blue-100 dark:bg-blue-900/50' : fila.etiqueta === 'Femenino' ? 'bg-pink-100 dark:bg-pink-900/50' : 'bg-gray-100 dark:bg-gray-700'">
                    <i class="pi text-sm"
                       :class="fila.etiqueta === 'Masculino' ? 'pi-mars text-blue-600 dark:text-blue-400' : fila.etiqueta === 'Femenino' ? 'pi-venus text-pink-600 dark:text-pink-400' : 'pi-question text-gray-500'" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center">
                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ fila.etiqueta }}</span>
                      <span class="text-sm font-mono font-semibold text-gray-900 dark:text-gray-100">{{ fila.total }}</span>
                    </div>
                    <div class="mt-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                      <div class="h-full rounded-full transition-all"
                           :class="fila.etiqueta === 'Masculino' ? 'bg-blue-500' : fila.etiqueta === 'Femenino' ? 'bg-pink-500' : 'bg-gray-400'"
                           :style="{ width: totalTrabajadores > 0 ? ((fila.total / totalTrabajadores) * 100) + '%' : '0%' }" />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Por color de piel -->
            <div v-if="porColorPiel.length">
              <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Por color de piel</h4>
              <div class="space-y-2">
                <div v-for="(fila, i) in porColorPiel" :key="i" class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0">
                    <i class="pi pi-user text-gray-500 text-sm" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center">
                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ fila.etiqueta }}</span>
                      <span class="text-sm font-mono font-semibold text-gray-900 dark:text-gray-100">{{ fila.total }}</span>
                    </div>
                    <div class="mt-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                      <div class="h-full bg-amber-500 rounded-full transition-all"
                           :style="{ width: totalTrabajadores > 0 ? ((fila.total / totalTrabajadores) * 100) + '%' : '0%' }" />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Por nivel educacional -->
            <div v-if="porNivelEducacional.length">
              <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Por nivel educacional</h4>
              <div class="space-y-2">
                <div v-for="(fila, i) in porNivelEducacional" :key="i" class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-900/50 flex items-center justify-center shrink-0">
                    <i class="pi pi-book text-violet-600 dark:text-violet-400 text-sm" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center">
                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ fila.etiqueta }}</span>
                      <span class="text-sm font-mono font-semibold text-gray-900 dark:text-gray-100">{{ fila.total }}</span>
                    </div>
                    <div class="mt-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                      <div class="h-full bg-violet-500 rounded-full transition-all"
                           :style="{ width: totalTrabajadores > 0 ? ((fila.total / totalTrabajadores) * 100) + '%' : '0%' }" />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Por rango de edad -->
            <div v-if="porEdad.length">
              <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Por rango de edad</h4>
              <div class="space-y-2">
                <div v-for="(fila, i) in porEdad" :key="i" class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-cyan-100 dark:bg-cyan-900/50 flex items-center justify-center shrink-0">
                    <i class="pi pi-calendar text-cyan-600 dark:text-cyan-400 text-sm" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center">
                      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ fila.etiqueta }} años</span>
                      <span class="text-sm font-mono font-semibold text-gray-900 dark:text-gray-100">{{ fila.total }}</span>
                    </div>
                    <div class="mt-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                      <div class="h-full bg-cyan-500 rounded-full transition-all"
                           :style="{ width: totalTrabajadores > 0 ? ((fila.total / totalTrabajadores) * 100) + '%' : '0%' }" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="!porSexo.length && !porColorPiel.length && !porNivelEducacional.length && !porEdad.length" class="text-center py-8">
            <i class="pi pi-inbox text-3xl text-gray-300 dark:text-gray-600 block mb-2" />
            <p class="text-sm text-gray-400 dark:text-gray-500">No hay datos demográficos para esta entidad</p>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ SECCIÓN 3: DOCUMENTACIÓN CHOFERES ═══════════════════ -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center">
            <i class="pi pi-id-card text-white text-base" />
          </div>
          <div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Documentación de Choferes</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Estado de licencias, psicométrico, recalificación y chequeo médico</p>
          </div>
        </div>

        <div class="p-5 space-y-5">
          <!-- Resumen de documentos -->
          <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <!-- Licencia -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
              <div class="flex items-center gap-2 mb-3">
                <div class="w-7 h-7 rounded bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                  <i class="pi pi-id-card text-blue-600 dark:text-blue-400 text-sm" />
                </div>
                <span class="text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase">Licencia</span>
              </div>
              <div class="space-y-1.5">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Vigentes</span>
                  <span class="font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ documentacion.licencia.vigentes }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Por vencer (90d)</span>
                  <span class="font-mono font-semibold text-amber-600 dark:text-amber-400">{{ documentacion.licencia.por_vencer }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Vencidas</span>
                  <span class="font-mono font-semibold text-red-600 dark:text-red-400">{{ documentacion.licencia.vencidas }}</span>
                </div>
              </div>
            </div>

            <!-- Psicométrico -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
              <div class="flex items-center gap-2 mb-3">
                <div class="w-7 h-7 rounded bg-violet-100 dark:bg-violet-900/50 flex items-center justify-center">
                  <i class="pi pi-brain text-violet-600 dark:text-violet-400 text-sm" />
                </div>
                <span class="text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase">Psicométrico</span>
              </div>
              <div class="space-y-1.5">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Vigentes</span>
                  <span class="font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ documentacion.psicometrico.vigentes }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Por vencer (90d)</span>
                  <span class="font-mono font-semibold text-amber-600 dark:text-amber-400">{{ documentacion.psicometrico.por_vencer }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Vencidos</span>
                  <span class="font-mono font-semibold text-red-600 dark:text-red-400">{{ documentacion.psicometrico.vencidos }}</span>
                </div>
              </div>
            </div>

            <!-- Recalificación -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
              <div class="flex items-center gap-2 mb-3">
                <div class="w-7 h-7 rounded bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                  <i class="pi pi-sync text-amber-600 dark:text-amber-400 text-sm" />
                </div>
                <span class="text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase">Recalificación</span>
              </div>
              <div class="space-y-1.5">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Vigentes</span>
                  <span class="font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ documentacion.recalificacion.vigentes }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Por vencer (90d)</span>
                  <span class="font-mono font-semibold text-amber-600 dark:text-amber-400">{{ documentacion.recalificacion.por_vencer }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Vencidas</span>
                  <span class="font-mono font-semibold text-red-600 dark:text-red-400">{{ documentacion.recalificacion.vencidas }}</span>
                </div>
              </div>
            </div>

            <!-- Chequeo médico -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
              <div class="flex items-center gap-2 mb-3">
                <div class="w-7 h-7 rounded bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                  <i class="pi pi-heart text-emerald-600 dark:text-emerald-400 text-sm" />
                </div>
                <span class="text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase">Chequeo Médico</span>
              </div>
              <div class="space-y-1.5">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Vigentes</span>
                  <span class="font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ documentacion.chequeo_medico.vigentes }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Por vencer (90d)</span>
                  <span class="font-mono font-semibold text-amber-600 dark:text-amber-400">{{ documentacion.chequeo_medico.por_vencer }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Vencidos</span>
                  <span class="font-mono font-semibold text-red-600 dark:text-red-400">{{ documentacion.chequeo_medico.vencidos }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Categorías de licencia -->
          <div v-if="categoriasLicencia.length">
            <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Categorías de licencia</h4>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300">Categoría</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Choferes</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr v-for="(fila, i) in categoriasLicencia" :key="i" class="hover:bg-gray-50 dark:hover:bg-gray-700/80">
                    <td class="table-cell font-medium text-sm dark:text-gray-200">{{ fila.etiqueta }}</td>
                    <td class="table-cell text-center font-mono text-sm dark:text-gray-300">{{ fila.total }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-if="!totalChoferes" class="text-center py-8">
            <i class="pi pi-inbox text-3xl text-gray-300 dark:text-gray-600 block mb-2" />
            <p class="text-base text-gray-400 dark:text-gray-500">No hay datos de documentación para esta entidad</p>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ SECCIÓN 4: SALARIO ═══════════════════ -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-red-500 flex items-center justify-center">
            <i class="pi pi-money-bill text-white text-sm" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Salario y Compensaciones</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Incidencias, penalizaciones y distribución salarial</p>
          </div>
        </div>

        <div class="p-5 space-y-5">
          <!-- Resumen incidencias y penalizaciones -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Incidencias -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
              <div class="flex items-center gap-2 mb-3">
                <div class="w-6 h-6 rounded bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                  <i class="pi pi-exclamation-triangle text-amber-600 dark:text-amber-400 text-xs" />
                </div>
                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Incidencias del mes</span>
              </div>
              <div class="space-y-1.5">
                <div class="flex justify-between text-xs">
                  <span class="text-gray-500 dark:text-gray-400">Total incidencias</span>
                  <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">{{ formatNumber(totalIncidencias) }}</span>
                </div>
                <div class="flex justify-between text-xs">
                  <span class="text-gray-500 dark:text-gray-400">Importe total</span>
                  <span class="font-mono font-semibold text-red-600 dark:text-red-400">{{ formatNumber(importeIncidencias, 2) }} MN</span>
                </div>
              </div>
              <div v-if="incidenciasMes.length" class="mt-3 space-y-1">
                <div v-for="(inc, i) in incidenciasMes.slice(0, 5)" :key="i" class="flex justify-between text-xs">
                  <span class="text-gray-500 dark:text-gray-400 truncate max-w-[200px]">{{ inc.tipo }}</span>
                  <span class="font-mono text-gray-700 dark:text-gray-300">{{ inc.total }}</span>
                </div>
              </div>
            </div>

            <!-- Penalizaciones -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
              <div class="flex items-center gap-2 mb-3">
                <div class="w-6 h-6 rounded bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                  <i class="pi pi-ban text-red-600 dark:text-red-400 text-xs" />
                </div>
                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Penalizaciones del mes</span>
              </div>
              <div class="space-y-1.5">
                <div class="flex justify-between text-xs">
                  <span class="text-gray-500 dark:text-gray-400">Total penalizaciones</span>
                  <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">{{ formatNumber(totalPenalizaciones) }}</span>
                </div>
                <div class="flex justify-between text-xs">
                  <span class="text-gray-500 dark:text-gray-400">Importe total</span>
                  <span class="font-mono font-semibold text-red-600 dark:text-red-400">{{ formatNumber(importePenalizaciones, 2) }} MN</span>
                </div>
              </div>
              <div v-if="penalizacionesMes.length" class="mt-3 space-y-1">
                <div v-for="(pen, i) in penalizacionesMes.slice(0, 5)" :key="i" class="flex justify-between text-xs">
                  <span class="text-gray-500 dark:text-gray-400 truncate max-w-[200px]">{{ pen.tipo }}</span>
                  <span class="font-mono text-gray-700 dark:text-gray-300">{{ pen.total }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Salarios por áreas -->
          <div v-if="salariosPorArea.length">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Salarios por áreas</h4>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300">Área</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Trabajadores</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Importe Total</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr v-for="(fila, i) in salariosPorArea" :key="i" class="hover:bg-gray-50 dark:hover:bg-gray-700/80">
                    <td class="table-cell font-medium dark:text-gray-200">{{ fila.area }}</td>
                    <td class="table-cell text-center font-mono dark:text-gray-300">{{ fila.trabajadores }}</td>
                    <td class="table-cell text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ formatNumber(fila.total_salario, 2) }}</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                    <td class="table-cell font-bold dark:text-gray-200">Total</td>
                    <td class="table-cell text-center font-mono font-bold dark:text-gray-200">{{ formatNumber(totalTrabajadoresSalario) }}</td>
                    <td class="table-cell text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ formatNumber(totalSalarioFinal, 2) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- Salarios de choferes por tasas -->
          <div v-if="salariosChoferesPorTasas.length">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Salario de choferes por tasa, almacenamiento y tiempo real trabajado</h4>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300">Tasa</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-center">Choferes</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Salario Regular</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Almacenaje</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Tiempo Real</th>
                    <th class="table-header dark:bg-gray-700 dark:text-gray-300 text-right">Total Salario</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr v-for="(fila, i) in salariosChoferesPorTasas" :key="i" class="hover:bg-gray-50 dark:hover:bg-gray-700/80">
                    <td class="table-cell font-medium dark:text-gray-200">{{ formatNumber(fila.tasa, 4) }}</td>
                    <td class="table-cell text-center font-mono dark:text-gray-300">{{ fila.choferes }}</td>
                    <td class="table-cell text-right font-mono dark:text-gray-300">{{ formatNumber(fila.total_regular, 2) }}</td>
                    <td class="table-cell text-right font-mono text-amber-600 dark:text-amber-400">{{ formatNumber(fila.total_almacenaje, 2) }}</td>
                    <td class="table-cell text-right font-mono text-blue-600 dark:text-blue-400">{{ formatNumber(fila.total_tiempo_trabajado, 2) }}</td>
                    <td class="table-cell text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ formatNumber(fila.total_salario, 2) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-if="!incidenciasMes.length && !penalizacionesMes.length && !salariosPorArea.length && !salariosChoferesPorTasas.length" class="text-center py-8">
            <i class="pi pi-inbox text-3xl text-gray-300 dark:text-gray-600 block mb-2" />
            <p class="text-sm text-gray-400 dark:text-gray-500">No hay datos salariales para este período</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  title: { type: String, default: 'Dashboard Recursos Humanos' },
  entidadNombre: { type: String, default: '' },
  entidad: { type: Object, default: null },

  // Sección 1: Empresa activa
  totalAreas: { type: Number, default: 0 },
  totalCargos: { type: Number, default: 0 },
  cargosEnUso: { type: Number, default: 0 },
  totalTrabajadores: { type: Number, default: 0 },
  salariosPorGrupoEscala: { type: Array, default: () => [] },
  plantillaPorArea: { type: Array, default: () => [] },
  totalPlazasPropuesta: { type: Number, default: 0 },
  totalPlazasAprobadas: { type: Number, default: 0 },
  totalTrabajadoresReales: { type: Number, default: 0 },

  // Sección 2: Fuerza de trabajo
  porSexo: { type: Array, default: () => [] },
  porColorPiel: { type: Array, default: () => [] },
  porNivelEducacional: { type: Array, default: () => [] },
  porEdad: { type: Array, default: () => [] },

  // Sección 3: Documentación choferes
  totalChoferes: { type: Number, default: 0 },
  categoriasLicencia: { type: Array, default: () => [] },
  documentacion: {
    type: Object,
    default: () => ({
      licencia: { vigentes: 0, vencidas: 0, por_vencer: 0 },
      psicometrico: { vigentes: 0, vencidos: 0, por_vencer: 0 },
      recalificacion: { vigentes: 0, vencidas: 0, por_vencer: 0 },
      chequeo_medico: { vigentes: 0, vencidos: 0, por_vencer: 0 },
    }),
  },

  // Sección 4: Salario
  incidenciasMes: { type: Array, default: () => [] },
  totalIncidencias: { type: Number, default: 0 },
  importeIncidencias: { type: Number, default: 0 },
  penalizacionesMes: { type: Array, default: () => [] },
  totalPenalizaciones: { type: Number, default: 0 },
  importePenalizaciones: { type: Number, default: 0 },
  salariosPorArea: { type: Array, default: () => [] },
  salariosChoferesPorTasas: { type: Array, default: () => [] },
  totalSalarioFinal: { type: Number, default: 0 },
  totalTrabajadoresSalario: { type: Number, default: 0 },
});

function formatNumber(valor, decimales = 0) {
  const num = Number(valor) || 0;
  return num.toLocaleString('es-CU', {
    minimumFractionDigits: decimales,
    maximumFractionDigits: decimales,
  });
}
</script>
