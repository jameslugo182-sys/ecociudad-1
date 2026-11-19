# Despliegue de EcoCiudad en Railway

Railway ejecuta **un contenedor por servicio** y no usa `docker-compose.yml`. Por eso el proyecto queda en **2 servicios**:

| Servicio en Railway | Qué es | Origen |
|---|---|---|
| **EcoCiudad** | Nginx + PHP-FPM en una sola imagen (supervisord) | `Dockerfile.railway` (lo detecta `railway.json`) |
| **Postgres** | Base de datos administrada por Railway | Plantilla «PostgreSQL» de Railway |

No se usan `Dockerfile`, `Dockerfile.db` ni `docker-compose.yml` (son para Docker local y Azure). Tampoco hay `queue`: las notificaciones se envían de forma síncrona.

`Dockerfile.railway` compila el frontend (Vite) dentro de la propia construcción, porque `public/build` no está en git.

## 1. Subir el código a GitHub

Railway construye desde el repositorio (`jameslugo182-sys/ecociudad-1`, rama `main` o la que elijas). Deben estar en el repo: `railway.json`, `Dockerfile.railway` y `docker/railway/`.

## 2. Crear el proyecto

1. En Railway: **New Project → Deploy PostgreSQL**.
2. **Cargar tus datos antes de desplegar la app.** En el servicio Postgres, pestaña *Variables*, copia `DATABASE_PUBLIC_URL`. En tu PC (con Docker abierto):

   ```powershell
   .\docker\azure\exportar-bd.ps1        # si aún no tienes docker/backup/ecociudad_completo.sql
   .\docker\railway\cargar-bd.ps1 -DatabaseUrl "postgresql://postgres:CLAVE@HOST.proxy.rlwy.net:PUERTO/railway"
   ```

   El volcado trae estructura, datos y la tabla `migrations`; las contraseñas de los usuarios son las mismas que en local. Si prefieres empezar sin datos, omite este paso y pon `SEED_ADMIN=true` y `ADMIN_PASSWORD` en la app (crea solo el administrador).
3. **New → GitHub Repo →** elige el repositorio. Railway lee `railway.json` y usa `Dockerfile.railway`.

## 3. Variables del servicio EcoCiudad

Pestaña *Variables* → *Raw Editor*:

```
APP_NAME=EcoCiudad
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...                      # php artisan key:generate --show
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_PE

DB_CONNECTION=pgsql
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_DATABASE=${{Postgres.PGDATABASE}}
DB_USERNAME=${{Postgres.PGUSER}}
DB_PASSWORD=${{Postgres.PGPASSWORD}}

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
MAIL_MAILER=log

RUN_MIGRATIONS=true
SEED_ADMIN=false
```

`Postgres` en las referencias es el nombre del servicio de base de datos; si lo renombraste, cámbialo. Railway inyecta `PORT` por sí mismo (Nginx escucha en ese puerto).

## 4. Dominio, volumen y comprobación

- **Dominio:** servicio EcoCiudad → *Settings → Networking → Generate Domain*. La URL es HTTPS; Railway termina el TLS y la app ya confía en el proxy.
- **Volumen (recomendado):** *Settings → Volumes → Add Volume* con *mount path* `/var/www/html/storage`. Sin él, las fotos de incidencias y los documentos de contratos se pierden en cada despliegue.
- **Salud:** Railway consulta `/up` (configurado en `railway.json`). Logs en la pestaña *Deployments*.

## 5. Comprobado en local

La imagen `Dockerfile.railway` se construyó y se ejecutó contra PostgreSQL con `PORT=3000`: `/up`, `/login` y los assets responden 200, las migraciones no tienen nada pendiente con la base cargada (16 de 16) y el contenedor consume unos 70 MB en reposo. `cargar-bd.ps1` se probó restaurando el volcado en una base vacía (11 usuarios, 16 migraciones).

## 6. Límites

- **Costos:** Railway cobra por uso (hay créditos de prueba y luego plan de pago); revisa el plan vigente.
- **Cambios de esquema:** con `RUN_MIGRATIONS=true` cada despliegue ejecuta `php artisan migrate --force`.
- **Datos personales:** el volcado no debe subirse a git (está en `.gitignore`); solo se carga con `cargar-bd.ps1`.
