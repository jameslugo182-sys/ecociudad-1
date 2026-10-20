# Desarrollo local

Windows + XAMPP es el entorno de trabajo diario. Docker es para el servidor ([DESPLIEGUE_DOCKER.md](DESPLIEGUE_DOCKER.md)).

## Requisitos

- PHP 8.2+ (XAMPP) con `pdo_pgsql`, `gd`, `mbstring`, `zip`
- Composer
- Node 22 (o 18+) y npm
- PostgreSQL (en `.env` local suele ser `127.0.0.1:5432`)

## Arranque

```bash
composer install
copy .env.example .env   # o usa tu .env actual
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
npm install
```

Dos procesos:

```bash
php artisan serve
npm run dev
```

`npm run dev` deja Vite en `http://localhost:5173` y el plugin Laravel apunta a `APP_URL` (p. ej. `http://127.0.0.1:8000`).

Atajo del `composer.json`: `composer run dev` (serve + cola + pail + vite).

## Administrador y demo

En `.env`:

```
ADMIN_EMAIL=...
ADMIN_PASSWORD=...
```

`php artisan db:seed --class=AdminUserSeeder`

El seeder demo crea ciudadanos y operarios `*@ecociudad.pe` con contraseña `Demo2026!`. No lo uses como cuentas reales.

## Pruebas

```bash
php artisan test
```

PHPUnit usa SQLite en memoria, cola `sync`, sesión `array`. No necesita PostgreSQL.

Cobertura útil:

- `tests/Feature/EcoCiudadFlowTest.php` — ciudadano / asignación / evidencia
- `tests/Feature/AdministrationTest.php` — módulos, contratos, flota, rutas, cronograma
- `tests/Unit/TrazadoRutaServiceTest.php`
- `tests/Unit/PlanificadorFlotaServiceTest.php`
- `tests/Feature/IotTachoTest.php` — módulo tachos, API ESP32, alerta y append a ruta
- `tests/Feature/Auth/*` — Breeze

Si tocas permisos, rutas o Inertia, agrega o ajusta un test Feature. Si tocas OSRM/agrupación, un Unit con `Http::fake`.

## SonarQube (local, puerto 9000)

Configuración: `sonar-project.properties`. El token del proyecto **no se commitea**.

```powershell
$env:XDEBUG_MODE='coverage'
php artisan test --coverage-clover=coverage.xml
$env:SONAR_TOKEN='pega_aqui_el_token_sqp'
sonar-scanner.bat
```

Si pasas `-Dsonar.sources=.` el análisis mete `vendor/` y `node_modules/`. Déjalo en las propiedades (solo `app`, `resources`, `routes`, etc.). El reporte queda en http://localhost:9000.

## Estilo

```bash
vendor/bin/pint
```

JSX: componentes en PascalCase, páginas bajo `Pages/` con el mismo nombre que `Inertia::render`.

## Commits y secretos

- No subas `.env`, `.env.docker`, ni claves.
- `public/build` está en `.gitignore`; hay que `npm run build` antes de armar la imagen Docker.
- No hagas commit a menos que James lo pida en el chat.

## Archivos que no son el producto

- `docs/*.pdf` y `resources/views/docs/` — tesis / PDF capítulo 7.
- `README.md` de Laravel en la raíz se reemplazó por el índice del producto; el detalle está en esta carpeta.
