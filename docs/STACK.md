# Stack y decisiones

Lista de lo que **está en el repo** y de lo que **no vamos a meter** sin una razón fuerte. Si alguien propone “separar el React” o “pasar a Next/JWT”, este archivo es la respuesta.

## Lo que usamos

### Backend

| Pieza | Versión / nota |
| --- | --- |
| PHP | ^8.2 (imagen Docker: 8.3) |
| Laravel | 12 |
| Inertia Laravel | 2 |
| Ziggy | 2 — `route('nombre')` en JS |
| DomPDF | `barryvdh/laravel-dompdf` — contratos y horarios en PDF |
| Eloquent / migraciones | PostgreSQL en local y Docker; SQLite en memoria en PHPUnit |

### Frontend (mismo repo)

| Pieza | Versión / nota |
| --- | --- |
| React | 18 |
| Inertia React | 2 — `Link`, `router`, `useForm`, `usePage` |
| Vite | 7 + `laravel-vite-plugin` |
| Tailwind CSS | 3 |
| Headless UI | 2 |
| Axios | cookies + CSRF; no es un cliente de API REST de negocio |
| Leaflet + react-leaflet | mapas de incidencias y recorridos |

Entrada Vite: `resources/js/app.jsx`. Páginas: `resources/js/Pages/**/*.jsx`.

### Datos y runtime

| Pieza | Decisión actual |
| --- | --- |
| Base de datos | PostgreSQL (`DB_CONNECTION=pgsql`) |
| Sesión | `database` |
| Cola | `database` (`queue:work` en Docker) |
| Caché | `database` en app; `array` en tests |
| Mail | `log` en desarrollo; notificación `ReporteAtendido` por correo |
| Tests | PHPUnit 11 — Feature + Unit |
| Lint PHP | Laravel Pint (dev) |

## Lo que no usamos (a propósito)

| Tentación | Por qué no |
| --- | --- |
| React Router / Next.js | La navegación es Inertia + `routes/web.php` |
| JWT / Passport como auth principal | Hay cookie de sesión y CSRF. Excepción: token de dispositivo del ESP32 ([IOT.md](IOT.md)) |
| Axios hacia `/api/…` como contrato de UI | Los controladores del panel devuelven Inertia o redirects |
| Repos `frontend` + `backend` | Un clone, un deploy |
| Redis obligatorio | Sesión, caché y cola ya van en PostgreSQL |
| Cypress / Playwright | Hoy la red de seguridad es PHPUnit |
| FullCalendar (npm) | El cronograma es CSS propio (`.cronograma-fc`) + helpers en `horarioRuta.js` |
| TypeScript | El JS/JSX actual es JavaScript |
| Docker Compose para el día a día en Windows | XAMPP + Vite; Docker es para el servidor |

Sanctum y Sail están en Composer porque vienen del esqueleto. No construyas features nuevas encima de ellos salvo que el equipo lo acuerde y actualice este archivo.

## Cómo agregar algo nuevo

1. ¿Se puede hacer con Laravel + Inertia + Eloquent? Hazlo así.
2. ¿Es un mapa o geometría? Leaflet en el cliente; trazado en `TrazadoRutaService`.
3. ¿Es un PDF? DomPDF + vista Blade.
4. ¿Es un permiso? Un código en `roles_sistema.modulos` y `module:clave` en la ruta. No armes un sistema de ACL paralelo.
5. Nueva dependencia npm/composer: anótala aquí el mismo día.

## Variables de entorno que importan

Plantillas: `.env.example` (local) y `.env.docker.example` (servidor). Nunca commits de `.env`.

- `APP_URL` — debe coincidir con la URL pública (incluye puerto si no es 80/443). El ESP32 debe alcanzar esa URL desde su WiFi, no `127.0.0.1`.
- `APP_KEY` — obligatoria.
- `ADMIN_EMAIL` / `ADMIN_PASSWORD` — seeder del administrador.
- `DB_*` — en Docker, `DB_HOST` lo pisa compose a `db`.
- `SEED_DEMO` — datos de prueba; en producción `false`.
