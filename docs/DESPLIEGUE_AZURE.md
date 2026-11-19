# Despliegue de EcoCiudad en Azure (imágenes web + app + db)

Todo el sistema se publica como **3 imágenes** en Azure Container Registry (ACR):

| Imagen | Contenido | Origen |
|---|---|---|
| `ecociudad-web` | Nginx (sirve estáticos y reenvía PHP) | `Dockerfile`, target `nginx` |
| `ecociudad-app` | PHP-FPM + Laravel (aplica migraciones al arrancar) | `Dockerfile`, target `app` |
| `ecociudad-db` | PostgreSQL 16 con la base de datos ya cargada | `Dockerfile.db` |

No hay contenedor `queue`: las notificaciones (`ReporteAtendido`, `TachoNivelAlerta`) no implementan `ShouldQueue`, se envían de forma síncrona.

## 1. Preparar (en tu PC)

```powershell
npm run build                                        # el Dockerfile copia public/build
copy .env.docker.example .env.docker                  # y completa APP_KEY, DB_PASSWORD, ADMIN_PASSWORD
docker compose --env-file .env.docker up -d --build   # prueba local en http://localhost:8080
.\docker\azure\exportar-bd.ps1                        # genera docker/backup/ecociudad_completo.sql
docker compose --env-file .env.docker build           # reconstruye ecociudad-db con ese volcado
```

`docker/backup/ecociudad_completo.sql` contiene datos personales: está en `.gitignore` y `.dockerignore`. Solo debe terminar dentro de la imagen `ecociudad-db`, que debe vivir en un registro **privado**.

## 2. Publicar y desplegar

Requiere Azure CLI (`az login`). Un solo comando crea el grupo de recursos, el ACR, sube las imágenes y despliega:

```powershell
.\docker\azure\desplegar-aci.ps1 -ResourceGroup rg-ecociudad -AcrName ecociudadacr -DnsLabel ecociudad-demo
```

- `-AcrName`: nombre único, solo letras y números.
- `-DnsLabel`: da la URL `http://<label>.<region>.azurecontainer.io:8080`.
- `-Location`: por defecto `brazilsouth`.
- `-SoloGenerarYaml`: no usa Azure; escribe el YAML para revisarlo (contiene secretos, bórralo).

Solo subir imágenes: `.\docker\azure\publicar-acr.ps1 -AcrName ecociudadacr`.

Los tres contenedores forman un **grupo de Azure Container Instances** y comparten red (`localhost`): por eso en Azure `DB_HOST=127.0.0.1` y Nginx usa `PHP_UPSTREAM=127.0.0.1:9000` (en Docker Compose el valor por defecto es `app:9000`).

## 3. Límites a tener en cuenta

- **Persistencia:** la base de datos y `storage/` (fotos de incidencias, documentos de contratos) usan volúmenes `emptyDir`. Si el grupo se detiene o se recrea, vuelve a los datos del volcado. Para uso real, usa Azure Database for PostgreSQL (`DB_HOST`, `DB_*` en el YAML y quitar el contenedor `db`) y Azure Files o Blob para `storage/`.
- **HTTPS:** ACI publica HTTP. Con un dominio propio, pon Azure Front Door o Application Gateway delante, cambia `APP_URL` a `https://...` y `SESSION_SECURE_COOKIE=true`.
- **Credenciales del registro:** el script usa el usuario administrador del ACR. Para producción usa una identidad administrada.
- **Primer arranque:** tarda 1–2 minutos (inicialización de PostgreSQL). Ver logs: `az container logs -g rg-ecociudad -n ecociudad --container-name app`.
