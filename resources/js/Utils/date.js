/**
 * Formato de fechas para toda la aplicación (dd/mm/yyyy).
 */

/**
 * Convierte un valor (Date, string ISO 'YYYY-MM-DD', string 'YYYY-MM-DD HH:MM:SS' o timestamp)
 * a un objeto Date. Devuelve null si no se puede parsear.
 */
export function toDate(value) {
    if (!value) return null
    if (value instanceof Date) return isNaN(value.getTime()) ? null : value
    if (typeof value === 'number') return new Date(value)

    const str = String(value)
    // ISO: YYYY-MM-DD o YYYY-MM-DD HH:MM:SS
    const m = str.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{1,2}):(\d{2}))?/)
    if (m) {
        const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]), Number(m[4] || 0), Number(m[5] || 0))
        return isNaN(d.getTime()) ? null : d
    }
    const d = new Date(str)
    return isNaN(d.getTime()) ? null : d
}

/**
 * Formatea a dd/mm/yyyy. Devuelve '' si no hay valor.
 */
export function formatDate(value) {
    const d = toDate(value)
    if (!d) return ''
    const dd = String(d.getDate()).padStart(2, '0')
    const mm = String(d.getMonth() + 1).padStart(2, '0')
    return `${dd}/${mm}/${d.getFullYear()}`
}

/**
 * Formatea a dd/mm/yyyy hh:mm. Devuelve '' si no hay valor.
 */
export function formatDateTime(value) {
    const d = toDate(value)
    if (!d) return ''
    const hh = String(d.getHours()).padStart(2, '0')
    const mi = String(d.getMinutes()).padStart(2, '0')
    return `${formatDate(d)} ${hh}:${mi}`
}
