# Despliegue de EcoCiudad con Docker

Índice de rumbo: [README.md](README.md). EcoCiudad es un solo proyecto (Laravel 12 + Inertia/React). Esta configuración levanta Nginx, PHP-FPM, un worker de colas y PostgreSQL.

No uses el `.env` de XAMPP en el servidor: ese archivo apunta a `127.0.0.1` y suele tener `APP_DEBUG=true`.

## Requisitos en el servidor

- Docker Engine 24+ y Docker Compose v2
- Puertos libres: `8080` por defecto (`APP_PORT`). En un servidor dedicado puedes usar `APP_PORT=80`. No lo pongas en `80` si Apache de XAMPP ya ocupa ese puerto.
- Git, o copiar el código (sin `vendor` ni `node_modules`)
- Frontend compilado en `public/build` (Vite). En el servidor, el camino más estable es:

```bash
chmod +x docker/build-assets.sh docker/entrypoint.sh
./docker/build-assets.sh
```

En tu PC con Node ya instalado: `npm ci && npm run build`.

## Primera subida

```bash
cd /opt/ecociudad   # o la ruta que uses
cp .env.docker.example .env
```

Edita `.env` y completa al menos:

- `APP_URL` — URL pública, por ejemplo `http://192.168.1.10:8080` o `https://ecociudad.tudominio.pe`
- `APP_KEY` — genera una:

```bash
echo "base64:$(openssl rand -base64 32)"
```

- `DB_PASSWORD` — contraseña fuerte de PostgreSQL
- `ADMIN_EMAIL` y `ADMIN_PASSWORD` — usuario administrador inicial (`SEED_ADMIN=true`)

Luego:

```bash
./docker/build-assets.sh          # si aún no existe public/build/manifest.json
docker compose up -d --build
docker compose ps
docker compose logs -f app
```

La app queda en `http://SERVIDOR:APP_PORT`. El healthcheck interno es `/up`.

`SEED_ADMIN=true` solo crea o actualiza el administrador. Déjalo en `false` después del primer arranque si no quieres que un restart reescriba la contraseña desde `.env`.

`SEED_DEMO=true` carga datos de prueba (ciudadanos y operarios demo). En un servidor real déjalo en `false`.

## HTTPS

Si hay un proxy (Caddy, Nginx, Traefik) terminando TLS:

1. Pon `APP_URL=https://tu-dominio`
2. Pon `SESSION_SECURE_COOKIE=true`
3. Laravel forzará HTTPS solo cuando `APP_URL` empiece por `https://`

Hasta que tengas certificado, deja HTTP y `SESSION_SECURE_COOKIE=false` o el login no guardará la cookie.

## Actualizar una versión nueva

```bash
git pull
npm ci && npm run build    # o ./docker/build-assets.sh
docker compose up -d --build
```

Las migraciones se ejecutan al arrancar el servicio `app`. Las fotos, contratos y evidencias viven en el volumen `laravel_storage` y no se pierden al reconstruir la imagen.

## Comandos útiles

```bash
docker compose logs -f web app queue db
docker compose exec app php artisan about
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=AdminUserSeeder --force
docker compose restart queue
```

PostgreSQL no se publica fuera de Docker a propósito. No abras el puerto `5432` en el firewall.

## Datos persistentes

| Volumen | Contenido |
| --- | --- |
| `postgres_data` | Base de datos |
| `laravel_storage` | Uploads (`storage/app`) y logs |

Copia de seguridad mínima:

```bash
docker compose exec -T db pg_dump -U ecociudad ecociudad > backup.sql
docker run --rm -v ecociudad_laravel_storage:/data -v "$PWD":/backup alpine tar czf /backup/storage.tgz -C /data .
```

## Qué no hacer

- No copies `.env` de tu PC de desarrollo.
- No pongas `APP_DEBUG=true` en producción.
- No hagas commit de `.env` ni de contraseñas.
