#!/bin/sh
# Genera la configuración de Nginx con el puerto que asigna Railway y delega en entrypoint.sh
# (espera a PostgreSQL, migra, cachea configuración y arranca supervisord).
set -eu

PORT="${PORT:-8080}"
export PORT
envsubst '${PORT}' < /etc/nginx/railway.conf.template > /etc/nginx/http.d/default.conf

exec /usr/local/bin/entrypoint.sh "$@"
