# Instalación de EMCARGA

Guía basada en despliegues reales, incluyendo entornos con restricciones de red (Cuba).

## Requisitos mínimos

- Docker Desktop (con WSL2 en Windows)
- 8 GB RAM, 20 GB disco
- VPN estable si estás en Cuba (indispensable para descargar paquetes)
- Git

## Puerto por defecto vs conflictivos

Por defecto la app usa `8080` para HTTP. Si tienes otro servicio usando ese puerto (IIS, XAMPP, etc.):

```bash
# Usar un puerto diferente
HTTP_PORT=56561 docker compose up -d
```

## 1. Clonar y configurar

```bash
git clone https://github.com/eidelzafiro/emcarga_new.git
cd emcarga_new
```

## 2. Variables de entorno

```bash
cp .env.example .env
```

Edita `.env` con los valores correctos para Docker:

```ini
APP_URL=http://localhost:8080        # Cambia el puerto si usas HTTP_PORT diferente
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=emcarga_new
DB_USERNAME=root
DB_PASSWORD=secret
```

> **⚠️ Importante**: `DB_HOST` debe ser `mysql` (nombre del servicio en docker-compose), NO `127.0.0.1`.

## 3. Generar APP_KEY

```bash
php artisan key:generate
```

Si no tienes PHP local, espera al paso 5 y ejecuta dentro del contenedor:

```bash
docker compose exec app php artisan key:generate
```

## 4. Compilar assets (Vite + Vue)

Los assets deben compilarse para que la página no se vea en blanco:

```bash
npm install
npm run build
```

Dentro del contenedor:

```bash
docker compose exec app npm install
docker compose exec app npm run build
```

> La página en blanco (código 200, HTML vacío) generalmente se debe a que falta `public/build/manifest.json`. Verifica: `ls -la public/build/manifest.json`.

## 5. Levantar contenedores

```bash
docker compose up -d
```

Esto inicia: PHP-FPM, Nginx, MySQL, Redis y Reverb (WebSocket).

## 6. Ejecutar migraciones

```bash
docker compose exec app php artisan migrate --force
```

## 7. Sembrar datos iniciales

```bash
docker compose exec app php artisan db:seed --force
```

Esto crea: usuario admin, roles, permisos, menú, catálogos base.

## 8. Verificar

```bash
docker compose exec app php artisan route:list
```

Abre `http://localhost:8080` en el navegador. Deberías ver la pantalla de login.

## Credenciales por defecto

Tras ejecutar los seeds:

- **Usuario**: ADMIN
- **Contraseña**: admin (cambiar en producción)

---

## Solución de problemas

### Página en blanco (código 200, sin contenido)

Causas más frecuentes (por orden de probabilidad):

1. **Assets no compilados**: Ejecuta `npm run build` y verifica que exista `public/build/manifest.json`.
2. **APP_URL incorrecta**: Debe coincidir con la URL real (puerto incluido). Ej: `http://localhost:8080`.
3. **Error de JavaScript**: Abre F12 (herramientas de desarrollo) → pestaña Console. Los errores JS no se ven en la terminal.
4. **Storage sin permisos**: `docker compose exec -u root app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache`.
5. **`.env` mal configurado**: `APP_KEY` vacía o `DB_HOST` apuntando a `127.0.0.1` en vez de `mysql`.
6. **Manifest.json corrupto**: Borra `public/build/` y re-ejecuta `npm run build`.
7. **Cache del navegador**: Forzar recarga con Ctrl+F5.

### Error SSL en Composer (curl error 56, SSL routines)

```bash
composer config --global secure-http false
composer config --global disable-tls true
composer config --global process-timeout 3600
composer install --prefer-source
```

### PHP version does not satisfy

El Dockerfile usa `php:8.4-fpm`. Verifica que no estés usando una imagen anterior:

```bash
# Debe decir PHP 8.4+
docker compose exec app php -v
```

### The "intl" PHP extension is required

Ya está instalada en el Dockerfile. Si usas PHP local, instala `php-intl`:

```bash
sudo apt install php8.4-intl   # Ubuntu/Debian
```

### Puerto ocupado

```bash
# Error: port is already allocated
HTTP_PORT=56561 docker compose up -d
MYSQL_PORT=3307 docker compose up -d
```

### Connection refused (MySQL)

```ini
# Incorrecto
DB_HOST=127.0.0.1

# Correcto (nombre del servicio Docker)
DB_HOST=mysql
```

### Operation not permitted (permisos)

```bash
docker compose exec -u root app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
```

### Vite manifest not found

```bash
docker compose exec app npm install
docker compose exec app npm run build
docker compose exec app ls -la public/build/manifest.json
```

### migrate:fresh falla por claves foráneas

```bash
docker compose exec app php artisan db:seed --force
```

Si sigue fallando, ejecuta las migraciones sin seeds:

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=UserSeeder --force
docker compose exec app php artisan db:seed --class=RoleSeeder --force
```

---

## Instalación desde cero (Cuba, con VPN)

```bash
# 1. VPN siempre activa

# 2. Clonar
git clone https://github.com/eidelzafiro/emcarga_new.git
cd emcarga_new

# 3. Configurar Composer para red lenta
composer config --global secure-http false
composer config --global disable-tls true
composer config --global process-timeout 3600

# 4. Variables de entorno
cp .env.example .env
# Editar .env: APP_URL, DB_HOST=mysql, DB_PASSWORD=secret

# 5. Generar key
php artisan key:generate

# 6. Construir y levantar
docker compose build --no-cache
docker compose up -d

# 7. Migrar
docker compose exec app php artisan migrate --force

# 8. Sembrar
docker compose exec app php artisan db:seed --force

# 9. Compilar assets (si no se compilaron en el build)
docker compose exec app npm install
docker compose exec app npm run build

# 10. Verificar
docker compose exec app ls -la public/build/manifest.json
```

La app estará en `http://localhost:8080`.

---

## Script automatizado (Windows PowerShell)

```powershell
# install.ps1
Write-Host "Instalando EMCARGA..." -ForegroundColor Cyan

# Verificar VPN
try {
    Invoke-WebRequest -Uri "https://github.com" -TimeoutSec 5 | Out-Null
} catch {
    Write-Host "Conecta la VPN primero" -ForegroundColor Red
    exit
}

composer config --global secure-http false
composer config --global disable-tls true

Copy-Item .env.example .env
php artisan key:generate

docker compose up -d

Write-Host "Esperando MySQL..." -ForegroundColor Yellow
Start-Sleep -Seconds 15

docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app npm install
docker compose exec app npm run build

Write-Host "Listo! http://localhost:8080" -ForegroundColor Green
```
