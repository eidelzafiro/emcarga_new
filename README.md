# EMCARGA - Sistema de Gestión de Transporte

Sistema de gestión empresarial para transporte de carga, migrado de CodeIgniter 3 a Laravel + Vue.js.

## Requisitos

- PHP 8.2+
- MySQL 8.0+
- Node.js 20+
- Composer
- Docker (opcional)

## Instalación

### Instalación local

```bash
# Clonar repositorio
git clone <repository-url>
cd emcarga-new

# Instalar dependencias
make install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Configurar base de datos en .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=emcarga
DB_USERNAME=root
DB_PASSWORD=

# Ejecutar migraciones
php artisan migrate

# Iniciar servidor de desarrollo
php artisan serve
```

### Instalación con Docker

```bash
# Iniciar contenedores
docker-compose up -d

# Instalar dependencias
docker-compose exec app composer install
docker-compose exec app npm install

# Configurar entorno
cp .env.example .env
docker-compose exec app php artisan key:generate

# Ejecutar migraciones
docker-compose exec app php artisan migrate

# Acceder a la aplicación
http://localhost:8080
```

## Desarrollo

### Comandos disponibles

```bash
make help           # Ver todos los comandos disponibles
make install        # Instalar dependencias
make dev            # Iniciar servidor de desarrollo
make test           # Ejecutar pruebas
make lint           # Ejecutar linters
make docker-up      # Iniciar Docker
make docker-down    # Detener Docker
```

### Estructura del proyecto

```
emcarga-new/
├── app/
│   ├── Http/Controllers/    # Controladores
│   ├── Models/              # Modelos Eloquent
│   └── ...
├── database/
│   └── migrations/          # Migraciones de BD
├── resources/
│   └── js/
│       ├── Pages/           # Páginas Vue
│       ├── Components/      # Componentes Vue
│       └── Layouts/         # Layouts
├── routes/
│   └── web.php              # Rutas web
├── docker/                  # Configuración Docker
└── .github/workflows/       # CI/CD
```

## CI/CD

El pipeline automatizado ejecuta:

1. **Lint**: PHPStan, Pint, ESLint
2. **Test**: PHPUnit con cobertura
3. **Build**: Compilación de assets
4. **Docker**: Construcción de imagen

### Configurar secrets en GitHub

- `DB_PASSWORD`: Contraseña de MySQL
- `DOCKER_USERNAME`: Usuario de Docker Hub
- `DOCKER_PASSWORD`: Contraseña de Docker Hub

## Módulos

- **Técnico**: Gestión de flota vehicular
- **Comercial**: Clientes, contratos, facturación
- **Recursos Humanos**: Nómina, plantilla
- **Contabilidad**: Asientos, conciliaciones
- **Operaciones**: Solicitudes, hoja de ruta

## Licencia

Propietario - EMCARGA/Carbocuba
