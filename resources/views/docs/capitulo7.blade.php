<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Capítulo 7. Arquitectura Tecnológica del Sistema</title>
    <style>
        @page { margin: 2.3cm 2.1cm 2.2cm 2.3cm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.48;
            color: #111;
        }
        h1 {
            font-size: 16px;
            text-align: center;
            margin: 0 0 10px;
        }
        h2 {
            font-size: 13px;
            margin: 18px 0 8px;
            padding-bottom: 3px;
            border-bottom: 0.6px solid #222;
            page-break-after: avoid;
        }
        h3 {
            font-size: 11.5px;
            margin: 12px 0 6px;
            page-break-after: avoid;
        }
        p { text-align: justify; margin: 0 0 8px; }
        .lead { margin-bottom: 14px; }
        .muted { color: #444; font-size: 10px; text-align: center; margin-bottom: 16px; }
        ul { margin: 0 0 10px 16px; }
        li { margin-bottom: 3px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 12px;
            font-size: 10px;
            page-break-inside: avoid;
        }
        th, td {
            border: 0.6px solid #333;
            padding: 5px 6px;
            vertical-align: top;
            text-align: left;
        }
        th { background: #eee; font-weight: bold; }
        pre {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 8px;
            line-height: 1.35;
            background: #f4f4f4;
            border: 0.6px solid #ccc;
            padding: 8px 9px;
            margin: 6px 0 12px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .flujo {
            text-align: center;
            font-family: DejaVu Sans Mono, monospace;
            font-size: 9px;
            line-height: 1.45;
            margin: 10px 0 14px;
        }
        .nota {
            font-size: 10px;
            border-top: 0.6px solid #999;
            padding-top: 8px;
            margin-top: 12px;
        }
    </style>
</head>
<body>
    <h1>Capítulo 7. Arquitectura Tecnológica del Sistema</h1>
    <div class="muted">EcoCiudad · Municipalidad Distrital de El Tambo</div>

    <p class="lead">
        Este capítulo describe las tecnologías utilizadas para implementar EcoCiudad y cómo interactúan entre sí.
        A diferencia de una arquitectura de frontend y backend separados, el sistema se construyó como una
        <strong>aplicación web única</strong>: Laravel 12 concentra la lógica de servidor, la autenticación y el
        acceso a datos, mientras que React 18 se incrusta en el mismo repositorio mediante Inertia.js. No existe
        un proyecto <em>frontend/</em> ni un proyecto <em>backend/</em> independientes, ni una API REST pública
        como canal principal de comunicación.
    </p>

    <h2>7.1. Enfoque arquitectónico</h2>
    <p>
        EcoCiudad sigue un modelo de aplicación monolítica modular. El navegador carga una sola plantilla Blade
        (<em>resources/views/app.blade.php</em>) que monta Inertia. Cada petición autenticada resuelve una ruta en
        <em>routes/web.php</em>, el controlador consulta modelos Eloquent o servicios de dominio y responde con el
        nombre de un componente React más un objeto de propiedades (<em>props</em>). Inertia hidrata esa página
        en el cliente sin convertir el sistema en una SPA desacoplada.
    </p>
    <p>
        La autenticación no usa JWT ni <em>localStorage</em>. Laravel Breeze gestiona sesión por cookies
        (<em>SESSION_DRIVER</em>), regenera el identificador al iniciar sesión y protege las mutaciones con el
        token CSRF (<em>X-XSRF-TOKEN</em>). Ziggy expone las rutas nombradas de Laravel al JavaScript, de modo que
        el cliente invoca <em>route('administracion.rutas.index')</em> en lugar de hardcodear URLs.
    </p>
    <p>Flujo de una petición típica:</p>
    <div class="flujo">
        Navegador (React + Inertia + Ziggy)<br>
        &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;visita Inertia / POST de formulario / JSON puntual<br>
        &nbsp;&nbsp;&nbsp;▼<br>
        Laravel 12 (middleware web, auth, role, module)<br>
        &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;controladores → servicios → Eloquent<br>
        &nbsp;&nbsp;&nbsp;▼<br>
        Base de datos (MySQL o SQLite según .env)<br>
        &nbsp;&nbsp;&nbsp;|<br>
        &nbsp;&nbsp;&nbsp;└── Trazado vial opcional: OSRM (Open Source Routing Machine)
    </div>

    <h2>7.2. Tecnologías de la capa de presentación</h2>
    <p>
        La interfaz se desarrolla en React 18, con componentes funcionales y Hooks, empaquetada por Vite 7 y
        estilizada con Tailwind CSS. Las páginas viven en <em>resources/js/Pages</em> y se resuelven por convención
        de Inertia (<em>Rutas/Cronograma</em> corresponde a <em>resources/js/Pages/Rutas/Cronograma.jsx</em>).
    </p>

    <h3>Estructura relevante del repositorio (capa de interfaz)</h3>
    <pre>ecociudad/
├── resources/
│   ├── css/app.css
│   ├── views/app.blade.php          # única plantilla raíz de Inertia
│   └── js/
│       ├── app.jsx                  # createInertiaApp + sincronización de sesión
│       ├── bootstrap.js             # Axios con cookies y CSRF
│       ├── sessionSync.js
│       ├── rutaMapa.js
│       ├── horarioRuta.js
│       ├── Components/
│       ├── Layouts/
│       │   ├── ModuleLayout.jsx     # menú lateral por módulo
│       │   ├── AuthenticatedLayout.jsx
│       │   └── GuestLayout.jsx
│       └── Pages/
│           ├── Welcome.jsx, Panel.jsx
│           ├── Auth/                # Login, Registro, recuperación de clave
│           ├── Reportes/            # ciudadano: listado y alta de incidencias
│           ├── Admin/               # mapa e incidencias de personal municipal
│           ├── Administracion/      # flota, equipos y mantenimiento
│           ├── Contratos/           # contratos, usuarios, maestros, horarios
│           ├── Rutas/               # planificación, horarios y cronograma
│           ├── Limpieza/            # operaciones de campo y evidencias
│           └── Profile/
├── package.json
└── vite.config.js</pre>

    <h3>Componentes y páginas principales</h3>
    <table>
        <thead>
            <tr>
                <th style="width:28%">Componente / página</th>
                <th>Responsabilidad</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>ModuleLayout</td>
                <td>Barra lateral compartida; resalta la ruta activa con Ziggy (<em>route().current</em>).</td>
            </tr>
            <tr>
                <td>Panel</td>
                <td>Tablero de módulos según el rol (incidencias, contratos, vehículos, rutas, reportes u operaciones).</td>
            </tr>
            <tr>
                <td>Pages/Reportes/Create</td>
                <td>Captura foto, tipo de incidencia, descripción y geolocalización del navegador; envía el alta con <em>useForm().post</em>.</td>
            </tr>
            <tr>
                <td>Pages/Admin/Mapa y RutaOperativaMapa</td>
                <td>Mapas Leaflet (<em>react-leaflet</em>) con incidencias, geometría de ruta y base municipal.</td>
            </tr>
            <tr>
                <td>Pages/Rutas/Index</td>
                <td>Planificación: incidencias específicas o propuestas de flota (OSRM) antes de persistir.</td>
            </tr>
            <tr>
                <td>Pages/Rutas/Horarios</td>
                <td>Asigna días y horas de recolección a una ruta activa.</td>
            </tr>
            <tr>
                <td>Pages/Rutas/Cronograma</td>
                <td>Calendario (Mes, Semana, Agenda, Día) de rutas que ya tienen horario.</td>
            </tr>
            <tr>
                <td>Pages/Limpieza/Recorrido</td>
                <td>Recorrido operativo: iniciar parada y registrar evidencia fotográfica.</td>
            </tr>
            <tr>
                <td>HorarioTurnosForm</td>
                <td>Editor semanal de turnos del personal (módulo de contratos).</td>
            </tr>
        </tbody>
    </table>

    <h3>Navegación</h3>
    <p>
        No se utiliza React Router. Las URLs las define Laravel en <em>routes/web.php</em> y <em>routes/auth.php</em>.
        El cliente navega con <em>Link</em> y <em>router</em> de Inertia, o con <em>route('nombre.de.ruta')</em> de Ziggy.
        El control de acceso ocurre en el servidor: middleware <em>auth</em>, <em>verified</em>, <em>role:</em> y
        <em>module:</em>. Un usuario inactivo o sin el rol o módulo adecuado recibe HTTP 403; no existe un
        <em>ProtectedRoute</em> en el cliente que decida a partir de un token.
    </p>
    <p>Grupos de rutas por actor:</p>
    <ul>
        <li>Ciudadano (<em>role:ciudadano</em>): <em>/reportes</em>, <em>/reportes/crear</em>.</li>
        <li>Administrador y personal con permisos de módulo: prefijo <em>/administracion</em> (incidencias, contratos, vehículos, rutas, métricas).</li>
        <li>Personal municipal (<em>role:personal_municipal</em>): prefijo <em>/admin</em>.</li>
        <li>Operaciones de limpieza (<em>module:operaciones</em>): prefijo <em>/limpieza</em>.</li>
    </ul>

    <h3>Comunicación con el servidor</h3>
    <p>
        La navegación y la mayoría de formularios viajan por visitas Inertia (mismo origen, sesión y CSRF). Axios
        queda configurado en <em>resources/js/bootstrap.js</em> para peticiones XHR puntuales —por ejemplo,
        <em>POST administracion.rutas.proponer</em>, que devuelve JSON de propuestas sin persistirlas— adjuntando
        cookies y el encabezado CSRF:
    </p>
    <pre>// resources/js/bootstrap.js
import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';</pre>
    <p>
        <em>HandleInertiaRequests</em> comparte en todas las páginas el usuario autenticado, el nombre del rol y
        los mensajes flash de sesión. Un listener en <em>sessionSync.js</em> redirige al login si otra pestaña cierra la sesión.
    </p>

    <h2>7.3. Tecnologías de la capa de servidor</h2>
    <p>
        El servidor se desarrolla en Laravel 12 (PHP 8.2 o superior). Expone rutas web, no un conjunto de
        controladores <em>Api/</em> sin estado. Las dependencias PHP relevantes son <em>inertiajs/inertia-laravel</em>,
        <em>tightenco/ziggy</em> y <em>barryvdh/laravel-dompdf</em> (formatos de contrato y horario en PDF).
    </p>

    <h3>Estructura de la capa de servidor</h3>
    <pre>ecociudad/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Reporte, Rutas, Contratos, Flota, Limpieza, Auth
│   │   ├── Middleware/      # HandleInertiaRequests, role, module
│   │   └── Requests/        # LoginRequest, ProfileUpdateRequest
│   ├── Models/              # User, Reporte, Camion, RutaRecoleccion, Contrato…
│   ├── Services/
│   │   ├── TrazadoRutaService.php       # OSRM y fallback en línea recta
│   │   ├── PlanificadorFlotaService.php # agrupación de incidencias por equipos
│   │   └── HorarioSemanal.php
│   └── Notifications/
├── bootstrap/app.php        # alias role y module; stack web de Inertia
├── database/migrations/
├── routes/web.php
├── routes/auth.php
└── tests/                   # PHPUnit (Feature y Unit)</pre>

    <h3>Controladores, modelos, servicios y middleware</h3>
    <table>
        <thead>
            <tr>
                <th style="width:16%">Capa</th>
                <th style="width:28%">Ejemplo</th>
                <th>Responsabilidad</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Controlador</td>
                <td>GestionRutasController</td>
                <td>Recibe la petición web, valida, orquesta servicios y responde con Inertia o JSON de previsualización. La persistencia de una ruta ocurre solo al asignar vehículo.</td>
            </tr>
            <tr>
                <td>Controlador</td>
                <td>ReporteController</td>
                <td>Alta de incidencias ciudadanas (foto, GPS, descripción) y listado del propio usuario.</td>
            </tr>
            <tr>
                <td>Modelo</td>
                <td>RutaRecoleccion</td>
                <td>Tabla <em>rutas_recoleccion</em>; relaciones con camión, generador y reportes; accessor <em>horario_resumen</em>.</td>
            </tr>
            <tr>
                <td>Modelo</td>
                <td>User</td>
                <td>Autenticación, rol, flag <em>activo</em> y vínculo opcional a <em>colaboradores</em>.</td>
            </tr>
            <tr>
                <td>Servicio</td>
                <td>TrazadoRutaService</td>
                <td>Ordena paradas y obtiene geometría por vías reales vía OSRM; si el servicio externo no responde, usa distancia en línea recta.</td>
            </tr>
            <tr>
                <td>Servicio</td>
                <td>PlanificadorFlotaService</td>
                <td>Agrupa incidencias según el número de equipos completos (1 conductor + 3 recolectores) sin grabar aún en base de datos.</td>
            </tr>
            <tr>
                <td>Middleware</td>
                <td>EnsureUserHasRole</td>
                <td>Exige rol exacto (<em>administrador</em>, <em>ciudadano</em>, <em>personal_municipal</em>, …) y usuario activo.</td>
            </tr>
            <tr>
                <td>Middleware</td>
                <td>EnsureUserCanAccessModule</td>
                <td>El administrador accede a todo; el resto según <em>roles_sistema.modulos</em> (incidencias, contratos, vehiculos, rutas, reportes, operaciones).</td>
            </tr>
        </tbody>
    </table>
    <p>
        El arranque en <em>bootstrap/app.php</em> registra Inertia en el grupo <em>web</em> y los alias <em>role</em>
        y <em>module</em>. No hay <em>routes/api.php</em> como canal funcional del producto.
    </p>
    <p>Autorización por rol y módulo (extracto):</p>
    <pre>// app/Http/Middleware/EnsureUserCanAccessModule.php
if ($user->rol === 'administrador') {
    return $next($request);
}
$rol = RolSistema::where('codigo', $user->rol)->where('activo', true)->first();
abort_unless($rol &amp;&amp; in_array($module, $rol->modulos ?? [], true), 403);</pre>
    <p>
        Los documentos PDF institucionales (formato de contrato y horario del trabajador) se generan en el servidor
        con DomPDF a partir de vistas Blade, no en el cliente.
    </p>

    <h2>7.4. Base de datos del sistema</h2>
    <p>
        Laravel se configura por variables de entorno (<em>.env</em>), nunca con credenciales en el código. El archivo
        <em>.env.example</em> usa SQLite por defecto; el entorno local de XAMPP puede apuntar a MySQL. Las pruebas
        automatizadas usan SQLite en memoria (<em>phpunit.xml</em>).
    </p>
    <pre># .env (ejemplo, sin valores reales)
DB_CONNECTION=mysql          # o sqlite en desarrollo/pruebas
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecociudad
DB_USERNAME=ecociudad_app
DB_PASSWORD=${DB_PASSWORD}

SESSION_DRIVER=database
FILESYSTEM_DISK=local</pre>

    <h3>Migraciones</h3>
    <p>Las tablas se crean con migraciones versionadas en <em>database/migrations/</em>, respetando claves foráneas:</p>
    <ul>
        <li><em>0001_01_01_000000_create_users_table.php</em> — usuarios, sesiones y restablecimiento de clave</li>
        <li><em>2026_09_08_044108_add_rol_to_users_table.php</em> — rol de aplicación</li>
        <li><em>2026_09_08_040859_create_reportes_table.php</em> — incidencias ciudadanas</li>
        <li><em>2026_09_08_155000_add_assignment_and_evidence_to_reportes_table.php</em> — asignación y evidencia</li>
        <li><em>2026_09_08_162000_create_camiones_and_teams_tables.php</em> — flota y <em>camion_user</em></li>
        <li><em>2026_09_08_203500_create_contract_management_tables.php</em> — cargos, roles, colaboradores, contratos</li>
        <li><em>2026_09_08_215000_create_rutas_recoleccion_tables.php</em> — rutas y <em>reporte_ruta</em></li>
        <li><em>2026_09_09_223000_add_geometria_to_rutas_recoleccion_table.php</em> — polilínea OSRM</li>
        <li><em>2026_09_10_001500_add_horario_to_rutas_recoleccion_table.php</em> — días y horas de recolección</li>
        <li><em>2026_09_10_003000_create_mantenimientos_vehiculo_tables.php</em></li>
        <li><em>2026_09_10_150000_expand_contratos_and_create_horarios_tables.php</em> — áreas, sedes, plantillas y horarios de personal</li>
    </ul>

    <h3>Relaciones Eloquent (extracto real)</h3>
    <pre>class Reporte extends Model {
    public function user() { return $this->belongsTo(User::class); }
    public function responsable() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function rutas() {
        return $this->belongsToMany(RutaRecoleccion::class, 'reporte_ruta')
            ->withPivot(['orden', 'distancia_desde_anterior_km']);
    }
}

class RutaRecoleccion extends Model {
    protected $table = 'rutas_recoleccion';
    public function camion() { return $this->belongsTo(Camion::class); }
    public function reportes() {
        return $this->belongsToMany(Reporte::class, 'reporte_ruta')
            ->orderByPivot('orden');
    }
}

class Camion extends Model {
    protected $table = 'camiones';
    public function personal() {
        return $this->belongsToMany(User::class)->withPivot('puesto');
    }
    public function tieneEquipoCompleto(): bool { /* 1 conductor + 3 recolectores */ }
}</pre>
    <p>
        Los catálogos de contratos (<em>cargos</em>, <em>roles_sistema</em>, <em>areas_unidad</em>, <em>sedes_trabajo</em>)
        alimentan los maestros de la interfaz y los permisos de módulo. El modelo <em>RolSistema</em> guarda en JSON
        la lista de módulos habilitados para cada rol operativo.
    </p>

    <h2>7.5. Infraestructura de desarrollo</h2>
    <p>
        El equipo trabaja en un entorno local Windows con XAMPP (PHP y MySQL) y Node.js. No se exige Docker para
        desarrollar: el servidor de aplicación se inicia con <em>php artisan serve</em> y los assets de React/Tailwind
        con <em>npm run dev</em> (Vite). En producción, Vite genera el bundle (<em>npm run build</em>) y Laravel
        entrega la aplicación como un solo sitio.
    </p>
    <table>
        <thead>
            <tr>
                <th style="width:32%">Elemento</th>
                <th>Herramienta</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Lenguaje de servidor</td><td>PHP 8.2 o superior, Laravel 12</td></tr>
            <tr><td>Interfaz</td><td>React 18, Inertia.js 2, Tailwind CSS, Vite 7</td></tr>
            <tr><td>Mapas y trazado</td><td>Leaflet / react-leaflet; OSRM para geometría por vías</td></tr>
            <tr><td>PDF institucionales</td><td>barryvdh/laravel-dompdf</td></tr>
            <tr><td>Servidor de desarrollo</td><td><em>php artisan serve</em> (p. ej. puerto 8000) + Vite (puerto 5173)</td></tr>
            <tr><td>Base de datos local</td><td>MySQL (XAMPP) o SQLite</td></tr>
            <tr><td>Editor</td><td>Visual Studio Code / Cursor</td></tr>
            <tr><td>Dependencias PHP</td><td>Composer (<em>composer.json</em> / <em>composer.lock</em>)</td></tr>
            <tr><td>Dependencias JS</td><td>npm (<em>package.json</em> / <em>package-lock.json</em>)</td></tr>
            <tr><td>Control de versiones</td><td>Git y GitHub</td></tr>
            <tr><td>Pruebas</td><td>PHPUnit 11 (suites Feature y Unit); SQLite en memoria</td></tr>
            <tr><td>Autenticación</td><td>Laravel Breeze (sesión, no JWT)</td></tr>
        </tbody>
    </table>
    <p>
        Las pruebas cubren autenticación, el flujo ciudadano-operativo (<em>EcoCiudadFlowTest</em>), la administración
        de flota y rutas (<em>AdministrationTest</em>) y los servicios de trazado y planificación
        (<em>TrazadoRutaServiceTest</em>, <em>PlanificadorFlotaServiceTest</em>).
    </p>
    <p class="nota">
        <strong>Nota.</strong> Todas las dependencias quedan fijadas en <em>composer.lock</em> y
        <em>package-lock.json</em>. Un <em>composer install</em> seguido de <em>npm install</em> y
        <em>php artisan migrate</em> reproduce el mismo código en cualquier máquina del equipo, sin separar el
        sistema en dos repositorios ni en dos servidores de aplicación.
    </p>
</body>
</html>
