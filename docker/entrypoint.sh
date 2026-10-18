#!/bin/sh
set -eu

cd /var/www/html

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
fi

if [ -n "${DB_HOST:-}" ]; then
    echo "Esperando PostgreSQL en ${DB_HOST}:${DB_PORT:-5432}..."
    i=0
    until php -r '
        $host = getenv("DB_HOST");
        $port = getenv("DB_PORT") ?: "5432";
        $db = getenv("DB_DATABASE");
        $user = getenv("DB_USERNAME");
        $pass = getenv("DB_PASSWORD") ?: "";
        try {
            new PDO("pgsql:host={$host};port={$port};dbname={$db}", $user, $pass, [
                PDO::ATTR_TIMEOUT => 3,
            ]);
            exit(0);
        } catch (Throwable $e) {
            exit(1);
        }
    '; do
        i=$((i + 1))
        if [ "$i" -ge 30 ]; then
            echo "No se pudo conectar a la base de datos."
            exit 1
        fi
        sleep 2
    done
fi

if [ -z "${APP_KEY:-}" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "APP_KEY está vacío. Cópialo desde .env.docker.example hacia .env y genera una clave antes de levantar el stack."
    exit 1
fi

php artisan storage:link --force >/dev/null 2>&1 || true

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

if [ "${SEED_ADMIN:-false}" = "true" ]; then
    php artisan db:seed --class=AdminUserSeeder --force
fi

if [ "${SEED_DEMO:-false}" = "true" ]; then
    php artisan db:seed --class=DemoEcoCiudadSeeder --force
fi

if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:cache
    php artisan view:cache
    php artisan event:cache
fi

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
fi

if [ "$(id -u)" = "0" ] && [ "${1:-}" != "php-fpm" ]; then
    exec su-exec www-data "$@"
fi

exec "$@"
