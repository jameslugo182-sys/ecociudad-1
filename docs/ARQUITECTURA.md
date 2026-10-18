# Arquitectura

EcoCiudad es una **aplicación monolítica** en un único repositorio. PHP renderiza la sesión y los datos; React pinta las páginas. No hay un frontend desplegado por separado. La única API JSON es la de **dispositivos IoT** (`/api/iot/…`), no la del panel.

## Cómo viaja una petición

```
Navegador (React 18 + Inertia + Ziggy + Leaflet)
        │  visita Inertia / axios (cookie de sesión + X-XSRF-TOKEN)
        ▼
Laravel 12  →  routes/web.php
        │  middleware: web, auth, role, module
        ▼
Controlador  →  Eloquent  →  PostgreSQL
        │
        ├── Inertia::render('Pages/…')  →  resources/views/app.blade.php
        │                                 + @vite + @routes (Ziggy)
        ├── redirect + flash
        └── PDF (DomPDF) o archivo en storage/app/public

ESP32 (ultrasónico)
        │  POST JSON + Bearer de dispositivo (sin sesión)
        ▼
Laravel 12  →  routes/api.php  →  LecturaIotController
```

El HTML raíz es `resources/views/app.blade.php`. Cada pantalla es un componente en `resources/js/Pages/…`. Inertia hidrata props del controlador; no hay React Router.

## Autenticación y seguridad

- Laravel Breeze (stack React/Inertia): login, registro, perfil, reset de contraseña.
- Sesión en base de datos (`SESSION_DRIVER=database`).
- CSRF: cookie `XSRF-TOKEN` y encabezado `X-XSRF-TOKEN` (ver `resources/js/bootstrap.js`).
- Usuarios inactivos no pasan `role` ni `module`.
- El administrador (`users.rol = administrador`) entra a todos los módulos sin fila en `roles_sistema`.
- Detrás de Nginx/Docker se confía en proxies (`bootstrap/app.php`) y, si `APP_URL` es `https://`, se fuerza el esquema HTTPS.

Sanctum está en `composer.json` por el esqueleto Laravel; **el flujo de la app no usa tokens SPA/JWT**. El ESP32 usa un token de dispositivo hasheado (`tachos.token_hash`), que no es sesión de usuario. Ver [IOT.md](IOT.md).

## Capas del código

| Capa | Dónde |
| --- | --- |
| Rutas HTTP | `routes/web.php`, `routes/auth.php`, `routes/api.php` (solo IoT) |
| Controladores | `app/Http/Controllers/` |
| Middleware propio | `EnsureUserHasRole`, `EnsureUserCanAccessModule`, `HandleInertiaRequests` |
| Dominio / persistencia | `app/Models/` |
| Servicios | `TrazadoRutaService`, `PlanificadorFlotaService`, `HorarioSemanal`, `NivelTachoService` |
| Páginas UI | `resources/js/Pages/` |
| Layouts / componentes | `resources/js/Layouts/`, `resources/js/Components/` |
| Estilos | Tailwind en `resources/css/app.css` |
| Migraciones / seeders | `database/` |
| Pruebas | `tests/Feature`, `tests/Unit` |

## Prefijos de URL

| Prefijo | Quién |
| --- | --- |
| `/` | Landing pública |
| `/login`, `/register`, `/profile` | Auth Breeze |
| `/dashboard` | Panel de módulos según rol |
| `/reportes` | Solo `ciudadano` |
| `/administracion/…` | Módulos municipales (`module:…`); dashboard extra exige `administrador` |
| `/admin/…` | Compatibilidad para `personal_municipal` (mismo controlador de incidencias/rutas) |
| `/limpieza/…` | Operarios con módulo `operaciones` |
| `/api/iot/…` | ESP32: lecturas de tacho (token de dispositivo) |
| `/up` | Healthcheck Laravel |

El panel abre cada módulo en una pestaña nueva (`Panel.jsx`). Cerrar sesión en una pestaña avisa a las demás (`sessionSync.js`).

## Infraestructura

- **Local:** PHP (XAMPP o `php artisan serve`) + `npm run dev` (Vite) + PostgreSQL.
- **Servidor:** Docker Compose — Nginx, PHP-FPM, worker de colas, PostgreSQL. Ver [DESPLIEGUE_DOCKER.md](DESPLIEGUE_DOCKER.md).
- **Colas / caché / sesión:** driver `database` (no Redis obligatorio).
- **Archivos:** disco `public` (`storage/app/public` + `php artisan storage:link`).
- **Mapas:** Leaflet en el cliente; trazado vial opcional vía OSRM público (ver [RUTAS_MAPAS.md](RUTAS_MAPAS.md)).

## Qué no es esta arquitectura

- No hay carpetas `frontend/` y `backend/` como dos proyectos.
- No hay SPA autenticada contra una API JSON con JWT (el panel es Inertia). La API `/api/iot` es solo para el ESP32.
- No hay microservicios. Un proceso PHP atiende la web; otro (opcional en Docker) consume `jobs`.
