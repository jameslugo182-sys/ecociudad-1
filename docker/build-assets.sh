#!/bin/sh
set -eu
# Compila el frontend (Vite) para copiarlo dentro de la imagen.
# En el servidor Linux suele ser más estable que npm ci dentro del Dockerfile.
docker run --rm \
    -v "$PWD":/app \
    -w /app \
    node:22-bookworm-slim \
    bash -c "npm ci --no-audit --no-fund && npm run build"
