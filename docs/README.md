# EcoCiudad — documentación de rumbo

Esta carpeta describe **lo que el proyecto es hoy**, no un diseño hipotético. Antes de agregar una librería, un repo extra o un patrón nuevo, léela.

Municipalidad Distrital de El Tambo (Huancayo). Plataforma web para incidencias ciudadanas, contratos, flota, rutas de recolección, operaciones de limpieza y tachos IoT.

## Cómo leer estos archivos

| Documento | Para qué sirve |
| --- | --- |
| [ARQUITECTURA.md](ARQUITECTURA.md) | Un solo repositorio, cómo viaja una petición, carpetas |
| [STACK.md](STACK.md) | Tecnologías que **sí** usamos y las que **no** vamos a introducir |
| [MODULOS_Y_ROLES.md](MODULOS_Y_ROLES.md) | Módulos, roles, middleware y pantallas |
| [DOMINIO.md](DOMINIO.md) | Modelos, estados, archivos subidos, seeders |
| [RUTAS_MAPAS.md](RUTAS_MAPAS.md) | Leaflet, OSRM, planificador de flota, cronograma |
| [DESARROLLO.md](DESARROLLO.md) | Cómo correrlo en XAMPP y cómo probar |
| [DESPLIEGUE_DOCKER.md](DESPLIEGUE_DOCKER.md) | Primera versión en servidor con Docker |
| [IOT.md](IOT.md) | ESP32, tachos fijos, lecturas y alerta a rutas |

PDFs académicos (`Capitulo_7_…`, estado del arte, plan de pruebas) son entregables de tesis. Si chocan con estos `.md`, **manda el código**.

## Regla corta

Front y back viven juntos: Laravel 12 + Inertia.js + React. Sesión y CSRF, no JWT de usuario. Rutas de Laravel y Ziggy, no React Router. El ESP32 usa token de dispositivo, no la sesión web.
