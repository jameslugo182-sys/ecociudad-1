# EcoCiudad

Plataforma municipal (Distrito de El Tambo, Huancayo) para incidencias ciudadanas, contratos, flota, rutas de recolección, operaciones de limpieza y tachos IoT.

**Un solo repositorio:** Laravel 12 + Inertia.js + React 18. No hay frontend y backend separados.

## Rumbo (léelo antes de codear)

Toda la documentación viva está en [`docs/`](docs/README.md):

- [Arquitectura](docs/ARQUITECTURA.md)
- [Stack y decisiones](docs/STACK.md)
- [Módulos y roles](docs/MODULOS_Y_ROLES.md)
- [Dominio y datos](docs/DOMINIO.md)
- [Rutas, mapas y cronograma](docs/RUTAS_MAPAS.md)
- [Desarrollo local](docs/DESARROLLO.md)
- [Tachos IoT / ESP32](docs/IOT.md)

## Arranque rápido

```bash
composer install
npm install
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve
npm run dev
```

Hace falta PostgreSQL y un `.env` con `APP_KEY`, `DB_*` y, para el admin, `ADMIN_EMAIL` / `ADMIN_PASSWORD`.

```bash
php artisan test
```
