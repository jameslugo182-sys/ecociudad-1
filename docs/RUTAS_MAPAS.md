# Rutas, mapas y cronograma

Todo el trazado y la planificación viven en este repo. La UI usa Leaflet; el servidor habla con OSRM cuando puede.

## Piezas

| Pieza | Archivo |
| --- | --- |
| Mapa de incidencias | `Pages/Admin/Mapa.jsx`, `MapaController` |
| Planificación de rutas | `Pages/Rutas/Index.jsx`, `GestionRutasController` |
| Trazado vial / fallback | `app/Services/TrazadoRutaService.php` |
| Propuesta por flota (no guarda) | `app/Services/PlanificadorFlotaService.php` |
| Horarios de ruta | `Pages/Rutas/Horarios.jsx` |
| Calendario | `Pages/Rutas/Cronograma.jsx`, `resources/js/horarioRuta.js`, CSS `.cronograma-fc` |
| Recorrido operativo | `Pages/Limpieza/Recorrido.jsx` |
| Tachos IoT | `Pages/Tachos/{Index,Mapa}.jsx`, `TachoController`, `NivelTachoService` |

## Base geográfica

`TrazadoRutaService` usa una base fija en Huancayo (`BASE_LATITUD` / `BASE_LONGITUD`). Las paradas son incidencias con lat/lng, incluidas las de tachos IoT (`Contenedor IoT`).

## OSRM

- URL: `https://router.project-osrm.org` (servicio público).
- `trip` para ordenar y geometría; si falla, `route` con vecino más cercano.
- Timeout ~12 s. Si OSRM no responde o `code !== Ok`, **línea recta Haversine** y `por_vias = false`.
- Las pruebas fingen HTTP (`Http::fake`). No dependas de OSRM para que PHPUnit pase.

Si en el futuro hay un OSRM propio, se cambia la constante/base URL en el servicio; no se inventa un microservicio de ruteo en otro lenguaje.

## Crear una ruta

1. El camión debe tener equipo completo y no estar en mantenimiento.
2. Se eligen incidencias (o se aceptan propuestas).
3. `POST /administracion/rutas` (o `/admin/rutas`) persiste `RutaRecoleccion` en `Planificada`, pivot `reporte_ruta`, geometría.
4. `POST …/rutas/proponer` **solo calcula** (`PlanificadorFlotaService::proponer`) y devuelve JSON/props. Nada se escribe hasta `store`.

Un tacho en alerta se puede **añadir al final** de una ruta ya `Planificada` o `En curso` (`TachoController::agregarARuta` + `trazarEnOrden`). No reordena las paradas previas.

## Horario y cronograma

Campos en `rutas_recoleccion`:

- `hora_inicio` / `hora_fin` (se muestran `HH:MM`)
- `dias_recoleccion`: JSON de enteros ISO **1 = lunes … 7 = domingo** (no el `getDay()` de JS que empieza en domingo)
- `horario_nota` opcional

El cronograma pinta eventos a partir de `ruta.fecha` en los días de la semana marcados. Vistas: mes, semana, agenda, día. No instalamos FullCalendar; el look está en `resources/css/app.css`.

## Planificador de flota

Agrupa incidencias (k-means geográfico simple) en N vehículos con equipo completo. Cada grupo se traza con `TrazadoRutaService`. Sirve para **preview** en la pantalla de rutas.

## Mapas en React

- `react-leaflet` + CSS de Leaflet.
- Coordenadas en arrays `[lat, lng]` (Leaflet), no GeoJSON crudo en la UI.
- Iconos de parada: clases en `app.css` (`.ruta-parada-icono`).

## Qué no hacer

- No guardes otra copia de la polilínea en localStorage como fuente de verdad.
- No llames a Google Directions / Mapbox desde el cliente para el trazado oficial.
- No cambies `dias_recoleccion` a nombres (`"lunes"`) ni a índices 0–6 sin migrar cronograma y `horario_resumen`.
