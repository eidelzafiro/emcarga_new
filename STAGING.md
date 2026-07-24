# Guía de Deploy a Staging (Docker)

## 1. Requisitos en el servidor

- Docker Engine 24+ y Docker Compose plugin (`docker compose version`)
- Git (para clonar)
- Puerto 8080 libre (Nginx)
- RAM recomendada: 4 GB mínimo

## 2. Clonar y preparar

```bash
git clone <url-del-repo> emcarga-staging
cd emcarga-staging
```

## 3. Configurar entorno Docker

Copiar el `.env.docker` como `.env`:

```bash
cp .env.example .env
```

Editar `.env` con estos cambios CLAVE:

| Variable | Valor | Motivo |
|---|---|---|
| `DB_HOST` | `mysql` | Nombre del servicio Docker |
| `DB_DATABASE` | `emcarga_new` | BD nueva de la app |
| `DB_USERNAME` | `root` | Usuario por defecto del contenedor |
| `DB_PASSWORD` | `secret` | Coincide con `MYSQL_ROOT_PASSWORD` |
| `LEGACY_DB_HOST` | `mysql` | Mismo contenedor MySQL |
| `LEGACY_DB_DATABASE` | `emcarga` | BD legacy (dump.sql) |
| `LEGACY_DB_USERNAME` | `root` | |
| `LEGACY_DB_PASSWORD` | `secret` | |
| `APP_ENV` | `production` | Modo producción |
| `APP_DEBUG` | `false` | Sin debug en staging |
| `APP_URL` | `http://<ip-del-servidor>:8080` | URL pública |

## 4. Construir y arrancar

```bash
docker compose build --no-cache
docker compose up -d
```

Esto arranca: Nginx (puerto 8080), PHP-FPM, MySQL con dump legacy, Redis, Reverb.

**Verificar que los contenedores están corriendo:**

```bash
docker compose ps
```

## 5. Migrar y cargar datos

Ejecutar dentro del contenedor `app`:

```bash
# Migraciones (crea estructura emcarga_new)
docker compose exec app php artisan migrate --force

# Seeders (roles, permisos, menú, notificaciones)
docker compose exec app php artisan db:seed --force

# ETL (carga datos desde emcarga → emcarga_new)
# --no-fresh: no reinicia la BD (ya corremos migrate + seed manual)
docker compose exec app php artisan emcarga:etl --no-fresh
```

## 6. Verificar

```bash
# Validar conteos
docker compose exec app php artisan emcarga:etl --validar

# Probar que responde HTTP
curl -I http://localhost:8080

# Ver logs si hay errores
docker compose logs app
docker compose logs nginx
```

## 7. Acceder

- **App**: `http://<ip-servidor>:8080`
- **Reverb WS**: `http://<ip-servidor>:8080` (puerto 8080)

## 8. Comandos útiles

| Acción | Comando |
|---|---|
| Ver logs de todos los servicios | `docker compose logs -f` |
| Detener | `docker compose down` |
| Destruir todo (incluye BD) | `docker compose down -v` |
| Reiniciar BD desde cero | `docker compose down -v && docker compose up -d` |
| Terminal dentro del contenedor | `docker compose exec app bash` |
| Ver tablas en MySQL | `docker compose exec mysql mysql -proot -e "USE emcarga_new; SHOW TABLES;"` |
