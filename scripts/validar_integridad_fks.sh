#!/usr/bin/env bash
# ============================================================================
# Validación de integridad referencial — emcarga_new (P2.2)
#
# Recorre todas las FKs declaradas en el esquema y cuenta filas huérfanas
# (hijo con valor NOT NULL que no existe en la tabla padre).
#
# Uso:
#   ./scripts/validar_integridad_fks.sh               # reporte completo
#   ./scripts/validar_integridad_fks.sh --solo-malas  # solo FKs con huérfanos
#
# Ejecutar DESPUÉS de cada corrida ETL. Cualquier FK con huérfanos > 0
# indica: mapeo ETL incompleto, orden de migración incorrecto, o data
# quality legacy (documentar, no corregir a ciegas).
# ============================================================================
set -euo pipefail
cd "$(dirname "$0")/.."

DB="emcarga_new"
MYSQL="docker compose exec -T -e MYSQL_PWD=secret mysql mysql -uroot -N -B"
SOLO_MALAS="${1:-}"
TMP_CONSULTAS=$(mktemp /tmp/fks_queries.XXXXXX.sql)
TMP_RESULTADOS=$(mktemp /tmp/fks_results.XXXXXX.tsv)
trap 'rm -f "$TMP_CONSULTAS" "$TMP_RESULTADOS"' EXIT

# 1) Genera un SELECT de huérfanos por cada FK declarada (uno por línea)
$MYSQL "$DB" <<'SQL' | sed 's/$/;/' > "$TMP_CONSULTAS"
SELECT CONCAT(
  'SELECT ''', TABLE_NAME, '.', COLUMN_NAME, ' -> ', REFERENCED_TABLE_NAME, '.', REFERENCED_COLUMN_NAME, ''' AS fk, COUNT(*) AS huerfanas ',
  'FROM `', TABLE_NAME, '` h ',
  'LEFT JOIN `', REFERENCED_TABLE_NAME, '` p ON h.`', COLUMN_NAME, '` = p.`', REFERENCED_COLUMN_NAME, '` ',
  'WHERE h.`', COLUMN_NAME, '` IS NOT NULL AND p.`', REFERENCED_COLUMN_NAME, '` IS NULL'
)
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'emcarga_new'
  AND REFERENCED_TABLE_NAME IS NOT NULL
  AND REFERENCED_TABLE_SCHEMA = 'emcarga_new'
ORDER BY TABLE_NAME;
SQL

# 2) Ejecuta todas las consultas de una vez (salida: fk <TAB> huerfanas)
$MYSQL "$DB" < "$TMP_CONSULTAS" > "$TMP_RESULTADOS"

# 3) Reporte
total=$(wc -l < "$TMP_RESULTADOS")
malas=$(awk -F'\t' '$2 > 0' "$TMP_RESULTADOS" | wc -l)

echo "==================================================================="
echo " INTEGRIDAD REFERENCIAL — ${DB} — $(date '+%Y-%m-%d %H:%M')"
echo "==================================================================="
printf "%-58s %12s\n" "FK" "HUÉRFANAS"
echo "-------------------------------------------------------------------"

if [ "$SOLO_MALAS" = "--solo-malas" ]; then
  awk -F'\t' '$2 > 0 {printf "%-58s %12d ⚠\n", $1, $2}' "$TMP_RESULTADOS"
else
  awk -F'\t' '{printf "%-58s %12d%s\n", $1, $2, ($2 > 0 ? " ⚠" : "")}' "$TMP_RESULTADOS"
fi

echo "-------------------------------------------------------------------"
echo "FKs evaluadas: ${total} | Con huérfanos: ${malas}"
if [ "$malas" -gt 0 ]; then
  echo "ESTADO: ❌ Hay ${malas} FKs con filas huérfanas — revisar antes del piloto"
  exit 1
else
  echo "ESTADO: ✅ Integridad referencial limpia"
fi
