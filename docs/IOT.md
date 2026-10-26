# Tachos IoT (ESP32)

La entidad registra **puntos fijos** (tachos designados) en un módulo propio. Un ESP32 con sensor ultrasónico (HC-SR04) mide la distancia tapa–residuo y la app calcula el % de llenado. Si cruza el umbral, se alerta al administrador y se puede **añadir ese punto a una ruta de camión ya planificada**.

La UI municipal sigue siendo Inertia + sesión. El ESP32 **no** usa login de usuario: lleva un token de dispositivo (hash en `tachos.token_hash`). No es JWT de la app.

## Piezas

| Pieza | Dónde |
| --- | --- |
| Módulo web | `TachoController`, `Pages/Tachos/{Index,Mapa}.jsx`, `module:tachos` |
| API del sensor | `POST /api/iot/tachos/{codigo}/lecturas` (`LecturaIotController`) |
| Dominio | `Tacho`, `LecturaTacho`, `NivelTachoService` |
| Alerta mail | `TachoNivelAlerta` a administradores activos |
| Incidencia reutilizable | `Reporte` tipo `Contenedor IoT` + `reportes.tacho_id` |
| Firmware de ejemplo | `firmware/esp32-tacho/esp32-tacho.ino` |

## Flujo

1. Administrador (o rol con módulo `tachos`) abre **Tachos IoT**, marca el punto en Leaflet, calibra vacío/lleno/umbral y guarda.
2. El sistema muestra **una sola vez** el token plano (flash `device_token`). Se copia al sketch.
3. El ESP32 envía `distancia_cm` cada 10 s con `Authorization: Bearer …`. El listado web recarga solo esos datos, sin refrescar la página.
4. `NivelTachoService` convierte distancia a %:  
   `clamp(100 * (vacio - distancia) / (vacio - lleno))`.
5. Al superar el umbral (histéresis: entra en umbral, sale en umbral−10) se abre o actualiza un `Reporte` `Contenedor IoT` y se manda correo.
6. En el listado, si hay alerta, se elige una ruta `Planificada` o `En curso` y se llama `trazarEnOrden` para **append** de la parada (no reordena las existentes).

## Autenticación del dispositivo

- Header: `Authorization: Bearer <token>` o `X-Device-Token`.
- El token plano no se guarda; solo el hash.
- Regenerar token invalida el anterior.
- Throttle: 30 lecturas/minuto por IP (`throttle:30,1`).
- CSRF no aplica: ruta en `routes/api.php`, no en `web.php`.

`APP_URL` debe ser alcanzable desde la red WiFi del ESP32. `http://127.0.0.1` en el firmware apunta al propio microcontrolador, no a tu PC. Usa la IP LAN (`192.168.…`) en desarrollo o el dominio público en servidor.

## Calibración

| Campo | Significado |
| --- | --- |
| `distancia_vacio_cm` | Sensor en la tapa hasta el fondo. Tacho de 75 cm: 75 |
| `distancia_lleno_cm` | Sensor hasta la superficie al tope. Con el HC-SR04 en la tapa: 5 |
| `umbral_alerta` | % a partir del cual hay alerta (default 80). ≥95% => estado `Lleno` |

Estados: `Operativo`, `Alerta`, `Lleno`, `Inactivo`.

## Qué no hacer

- No pongas JWT de usuarios en el ESP32.
- No expongas el token en logs de producción.
- No uses esta API desde Axios del panel: el módulo web habla Inertia.
- No crees un segundo SPA solo para IoT.

Detalle de ruteo vial al incorporar el tacho: [RUTAS_MAPAS.md](RUTAS_MAPAS.md).
