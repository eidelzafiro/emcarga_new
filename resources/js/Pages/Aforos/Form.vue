<script setup>
import { ref, reactive, computed, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import Textarea from 'primevue/textarea'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Checkbox from 'primevue/checkbox'
import RadioButton from 'primevue/radiobutton'
import { useToast } from 'primevue/usetoast'

const props = defineProps({
    tiposCarga: Array,
    clientes: Array,
    lugares: Array,
    productos: Array,
    monedas: Array,
    choferes: Array,
    tractivos: Array,
    arrastres: Array,
    cartasPendientes: Array,
    cartaPreseleccionada: Object,
    hojasRuta: Array,
    tasas: Array,
    fechaOperaciones: String,
    aforo: Object,
})

const toast = useToast()
const esEdicion = computed(() => !!props.aforo?.id)

// ---------- Estado inicial (CP preseleccionada o aforo a editar) ----------
const cartaBase = computed(() => {
    if (esEdicion.value) {
        // En edición la CP se carga completa (aunque ya tenga aforo)
        return props.cartaPreseleccionada || {}
    }
    return props.cartaPreseleccionada || null
})

function toDate(v) {
    if (!v) return null
    if (v instanceof Date) return v
    return new Date(String(v).slice(0, 10) + 'T00:00:00')
}

const form = reactive({
    id_carta_porte: props.aforo?.id_carta_porte || props.cartaPreseleccionada?.id || null,
    fecha_parte: toDate(props.aforo?.fecha_parte || props.fechaOperaciones) || new Date(),
    flete_mt: Number(props.aforo?.flete_mt ?? 0),
    flete_mlc: Number(props.aforo?.flete_mlc ?? 0),
    flete_demora: Number(props.aforo?.flete_demora ?? 0),
    otros_mt: Number(props.aforo?.otros_mt ?? 0),
    ingreso_mt: Number(props.aforo?.ingreso_mt ?? 0),

    // Datos generales editables de la CP.
    // Fase 4d: cliente/tipos/productos se derivan de la solicitud; equipo de la HR.
    id_hoja_ruta: props.cartaPreseleccionada?.id_hoja_ruta ?? null,
    id_cliente: props.cartaPreseleccionada?.cliente?.id ?? props.cartaPreseleccionada?.solicitud?.id_cliente ?? null,
    id_tractivo: props.cartaPreseleccionada?.hojaRuta?.id_tractivo ?? null,
    id_arrastre: props.cartaPreseleccionada?.hojaRuta?.id_arrastre ?? null,
    id_chofer: props.cartaPreseleccionada?.hojaRuta?.id_chofer ?? null,
    id_chofer2: props.cartaPreseleccionada?.hojaRuta?.id_chofer2 ?? null,
    id_lugar_origen: props.cartaPreseleccionada?.solicitud?.id_lugar_origen ?? props.cartaPreseleccionada?.lugar_origen?.id ?? null,
    id_lugar_destino: props.cartaPreseleccionada?.solicitud?.id_lugar_destino ?? props.cartaPreseleccionada?.lugar_destino?.id ?? null,
    id_producto: props.cartaPreseleccionada?.solicitud?.id_producto ?? null,
    id_tipo_carga: props.cartaPreseleccionada?.solicitud?.id_tipo_carga ?? null,
    id_moneda: props.cartaPreseleccionada?.solicitud?.id_moneda ?? 1,
    distancia: props.cartaPreseleccionada?.distancia ?? 0,
    toneladas: props.cartaPreseleccionada?.toneladas ?? 0,
    conduce: props.cartaPreseleccionada?.conduce ?? '',
    fecha_emision: toDate(props.cartaPreseleccionada?.fecha_emision),
    fecha_recepcion: toDate(props.cartaPreseleccionada?.fecha_recepcion),

    // Salario
    id_tasa: props.aforo?.id_tasa ?? null,
    tasa: Number(props.aforo?.tasa ?? 0),
    salario: Number(props.aforo?.salario ?? 0),

    // Indicadores
    viajes: Number(props.aforo?.viajes ?? 1),
    tipo_indicadores: Number(props.aforo?.tipo_indicadores ?? 1),

    // Almacenaje
    almacenaje_peso: Number(props.aforo?.almacenaje_peso ?? 0),
    almacenaje_horas: Number(props.aforo?.almacenaje_horas ?? 0),
    almacenaje_tarifa: Number(props.aforo?.almacenaje_tarifa ?? 0),
    almacenaje_flete: Number(props.aforo?.almacenaje_flete ?? 0),

    // Demora
    dem_carga: Number(props.aforo?.dem_carga ?? 0),
    dem_descarga: Number(props.aforo?.dem_descarga ?? 0),
    dem_total: Number(props.aforo?.dem_total ?? 0),
    fecha_carga: toDate(props.aforo?.fecha_carga),
    hora_carga_1: props.aforo?.hora_carga_1 ?? null,
    hora_carga_2: props.aforo?.hora_carga_2 ?? null,
    fecha_descarga: toDate(props.aforo?.fecha_descarga),
    hora_descarga_1: props.aforo?.hora_descarga_1 ?? null,
    hora_descarga_2: props.aforo?.hora_descarga_2 ?? null,
    tar_dem_1: Number(props.aforo?.tar_dem_1 ?? 0),
    tar_dem_2: Number(props.aforo?.tar_dem_2 ?? 0),
    flete_dem_1: Number(props.aforo?.flete_dem_1 ?? 0),
    flete_dem_2: Number(props.aforo?.flete_dem_2 ?? 0),
    tiempo_feriado: Number(props.aforo?.tiempo_feriado ?? 0),

    // Tiempos
    tiempo_otros: Number(props.aforo?.tiempo_otros ?? 0),
    tiempo_movimiento: Number(props.aforo?.tiempo_movimiento ?? 0),
    tiempo_carga: Number(props.aforo?.tiempo_carga ?? 0),
    tiempo_descarga: Number(props.aforo?.tiempo_descarga ?? 0),
    tiempo_total: Number(props.aforo?.tiempo_total ?? 0),

    // Recargos
    recargo_1: Number(props.aforo?.recargo_1 ?? 0),
    recargo_2: Number(props.aforo?.recargo_2 ?? 0),
    recargo_3: Number(props.aforo?.recargo_3 ?? 0),
    recargo_4: Number(props.aforo?.recargo_4 ?? 0),
    recargo_5: Number(props.aforo?.recargo_5 ?? 0),
})

const recargosCheck = reactive({
    incumplimiento: Number(form.recargo_1) > 0,
    entrega_doc: Number(form.recargo_2) > 0,
    error_doc: Number(form.recargo_3) > 0,
    limpio_libre: Number(form.recargo_4) > 0,
    proteccion: Number(form.recargo_5) > 0,
})

// ---------- Líneas de tarifa (precargadas si se edita) ----------
const lineaDesde = (i) => {
    const l = props.aforo?.lineas?.[i] || {}
    return {
        id_tipo_carga: l.id_tipo_carga ?? null,
        peso_cobrar: Number(l.peso_cobrar ?? 0),
        distancia: Number(l.distancia ?? 0),
        tarifa_mt: Number(l.tarifa_mt ?? 0),
        flete_mt: Number(l.flete_mt ?? 0),
        flete_mlc: Number(l.flete_mlc ?? 0),
        calculando: false,
    }
}

const lineas = reactive(Array.from({ length: 5 }, (_, i) => lineaDesde(i)))

// ---------- Indicadores 5 filas (precargadas si se edita) ----------
const indFilaDesde = (i) => {
    const f = props.aforo?.indFilas?.[i] || {}
    return {
        tn_pos: Number(f.tn_pos ?? 0),
        tn_real: Number(f.tn_real ?? 0),
        km_carga: Number(f.km_carga ?? 0),
        km_vacio: Number(f.km_vacio ?? 0),
        km_total: Number(f.km_total ?? 0),
        traf_pos: Number(f.traf_pos ?? 0),
        traf_real: Number(f.traf_real ?? 0),
    }
}

const indFilas = reactive(Array.from({ length: 5 }, (_, i) => indFilaDesde(i)))

// ---------- Visibilidad de filas extra (3-5) ----------
const mostrarLineasExtra = ref(false)
const mostrarIndExtra = ref(false)

// Solo las filas 1-2 visibles por defecto; 3-5 opcionales
const lineasVisibles = computed(() => mostrarLineasExtra.value ? lineas : lineas.slice(0, 2))
const indFilasVisibles = computed(() => mostrarIndExtra.value ? indFilas : indFilas.slice(0, 2))

// ---------- Datos de la CP seleccionada ----------
const cpOpciones = computed(() =>
    (props.cartasPendientes || []).map((c) => ({
        id: c.id,
        numero: c.numero,
        cliente: c.cliente?.nombre,
        tractivo: c.tractivo?.codigo,
        hr: c.hoja_ruta?.numero,
    }))
)

const cp = computed(() => {
    if (esEdicion.value) return props.cartaPreseleccionada || {}
    return props.cartasPendientes?.find((c) => c.id === form.id_carta_porte) || {}
})

const capacidad = computed(() => Number(cp.value.tractivo?.capacidad_toneladas || 0))
const monedaCliente = computed(() => props.monedas?.find((m) => m.id === form.id_moneda) || null)

// Derivación desde la hoja de ruta (tractivo/arrastre/choferes, solo lectura)
const hrSeleccionada = computed(() => props.hojasRuta?.find((h) => h.id === form.id_hoja_ruta) || null)
const tractivoCodigo = computed(() => {
    if (hrSeleccionada.value?.tractivo_codigo) return hrSeleccionada.value.tractivo_codigo
    return props.tractivos?.find((t) => t.id === form.id_tractivo)?.codigo || '—'
})
const arrastreCodigo = computed(() => {
    if (hrSeleccionada.value?.arrastre_codigo) return hrSeleccionada.value.arrastre_codigo
    return props.arrastres?.find((t) => t.id === form.id_arrastre)?.codigo || '—'
})
const choferNombre = computed(() => hrSeleccionada.value?.chofer_nombre || '—')
const chofer2Nombre = computed(() => hrSeleccionada.value?.chofer2_nombre || '—')

function onSeleccionarCarta() {
    const sel = props.cartasPendientes?.find((c) => c.id === form.id_carta_porte)
    if (!sel) return
    form.id_hoja_ruta = sel.id_hoja_ruta ?? null
    // Fase 4d: cliente/producto/tipo de la solicitud; equipo de la HR
    form.id_cliente = sel.cliente?.id ?? sel.solicitud?.id_cliente ?? null
    form.id_tractivo = sel.hojaRuta?.id_tractivo ?? null
    form.id_arrastre = sel.hojaRuta?.id_arrastre ?? null
    form.id_chofer = sel.hojaRuta?.id_chofer ?? null
    form.id_chofer2 = sel.hojaRuta?.id_chofer2 ?? null
    form.id_lugar_origen = sel.solicitud?.id_lugar_origen ?? null
    form.id_lugar_destino = sel.solicitud?.id_lugar_destino ?? null
    form.id_producto = sel.solicitud?.id_producto ?? sel.producto?.id ?? null
    form.id_tipo_carga = sel.solicitud?.id_tipo_carga ?? sel.tipo_carga?.id ?? null
    form.id_moneda = sel.solicitud?.id_moneda ?? 1
    form.distancia = Number(sel.distancia || 0)
    form.toneladas = Number(sel.toneladas || 0)
    form.conduce = sel.conduce || ''
    form.fecha_emision = toDate(sel.fecha_emision)
    form.fecha_recepcion = toDate(sel.fecha_recepcion)
    // En nuevo aforo la fecha de parte es la fecha de operaciones
    if (!esEdicion.value) {
        form.fecha_parte = props.fechaOperaciones ? toDate(props.fechaOperaciones) : new Date()
    }
    // Pre-carga línea 1
    lineas[0].id_tipo_carga = form.id_tipo_carga
    if (sel.distancia) lineas[0].distancia = Number(sel.distancia)
    if (sel.toneladas) lineas[0].peso_cobrar = Number(sel.toneladas)
    calcularTodas()
}

function onTractivo() {
    const t = props.tractivos?.find((x) => x.id === form.id_tractivo)
    if (t?.capacidad_toneladas) form.toneladas = Number(t.capacidad_toneladas)
}

// Al elegir la hoja de ruta se derivan tractivo/arrastre/choferes (solo lectura:
// para cambiarlos habría que cambiar la HR). El cliente viene de la solicitud.
function onSeleccionarHojaRuta() {
    const hr = props.hojasRuta?.find((h) => h.id === form.id_hoja_ruta)
    if (!hr) return
    form.id_tractivo = hr.id_tractivo ?? null
    form.id_arrastre = hr.id_arrastre ?? null
    form.id_chofer = hr.id_chofer ?? null
    form.id_chofer2 = hr.id_chofer2 ?? null
    form.toneladas = capacidad.value
    onTractivo()
    calcularTodas()
}

// ---------- Cálculo ----------
function round2(n) {
    return Math.round(Number(n || 0) * 100) / 100
}

async function api(url, data) {
    const resp = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(data),
    })
    return resp.json()
}

async function cotizarLinea(linea) {
    if (!linea.id_tipo_carga || !linea.distancia || !linea.peso_cobrar) return
    linea.calculando = true
    try {
        const data = await api(route('aforos.cotizar'), {
            moneda: form.id_moneda ?? 1,
            tipocarga: linea.id_tipo_carga,
            distancia: linea.distancia,
            peso: linea.peso_cobrar,
            capacidad: capacidad.value,
            descuento: 0,
            mlc: 0,
            tipocont: form.id_tipo_carga == 3 || form.id_tipo_carga == 4 ? 1 : 0,
            origen: form.id_lugar_origen || 0,
            destino: form.id_lugar_destino || 0,
            cliente: form.id_cliente || 0,
            producto: form.id_producto || 0,
        })
        linea.tarifa_mt = Number(data.tarmt || 0)
        linea.flete_mt = Number(data.fletemt || 0)
        linea.flete_mlc = Number(data.fletemlc || 0)
        calcularTotales()
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: e.message, life: 5000 })
    } finally {
        linea.calculando = false
    }
}

function calcularTodas() {
    lineas.forEach((l) => cotizarLinea(l))
}

// Enter en un InputNumber de línea: asegura que el v-model ya se actualizó
// antes de calcular (evita el bug de "solo funciona la 1ª vez").
async function calcularLineaConEnter(linea) {
    await nextTick()
    cotizarLinea(linea)
}

async function calcularAlmacenaje() {
    if (form.almacenaje_peso <= 0 || form.almacenaje_horas <= 0) return
    const data = await api(route('aforos.cotizar-almacenaje'), {
        alm_peso: form.almacenaje_peso,
        alm_horas: form.almacenaje_horas,
        descuento: 0,
        tipocarga: lineas[0].id_tipo_carga || 0,
        tipocont: form.id_tipo_carga == 3 || form.id_tipo_carga == 4 ? 1 : 0,
    })
    form.almacenaje_tarifa = Number(data.alm_tarifa || 0)
    form.almacenaje_flete = Number(data.alm_flete || 0)
    calcularTotales()
}

// Enter en almacenaje: asegura que el v-model ya se actualizó antes de calcular
async function calcularAlmacenajeEnter() {
    await nextTick()
    calcularAlmacenaje()
}

async function calcularDemora() {
    const horas = round2(Number(form.dem_carga) + Number(form.dem_descarga))
    if (horas <= 0) return
    const data = await api(route('aforos.cotizar-demora'), {
        tipocarga1: lineas[0].id_tipo_carga || 0,
        tipocarga2: lineas[1].id_tipo_carga || 0,
        capacidad: capacidad.value,
        demcarga: form.dem_carga,
        demdescarga: form.dem_descarga,
        descuento1: 0,
        descuento2: 0,
        horas,
        conttipo: 0,
    })
    form.tar_dem_1 = Number(data.tardem1 || 0)
    form.tar_dem_2 = Number(data.tardem2 || 0)
    if (Number(form.dem_carga) > 0) form.flete_dem_1 = Number(data.fdemcarga || 0)
    if (Number(form.dem_descarga) > 0) form.flete_dem_2 = Number(data.fdemdescarga || 0)
    form.dem_total = horas
    form.flete_demora = Number(data.fletedemt || 0)
    calcularTotales()
}

async function calcularTiempos() {
    const data = await api(route('aforos.cotizar-tiempos'), {
        otros: form.tiempo_otros,
        movimiento: form.tiempo_movimiento,
        carga: form.tiempo_carga,
        descarga: form.tiempo_descarga,
    })
    form.tiempo_total = Number(data.ttotal || 0)
}

// ---------- Salario: se propone una tasa (por rango) pero es seleccionable ----------
async function calcularSalario() {
    if (!lineas[0].id_tipo_carga) return
    const data = await api(route('aforos.cotizar-salario'), {
        tipocarga: lineas[0].id_tipo_carga,
        capacidad: capacidad.value,
        distancia: lineas[0].distancia,
        ingresos: form.ingreso_mt,
        almacenaje: form.almacenaje_flete,
        idchofer2: form.id_chofer2 || 0,
    })
    // Solo propone si aún no se ha seleccionado una tasa manualmente
    if (!form.id_tasa) {
        form.id_tasa = data.idtasa
        form.tasa = Number(data.tasa || 0)
        form.salario = Number(data.salario || 0)
    }
}

function onSelectTasa() {
    const t = props.tasas?.find((x) => x.id === form.id_tasa)
    if (t) {
        form.tasa = Number(t.tasa)
        form.salario = round2(Number(t.tasa) * form.ingreso_mt)
    }
}

// ---------- Indicadores ----------
function calcularIndicadores() {
    indFilas.forEach((f) => {
        f.km_total = round2(Number(f.km_carga) + Number(f.km_vacio))
    })
    const tipo = Number(form.tipo_indicadores)
    let kmCargaTotal, kmVacioTotal, tnPosTotal, tnRealTotal, trafPos = 0, trafReal = 0

    if (tipo === 4) {
        const f = indFilas[0]
        kmCargaTotal = f.km_carga; kmVacioTotal = f.km_vacio
        tnPosTotal = f.tn_pos; tnRealTotal = f.tn_real
        trafPos = f.traf_pos; trafReal = f.traf_real
    } else {
        if (tipo === 3) {
            kmCargaTotal = Math.max(...indFilas.map((f) => Number(f.km_carga || 0)))
            indFilas.forEach((f) => (f.km_total = round2(kmCargaTotal + Number(f.km_vacio))))
        } else {
            kmCargaTotal = round2(indFilas.reduce((s, f) => s + Number(f.km_carga || 0), 0))
        }
        kmVacioTotal = round2(indFilas.reduce((s, f) => s + Number(f.km_vacio || 0), 0))
        tnPosTotal = round2(indFilas.reduce((s, f) => s + Number(f.tn_pos || 0), 0))
        tnRealTotal = round2(indFilas.reduce((s, f) => s + Number(f.tn_real || 0), 0))
        indFilas.forEach((f) => {
            if (tipo === 2 && form.viajes > 0) {
                f.traf_pos = round2((Number(f.tn_pos) / form.viajes) * Number(f.km_carga))
                f.traf_real = round2((Number(f.tn_real) / form.viajes) * Number(f.km_carga))
            } else {
                f.traf_pos = round2(Number(f.tn_pos) * Number(f.km_carga))
                f.traf_real = round2(Number(f.tn_real) * Number(f.km_carga))
            }
            trafPos += f.traf_pos
            trafReal += f.traf_real
        })
    }
    return {
        km_carga_total: kmCargaTotal, km_vacio_total: kmVacioTotal,
        km_total_total: round2(kmCargaTotal + kmVacioTotal),
        tn_pos_total: tnPosTotal, tn_real_total: tnRealTotal,
        traf_pos_total: trafPos, traf_real_total: trafReal,
    }
}

// ---------- Totales ----------
const almacenajeTotal = computed(() => Number(form.almacenaje_flete || 0))
const fleteTotal = computed(() =>
    round2(lineas.reduce((s, l) => s + Number(l.flete_mt || 0), 0))
)
const fleteMlcTotal = computed(() => round2(lineas.reduce((s, l) => s + Number(l.flete_mlc || 0), 0)))
const demoraTotal = computed(() => Number(form.flete_demora || 0))
const demoraHorasTotal = computed(() => round2(Number(form.dem_carga || 0) + Number(form.dem_descarga || 0)))
// Otros = recargos + almacenaje (el usuario indica que el almacenaje se suma a otros y al ingreso total)
const otrosSinAlmacenaje = computed(() =>
    round2(Number(form.recargo_1) + Number(form.recargo_2) + Number(form.recargo_3) +
        Number(form.recargo_4) + Number(form.recargo_5))
)
const otrosTotal = computed(() => round2(otrosSinAlmacenaje.value + almacenajeTotal.value))
const ingresoTotal = computed(() => round2(fleteTotal.value + demoraTotal.value + otrosTotal.value))

function calcularTotales() {
    form.flete_mt = fleteTotal.value
    form.flete_mlc = fleteMlcTotal.value
    form.otros_mt = otrosTotal.value
    form.ingreso_mt = ingresoTotal.value
    calcularSalario()
}

function onRecargo(k, valor) {
    // Recargo 1 (INCUMPLIMIENTO): valor por rango de capacidad (paridad legacy 805/1680/2450)
    if (k === 1 && valor === undefined) {
        const cap = Number(capacidad.value || 0)
        valor = cap <= 10 ? 805 : (cap <= 20 ? 1680 : 2450)
    }
    form['recargo_' + k] = recargosCheck[mapRecargo(k)] ? valor : 0
    calcularTotales()
}
function mapRecargo(k) {
    return { 1: 'incumplimiento', 2: 'entrega_doc', 3: 'error_doc', 4: 'limpio_libre', 5: 'proteccion' }[k]
}

// ---------- Submit ----------
const TIPOS_ACUERDO = [22, 23] // ACUERDO/IMPORTE, ACUERDO/VIAJE (no requieren cálculo)

// Una línea requiere cálculo si tiene tipo, peso y kms y NO es acuerdo
function lineaRequiereCalculo(l) {
    if (!l.id_tipo_carga) return false
    if (TIPOS_ACUERDO.includes(Number(l.id_tipo_carga))) return false
    return true
}

function submit() {
    // Validar que cada línea esté calculada (flete > 0) salvo acuerdos
    const faltantes = []
    lineas.forEach((l, i) => {
        if (lineaRequiereCalculo(l) && Number(l.flete_mt) <= 0) {
            faltantes.push(`Línea ${i + 1}`)
        }
    })

    // Recalcular indicadores y salario antes de guardar
    calcularIndicadores()
    calcularTotales()

    if (faltantes.length > 0) {
        toast.add({ severity: 'warn', summary: 'Cálculo pendiente', detail: `Calcule las tarifas de: ${faltantes.join(', ')}`, life: 5000 })
        return
    }

    const tot = calcularIndicadores()
    const payload = {
        ...form,
        fecha_parte: form.fecha_parte ? String(form.fecha_parte.getFullYear()) + '-' + String(form.fecha_parte.getMonth() + 1).padStart(2, '0') + '-' + String(form.fecha_parte.getDate()).padStart(2, '0') : null,
        fecha_emision: form.fecha_emision ? String(form.fecha_emision.getFullYear()) + '-' + String(form.fecha_emision.getMonth() + 1).padStart(2, '0') + '-' + String(form.fecha_emision.getDate()).padStart(2, '0') : null,
        // La fecha de recepción no se muestra en el formulario; se pone igual a la fecha de parte
        fecha_recepcion: form.fecha_recepcion
            ? String(form.fecha_recepcion.getFullYear()) + '-' + String(form.fecha_recepcion.getMonth() + 1).padStart(2, '0') + '-' + String(form.fecha_recepcion.getDate()).padStart(2, '0')
            : (form.fecha_parte ? String(form.fecha_parte.getFullYear()) + '-' + String(form.fecha_parte.getMonth() + 1).padStart(2, '0') + '-' + String(form.fecha_parte.getDate()).padStart(2, '0') : null),
        fecha_carga: form.fecha_carga ? String(form.fecha_carga.getFullYear()) + '-' + String(form.fecha_carga.getMonth() + 1).padStart(2, '0') + '-' + String(form.fecha_carga.getDate()).padStart(2, '0') : null,
        fecha_descarga: form.fecha_descarga ? String(form.fecha_descarga.getFullYear()) + '-' + String(form.fecha_descarga.getMonth() + 1).padStart(2, '0') + '-' + String(form.fecha_descarga.getDate()).padStart(2, '0') : null,
        lineas: lineas.map((l) => ({
            id_tipo_carga: l.id_tipo_carga,
            peso_cobrar: l.peso_cobrar,
            distancia: l.distancia,
            tarifa_mt: l.tarifa_mt,
            flete_mt: l.flete_mt,
            flete_mlc: l.flete_mlc,
        })),
        indicadores: {
            viajes: form.viajes,
            tipo: form.tipo_indicadores,
            valores: 0,
            ...Object.fromEntries(indFilas.flatMap((f, i) => [
                [`tn_pos_${i + 1}`, f.tn_pos], [`tn_real_${i + 1}`, f.tn_real],
                [`km_carga_${i + 1}`, f.km_carga], [`km_vacio_${i + 1}`, f.km_vacio],
                [`km_total_${i + 1}`, f.km_total], [`traf_pos_${i + 1}`, f.traf_pos],
                [`traf_real_${i + 1}`, f.traf_real],
            ])),
            ...tot,
        },
    }

    const options = {
        onSuccess: () => toast.add({ severity: 'success', summary: esEdicion.value ? 'Aforo actualizado' : 'Aforo creado', life: 3000 }),
        onError: (e) => toast.add({ severity: 'error', summary: 'Error', detail: Object.values(e).join(', '), life: 5000 }),
    }

    if (esEdicion.value) {
        router.put(route('aforos.update', props.aforo.id), payload, options)
    } else {
        router.post(route('aforos.store'), payload, options)
    }
}

function numeroLabel(n) {
    return Number(n || 0).toLocaleString()
}
</script>

<template>
    <AppLayout :title="title">
        <div class="max-w-7xl mx-auto p-4">
            <h2 class="text-xl font-bold mb-4 text-surface-800 dark:text-surface-100">{{ esEdicion ? 'Editar Aforo' : 'Nuevo Aforo' }}</h2>
            <form @submit.prevent="submit" class="space-y-4">
                <!-- Selección de CP (solo en creación) -->
                <div v-if="!esEdicion" class="bg-surface-50 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 rounded-xl overflow-hidden">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-blue-50 dark:bg-blue-950/40 border-b border-surface-200 dark:border-surface-700">
                        <i class="pi pi-file-check text-blue-700 dark:text-blue-400"></i>
                        <h3 class="font-semibold text-blue-800 dark:text-blue-300">Carta de Porte (no aforada del mes)</h3>
                    </div>
                    <div class="p-4">
                        <Select v-model="form.id_carta_porte" :options="cpOpciones" option-value="id" option-label="numero"
                            :filter="true" filter-placeholder="Buscar CP..." placeholder="SELECCIONE LA CARTA PORTE..."
                            class="w-full" @change="onSeleccionarCarta" />
                    </div>
                </div>

                <!-- DATOS GENERALES: una sola sección, editables (momento de corregir) -->
                <div class="bg-surface-50 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 rounded-xl overflow-hidden">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-blue-50 dark:bg-blue-950/40 border-b border-surface-200 dark:border-surface-700">
                        <i class="pi pi-file-edit text-blue-700 dark:text-blue-400"></i>
                        <h3 class="font-semibold text-blue-800 dark:text-blue-300">Datos Generales</h3>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                            <div class="sm:col-span-2 lg:col-span-3 xl:col-span-4 flex items-center gap-2">
                                <label class="w-28 shrink-0 text-sm font-medium text-surface-600 dark:text-surface-300">CP No.</label>
                                <InputText :model-value="cp?.numero || (esEdicion ? form.id_carta_porte : '')" readonly class="w-full readonly-field" />
                            </div>
                            <div class="min-w-0 xl:col-span-2">
                                <label class="block mb-1 text-sm font-medium text-surface-600 dark:text-surface-300">Hoja de Ruta</label>
                                <Select v-model="form.id_hoja_ruta" :options="hojasRuta" option-value="id" option-label="numero"
                                    :filter="true" filter-placeholder="Buscar HR..." placeholder="SELECCIONE HOJA DE RUTA..."
                                    class="w-full" @change="onSeleccionarHojaRuta" />
                            </div>
                            <div class="min-w-0">
                                <label class="block mb-1 text-sm font-medium text-surface-600 dark:text-surface-300">Fecha de Parte</label>
                                <DatePicker v-model="form.fecha_parte" date-format="dd/mm/yy" class="w-full" />
                            </div>
                            <div class="min-w-0">
                                <label class="block mb-1 text-sm font-medium text-surface-600 dark:text-surface-300">Fecha Emisión</label>
                                <DatePicker v-model="form.fecha_emision" date-format="dd/mm/yy" class="w-full" />
                            </div>
                            <div class="min-w-0">
                                <label class="block mb-1 text-sm font-medium text-surface-600 dark:text-surface-300">Cliente</label>
                                <Select v-model="form.id_cliente" :options="clientes" option-value="id" option-label="nombre" filter placeholder="Cliente..." class="w-full" @change="calcularTodas" />
                            </div>
                            <div class="min-w-0">
                                <label class="block mb-1 text-sm font-medium text-surface-600 dark:text-surface-300">Tractivo</label>
                                <InputText :model-value="tractivoCodigo" readonly class="w-full readonly-field" />
                            </div>
                            <div class="min-w-0">
                                <label class="block mb-1 text-sm font-medium text-surface-600 dark:text-surface-300">Arrastre</label>
                                <InputText :model-value="arrastreCodigo" readonly class="w-full readonly-field" />
                            </div>
                            <div class="min-w-0">
                                <label class="block mb-1 text-sm font-medium text-surface-600 dark:text-surface-300">Chofer</label>
                                <InputText :model-value="choferNombre" readonly class="w-full readonly-field" />
                            </div>
                            <div class="min-w-0">
                                <label class="block mb-1 text-sm font-medium text-surface-600 dark:text-surface-300">Chofer 2</label>
                                <InputText :model-value="chofer2Nombre" readonly class="w-full readonly-field" />
                            </div>
                            <div class="min-w-0">
                                <label class="block mb-1 text-sm font-medium text-surface-600 dark:text-surface-300">Origen</label>
                                <Select v-model="form.id_lugar_origen" :options="lugares" option-value="id" option-label="nombre" filter placeholder="Origen..." class="w-full" @change="calcularTodas" />
                            </div>
                            <div class="min-w-0">
                                <label class="block mb-1 text-sm font-medium text-surface-600 dark:text-surface-300">Destino</label>
                                <Select v-model="form.id_lugar_destino" :options="lugares" option-value="id" option-label="nombre" filter placeholder="Destino..." class="w-full" @change="calcularTodas" />
                            </div>
                            <div class="min-w-0">
                                <label class="block mb-1 text-sm font-medium text-surface-600 dark:text-surface-300">Producto</label>
                                <Select v-model="form.id_producto" :options="productos" option-value="id" option-label="nombre" filter placeholder="Producto..." class="w-full" />
                            </div>
                            <div class="min-w-0">
                                <label class="block mb-1 text-sm font-medium text-surface-600 dark:text-surface-300">Moneda</label>
                                <Select v-model="form.id_moneda" :options="monedas" option-value="id" option-label="nombre" placeholder="Moneda..." class="w-full" />
                            </div>
                            <div class="min-w-0">
                                <label class="block mb-1 text-sm font-medium text-surface-600 dark:text-surface-300">Capacidad (t)</label>
                                <InputNumber :model-value="capacidad" readonly class="w-full readonly-field" />
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-surface-600 dark:text-surface-300">Conduce</label>
                                <InputText v-model="form.conduce" class="w-full" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calculo de la Tarifas -->
                <div class="bg-surface-50 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-blue-50 dark:bg-blue-950/40 border-b border-surface-200 dark:border-surface-700">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-table text-blue-700 dark:text-blue-400"></i>
                            <h3 class="font-semibold text-blue-800 dark:text-blue-300">Calculo de la Tarifas</h3>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-1.5 text-xs text-surface-600 dark:text-surface-300 cursor-pointer">
                                <Checkbox v-model="mostrarLineasExtra" binary :binary="true" />
                                Mostrar líneas 3-5
                            </label>
                            <Button label="Calcular todas" icon="pi pi-calculator" size="small" @click="calcularTodas" />
                        </div>
                    </div>
                    <div class="p-3">
                        <DataTable :value="lineasVisibles" striped-rows>
                            <Column field="id_tipo_carga" header="Tipo">
                                <template #body="{ data }">
                                    <Select v-model="data.id_tipo_carga" :options="tiposCarga" option-value="id" option-label="nombre" filter class="w-full" @change="cotizarLinea(data)" />
                                </template>
                            </Column>
                            <Column field="distancia" header="Kms">
                                <template #body="{ data }">
                                    <InputNumber v-model="data.distancia" :min="0" class="w-full text-right" @blur="cotizarLinea(data)" @keydown.enter.prevent="calcularLineaConEnter(data)" />
                                </template>
                            </Column>
                            <Column field="peso_cobrar" header="Peso (t)">
                                <template #body="{ data }">
                                    <InputNumber v-model="data.peso_cobrar" :min="0" class="w-full text-right" @blur="cotizarLinea(data)" @keydown.enter.prevent="calcularLineaConEnter(data)" />
                                </template>
                            </Column>
                            <Column field="tarifa_mt" header="Tar" class="text-right">
                                <template #body="{ data }">
                                    <span class="text-red-600 font-semibold">{{ numeroLabel(data.tarifa_mt) }}</span>
                                </template>
                            </Column>
                            <Column field="flete_mt" header="Flete MN" class="text-right">
                                <template #body="{ data }"><span class="font-medium">{{ numeroLabel(data.flete_mt) }}</span></template>
                            </Column>
                            <Column field="flete_mlc" header="CL" class="text-right">
                                <template #body="{ data }">{{ numeroLabel(data.flete_mlc) }}</template>
                            </Column>
                            <Column header="">
                                <template #body="{ data }">
                                    <Button icon="pi pi-calculator" text rounded severity="info" @click="cotizarLinea(data)" :loading="data.calculando" v-tooltip.top="'Calcular'" />
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </div>

                <!-- Almacenaje -->
                <div class="bg-surface-50 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 rounded-xl overflow-hidden">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-blue-50 dark:bg-blue-950/40 border-b border-surface-200 dark:border-surface-700">
                        <i class="pi pi-box text-blue-700 dark:text-blue-400"></i>
                        <h3 class="font-semibold text-blue-800 dark:text-blue-300">Almacenaje</h3>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div>
                                <label class="text-xs text-surface-500 block mb-1">Peso</label>
                                <InputNumber v-model="form.almacenaje_peso" :min="0" placeholder="PESO..." class="w-full text-right" @blur="calcularAlmacenaje" @keydown.enter.prevent="calcularAlmacenajeEnter" />
                            </div>
                            <div>
                                <label class="text-xs text-surface-500 block mb-1">Horas</label>
                                <InputNumber v-model="form.almacenaje_horas" :min="0" placeholder="HORAS..." class="w-full text-right" @blur="calcularAlmacenaje" @keydown.enter.prevent="calcularAlmacenajeEnter" />
                            </div>
                            <div>
                                <label class="text-xs text-surface-500 block mb-1">Tarifa</label>
                                <InputNumber v-model="form.almacenaje_tarifa" readonly class="w-full text-right readonly-field" />
                            </div>
                            <div>
                                <label class="text-xs text-surface-500 block mb-1">Flete</label>
                                <InputNumber v-model="form.almacenaje_flete" readonly class="w-full text-right readonly-field" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Demora -->
                <div class="bg-surface-50 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 rounded-xl overflow-hidden">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-blue-50 dark:bg-blue-950/40 border-b border-surface-200 dark:border-surface-700">
                        <i class="pi pi-clock text-blue-700 dark:text-blue-400"></i>
                        <h3 class="font-semibold text-blue-800 dark:text-blue-300">Demora</h3>
                    </div>
                    <div class="p-4 space-y-3">
                        <!-- Fila 1: CARGA (izquierda) / DESCARGA (derecha) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-semibold text-surface-500 block mb-1">CARGA</label>
                                <div class="grid grid-cols-[1fr_auto_auto] gap-2 items-center">
                                    <DatePicker v-model="form.fecha_carga" date-format="dd/mm/yy" class="w-full" />
                                    <InputText v-model="form.hora_carga_1" placeholder="H1" class="w-16" />
                                    <InputText v-model="form.hora_carga_2" placeholder="H2" class="w-16" @blur="calcularDemora" @keydown.enter.prevent="calcularDemora" />
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-surface-500 block mb-1">DESCARGA</label>
                                <div class="grid grid-cols-[1fr_auto_auto] gap-2 items-center">
                                    <DatePicker v-model="form.fecha_descarga" date-format="dd/mm/yy" class="w-full" />
                                    <InputText v-model="form.hora_descarga_1" placeholder="H1" class="w-16" />
                                    <InputText v-model="form.hora_descarga_2" placeholder="H2" class="w-16" @blur="calcularDemora" @keydown.enter.prevent="calcularDemora" />
                                </div>
                            </div>
                        </div>
                        <!-- Fila 2: horas carga, flete carga | horas descarga, flete descarga -->
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                            <div>
                                <label class="text-xs text-surface-500 block mb-1">Horas carga</label>
                                <InputNumber v-model="form.dem_carga" :min="0" @blur="calcularDemora" @keydown.enter.prevent="calcularDemora" class="w-full" />
                            </div>
                            <div>
                                <label class="text-xs text-surface-500 block mb-1">Flete carga</label>
                                <InputNumber v-model="form.flete_dem_1" readonly class="w-full bg-surface-100 dark:bg-surface-800" />
                            </div>
                            <div>
                                <label class="text-xs text-surface-500 block mb-1">Horas descarga</label>
                                <InputNumber v-model="form.dem_descarga" :min="0" @blur="calcularDemora" @keydown.enter.prevent="calcularDemora" class="w-full" />
                            </div>
                            <div>
                                <label class="text-xs text-surface-500 block mb-1">Flete descarga</label>
                                <InputNumber v-model="form.flete_dem_2" readonly class="w-full bg-surface-100 dark:bg-surface-800" />
                            </div>
                        </div>
                        <!-- Fila 3: feriado + totales de demora -->
                        <div class="grid grid-cols-2 sm:grid-cols-2 gap-3 items-end">
                            <div>
                                <label class="text-xs text-surface-500 block mb-1">Horas total</label>
                                <InputNumber :model-value="demoraHorasTotal" readonly class="w-full text-right readonly-field" />
                            </div>
                            <div>
                                <label class="text-xs text-surface-500 block mb-1">Flete total</label>
                                <InputNumber :model-value="demoraTotal" readonly class="w-full text-right readonly-field" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recargos (van antes de Salario porque sus importes alimentan el salario) -->
                <div class="bg-surface-50 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 rounded-xl overflow-hidden">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-blue-50 dark:bg-blue-950/40 border-b border-surface-200 dark:border-surface-700">
                        <i class="pi pi-plus-circle text-blue-700 dark:text-blue-400"></i>
                        <h3 class="font-semibold text-blue-800 dark:text-blue-300">Recargos</h3>
                    </div>
                    <div class="p-4 space-y-2">
                        <div class="flex items-center gap-2">
                            <Checkbox v-model="recargosCheck.incumplimiento" binary @change="onRecargo(1)" />
                            <span class="text-sm w-36 text-surface-700 dark:text-surface-200">INCUMP. CARGA?</span>
                            <InputNumber v-model="form.recargo_1" readonly class="w-24 text-right readonly-field" />
                        </div>
                        <div class="flex items-center gap-2">
                            <Checkbox v-model="recargosCheck.entrega_doc" binary @change="onRecargo(2, 210)" />
                            <span class="text-sm w-36 text-surface-700 dark:text-surface-200">ENTREGA DOC.?</span>
                            <InputNumber :model-value="form.recargo_2" readonly class="w-24 text-right readonly-field" />
                        </div>
                        <div class="flex items-center gap-2">
                            <Checkbox v-model="recargosCheck.error_doc" binary @change="onRecargo(3, 280)" />
                            <span class="text-sm w-36 text-surface-700 dark:text-surface-200">ERROR DOC.?</span>
                            <InputNumber :model-value="form.recargo_3" readonly class="w-24 text-right readonly-field" />
                        </div>
                        <div class="flex items-center gap-2">
                            <Checkbox v-model="recargosCheck.limpio_libre" binary @change="onRecargo(4, 210)" />
                            <span class="text-sm w-36 text-surface-700 dark:text-surface-200">LIMPIO/LIBRE?</span>
                            <InputNumber :model-value="form.recargo_4" readonly class="w-24 text-right readonly-field" />
                        </div>
                        <div class="flex items-center gap-2">
                            <Checkbox v-model="recargosCheck.proteccion" binary @change="onRecargo(5, 1750)" />
                            <span class="text-sm w-36 text-surface-700 dark:text-surface-200">PROT. CARGA?</span>
                            <InputNumber :model-value="form.recargo_5" readonly class="w-24 text-right readonly-field" />
                        </div>
                        <div class="flex items-center gap-2 pt-2 border-t border-surface-100 dark:border-surface-700">
                            <span class="font-semibold text-blue-800 dark:text-blue-300">OTROS TOTAL</span>
                            <InputNumber :model-value="otrosTotal" readonly class="w-24 text-right readonly-field" />
                        </div>
                    </div>
                </div>

                <!-- Salario (tiempos + feriado + coeficiente) -->
                <div class="bg-surface-50 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 rounded-xl overflow-hidden">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-blue-50 dark:bg-blue-950/40 border-b border-surface-200 dark:border-surface-700">
                        <i class="pi pi-money-bill text-blue-700 dark:text-blue-400"></i>
                        <h3 class="font-semibold text-blue-800 dark:text-blue-300">Salario</h3>
                    </div>
                    <div class="p-4 space-y-3">
                        <div>
                            <label class="text-xs font-semibold text-surface-500 block mb-1">TIEMPOS</label>
                            <div class="grid grid-cols-2 sm:grid-cols-6 gap-3 items-end">
                                <div><label class="text-xs text-surface-500 block">OTROS</label><InputNumber v-model="form.tiempo_otros" @blur="calcularTiempos" class="w-full" /></div>
                                <div><label class="text-xs text-surface-500 block">MOV</label><InputNumber v-model="form.tiempo_movimiento" @blur="calcularTiempos" class="w-full" /></div>
                                <div><label class="text-xs text-surface-500 block">CAR</label><InputNumber v-model="form.tiempo_carga" @blur="calcularTiempos" class="w-full" /></div>
                                <div><label class="text-xs text-surface-500 block">DES</label><InputNumber v-model="form.tiempo_descarga" @blur="calcularTiempos" class="w-full" /></div>
                                <div><label class="text-xs text-surface-500 block">TIEMPO</label><InputNumber v-model="form.tiempo_total" readonly class="w-full text-right readonly-field" /></div>
                                <div><label class="text-xs text-surface-500 block">FERIADO</label><InputNumber v-model="form.tiempo_feriado" class="w-full" /></div>
                            </div>
                        </div>
                        <div class="pt-3 border-t border-surface-100 dark:border-surface-700">
                            <label class="text-xs font-semibold text-surface-500 block mb-1">COEFICIENTE</label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                                <div>
                                    <label class="text-xs text-surface-500 block mb-1">Tasa</label>
                                    <Select v-model="form.id_tasa" :options="tasas" option-value="id" option-label="nombre"
                                        placeholder="SELECCIONE TASA..." filter class="w-full" @change="onSelectTasa" />
                                </div>
                                <div>
                                    <label class="text-xs text-surface-500 block mb-1">Coeficiente</label>
                                    <InputNumber v-model="form.tasa" :min="0" readonly :max-fraction-digits="6" class="w-full text-right readonly-field" />
                                </div>
                                <div>
                                    <label class="text-xs text-surface-500 block mb-1">Salario</label>
                                    <InputNumber v-model="form.salario" readonly class="w-full text-right readonly-field" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Indicadores -->
                <div class="bg-surface-50 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-blue-50 dark:bg-blue-950/40 border-b border-surface-200 dark:border-surface-700">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-chart-bar text-blue-700 dark:text-blue-400"></i>
                            <h3 class="font-semibold text-blue-800 dark:text-blue-300">Indicadores</h3>
                        </div>
                        <label class="flex items-center gap-1.5 text-xs text-surface-600 dark:text-surface-300 cursor-pointer">
                            <Checkbox v-model="mostrarIndExtra" binary :binary="true" />
                            Mostrar filas 3-5
                        </label>
                    </div>
                    <div class="p-4">
                        <div class="flex items-center gap-4 mb-3 flex-wrap">
                            <div class="flex gap-3 flex-wrap">
                                <label class="flex items-center gap-1 text-sm"><RadioButton v-model="form.tipo_indicadores" :value="1" @change="calcularIndicadores" /> Normal</label>
                                <label class="flex items-center gap-1 text-sm"><RadioButton v-model="form.tipo_indicadores" :value="2" @change="calcularIndicadores" /> /Viajes</label>
                                <label class="flex items-center gap-1 text-sm"><RadioButton v-model="form.tipo_indicadores" :value="3" @change="calcularIndicadores" /> Desag</label>
                                <label class="flex items-center gap-1 text-sm"><RadioButton v-model="form.tipo_indicadores" :value="4" @change="calcularIndicadores" /> Manual</label>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mb-3">
                            <label class="text-sm font-medium text-surface-600 dark:text-surface-300">Viajes</label>
                            <InputNumber v-model="form.viajes" :min="1" class="w-20" @blur="calcularIndicadores" @keydown.enter.prevent="calcularIndicadores" />
                        </div>
                        <DataTable :value="indFilasVisibles" size="small" striped-rows>
                            <Column field="tn_pos" header="TNPOS">
                                <template #body="{ data }"><InputNumber v-model="data.tn_pos" class="w-full" @blur="calcularIndicadores" @keydown.enter.prevent="calcularIndicadores" /></template>
                            </Column>
                            <Column field="tn_real" header="TNREAL">
                                <template #body="{ data }"><InputNumber v-model="data.tn_real" class="w-full" @blur="calcularIndicadores" @keydown.enter.prevent="calcularIndicadores" /></template>
                            </Column>
                            <Column field="km_carga" header="CARGA">
                                <template #body="{ data }"><InputNumber v-model="data.km_carga" class="w-full" @blur="calcularIndicadores" @keydown.enter.prevent="calcularIndicadores" /></template>
                            </Column>
                            <Column field="km_vacio" header="VACIO">
                                <template #body="{ data }"><InputNumber v-model="data.km_vacio" class="w-full" @blur="calcularIndicadores" @keydown.enter.prevent="calcularIndicadores" /></template>
                            </Column>
                            <Column field="km_total" header="TOTAL">
                                <template #body="{ data }">{{ data.km_total }}</template>
                            </Column>
                            <Column field="traf_pos" header="TFPOS">
                                <template #body="{ data }">{{ data.traf_pos }}</template>
                            </Column>
                            <Column field="traf_real" header="TFREAL">
                                <template #body="{ data }">{{ data.traf_real }}</template>
                            </Column>
                        </DataTable>
                    </div>
                </div>

                <!-- Totales -->
                <div class="grid grid-cols-2 sm:grid-cols-6 gap-3 p-4 bg-blue-50 dark:bg-blue-950/40 rounded-xl border border-blue-100 dark:border-blue-900">
                    <div><label class="block mb-1 font-medium text-blue-800 dark:text-blue-300 text-sm">FLETE MN</label><InputNumber :model-value="fleteTotal" readonly class="w-full" /></div>
                    <div><label class="block mb-1 font-medium text-blue-800 dark:text-blue-300 text-sm">FLETE MLC</label><InputNumber :model-value="fleteMlcTotal" readonly class="w-full" /></div>
                    <div><label class="block mb-1 font-medium text-blue-800 dark:text-blue-300 text-sm">ALMACENAJE</label><InputNumber :model-value="almacenajeTotal" readonly class="w-full" /></div>
                    <div><label class="block mb-1 font-medium text-blue-800 dark:text-blue-300 text-sm">DEMORA</label><InputNumber :model-value="demoraTotal" readonly class="w-full" /></div>
                    <div><label class="block mb-1 font-medium text-blue-800 dark:text-blue-300 text-sm">OTROS</label><InputNumber :model-value="otrosSinAlmacenaje" readonly class="w-full" /></div>
                    <div><label class="block mb-1 font-medium text-blue-800 dark:text-blue-300 text-sm">INGRESO (VENTAS)</label><InputNumber :model-value="ingresoTotal" readonly class="w-full" /></div>
                </div>

                <div class="flex gap-2 justify-end">
                    <Button label="Cancelar" severity="secondary" @click="router.get(route('aforos.index'))" />
                    <Button :label="esEdicion ? 'Guardar Cambios' : 'Guardar Aforo'" type="submit" icon="pi pi-save" />
                </div>
            </form>
        </div>
    </AppLayout>
</template>
