<?php

namespace App\Http\Controllers;

use App\Models\RolSistema;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $modulos = match ($request->user()->rol) {
            'administrador' => $this->modulosAdministrador(),
            'area_limpieza' => [
                $this->modulo('Operaciones asignadas', 'Consulta las tareas de limpieza y registra evidencias.', 'vehiculos', route('limpieza.dashboard')),
            ],
            'ciudadano' => [
                $this->modulo('Incidencias Registradas', 'Consulta el estado de tus reportes o registra uno nuevo.', 'incidencias', route('reportes.index')),
            ],
            default => $this->modulosDelRol($request->user()->rol),
        };

        return Inertia::render('Panel', [
            'modulos' => $modulos,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function modulo(string $nombre, string $descripcion, string $icono, string $url): array
    {
        return compact('nombre', 'descripcion', 'icono', 'url');
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function catalogoModulos(): array
    {
        return [
            'incidencias' => $this->modulo('Incidencias Registradas', 'Consulta y supervisa las incidencias enviadas por los ciudadanos.', 'incidencias', route('administracion.incidencias.index')),
            'contratos' => $this->modulo('Gestión de contratos', 'Administra contratos, cuentas, cargos y roles del personal.', 'contratos', route('administracion.contratos.index')),
            'vehiculos' => $this->modulo('Vehículos', 'Registra la flota y conforma los equipos de cada vehículo.', 'vehiculos', route('administracion.vehiculos.index')),
            'rutas' => $this->modulo('Gestión de rutas', 'Visualiza incidencias, equipos y la planificación territorial.', 'rutas', route('administracion.rutas.index')),
            'reportes' => $this->modulo('Reportes', 'Revisa indicadores y métricas operativas del sistema.', 'reportes', route('administracion.reportes.index')),
            'operaciones' => $this->modulo('Operaciones asignadas', 'Consulta las tareas de limpieza y registra evidencias.', 'vehiculos', route('limpieza.dashboard')),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function modulosAdministrador(): array
    {
        $catalogo = $this->catalogoModulos();

        return collect(['incidencias', 'contratos', 'vehiculos', 'rutas', 'reportes'])
            ->map(fn (string $modulo) => $catalogo[$modulo])
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function modulosDelRol(string $codigoRol): array
    {
        $rol = RolSistema::where('codigo', $codigoRol)
            ->where('activo', true)
            ->first();
        $permisos = $rol?->modulos ?? [];
        $catalogo = $this->catalogoModulos();

        return collect($permisos)
            ->filter(fn (string $modulo) => isset($catalogo[$modulo]))
            ->map(fn (string $modulo) => $catalogo[$modulo])
            ->values()
            ->all();
    }
}
