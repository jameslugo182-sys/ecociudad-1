<?php

namespace App\Http\Controllers;

use App\Models\AreaUnidad;
use App\Models\Cargo;
use App\Models\RolSistema;
use App\Models\SedeTrabajo;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MaestroContratoController extends Controller
{
    private const MODULOS = [
        'incidencias' => 'Incidencias registradas',
        'contratos' => 'Gestión de contratos',
        'vehiculos' => 'Vehículos',
        'rutas' => 'Gestión de rutas',
        'reportes' => 'Reportes',
        'operaciones' => 'Operaciones de limpieza',
    ];

    public function index(): RedirectResponse
    {
        return redirect()->route('administracion.contratos.maestros.roles.index');
    }

    public function cargos(): Response
    {
        return Inertia::render('Contratos/Maestros/Cargos', [
            'cargos' => Cargo::withCount('contratos')->orderBy('nombre')->get(),
        ]);
    }

    public function roles(): Response
    {
        return Inertia::render('Contratos/Maestros/Roles', [
            'roles' => RolSistema::orderBy('nombre')->get(),
            'modulos' => collect(self::MODULOS)
                ->map(fn ($label, $value) => compact('value', 'label'))
                ->values(),
        ]);
    }

    public function storeCargo(Request $request): RedirectResponse
    {
        Cargo::create($request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:cargos,nombre'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ]));

        return back()
            ->with('success', 'Cargo agregado al maestro.')
            ->with('registered', true);
    }

    public function updateCargo(Request $request, Cargo $cargo): RedirectResponse
    {
        $cargo->update($request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('cargos', 'nombre')->ignore($cargo)],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ]));

        return back()->with('success', 'Cargo actualizado.');
    }

    public function cambiarEstadoCargo(Request $request, Cargo $cargo): RedirectResponse
    {
        $cargo->update($request->validate(['activo' => ['required', 'boolean']]));

        return back()->with('success', 'Estado del cargo actualizado.');
    }

    public function areas(): Response
    {
        return Inertia::render('Contratos/Maestros/Catalogo', [
            'titulo' => 'Áreas / unidades',
            'descripcion' => 'Valores disponibles para el área de trabajo del contrato.',
            'placeholderNombre' => 'Nombre del área o unidad',
            'items' => AreaUnidad::withCount('contratos')->orderBy('nombre')->get(),
            'conteoLabel' => 'contratos asociados',
            'botonNuevo' => '+ Nueva área',
            'routeNames' => [
                'store' => 'administracion.contratos.maestros.areas.store',
                'update' => 'administracion.contratos.maestros.areas.update',
                'estado' => 'administracion.contratos.maestros.areas.estado',
            ],
        ]);
    }

    public function storeArea(Request $request): RedirectResponse
    {
        AreaUnidad::create($this->validarCatalogo($request, 'areas_unidad'));

        return back()->with('success', 'Área agregada al maestro.')->with('registered', true);
    }

    public function updateArea(Request $request, AreaUnidad $area): RedirectResponse
    {
        $area->update($this->validarCatalogo($request, 'areas_unidad', $area->id));

        return back()->with('success', 'Área actualizada.');
    }

    public function cambiarEstadoArea(Request $request, AreaUnidad $area): RedirectResponse
    {
        $area->update($request->validate(['activo' => ['required', 'boolean']]));

        return back()->with('success', 'Estado del área actualizado.');
    }

    public function sedes(): Response
    {
        return Inertia::render('Contratos/Maestros/Catalogo', [
            'titulo' => 'Sedes de trabajo',
            'descripcion' => 'Valores disponibles para la sede del contrato.',
            'placeholderNombre' => 'Nombre de la sede',
            'items' => SedeTrabajo::withCount('contratos')->orderBy('nombre')->get(),
            'conteoLabel' => 'contratos asociados',
            'botonNuevo' => '+ Nueva sede',
            'routeNames' => [
                'store' => 'administracion.contratos.maestros.sedes.store',
                'update' => 'administracion.contratos.maestros.sedes.update',
                'estado' => 'administracion.contratos.maestros.sedes.estado',
            ],
        ]);
    }

    public function storeSede(Request $request): RedirectResponse
    {
        SedeTrabajo::create($this->validarCatalogo($request, 'sedes_trabajo'));

        return back()->with('success', 'Sede agregada al maestro.')->with('registered', true);
    }

    public function updateSede(Request $request, SedeTrabajo $sede): RedirectResponse
    {
        $sede->update($this->validarCatalogo($request, 'sedes_trabajo', $sede->id));

        return back()->with('success', 'Sede actualizada.');
    }

    public function cambiarEstadoSede(Request $request, SedeTrabajo $sede): RedirectResponse
    {
        $sede->update($request->validate(['activo' => ['required', 'boolean']]));

        return back()->with('success', 'Estado de la sede actualizado.');
    }

    public function storeRol(Request $request): RedirectResponse
    {
        RolSistema::create($this->validarRol($request));

        return back()
            ->with('success', 'Rol agregado al maestro.')
            ->with('registered', true);
    }

    public function updateRol(Request $request, RolSistema $rol): RedirectResponse
    {
        $datos = $this->validarRol($request, $rol);

        if ($rol->protegido) {
            unset($datos['codigo']);
        }

        $rol->update($datos);

        return back()->with('success', 'Rol actualizado.');
    }

    public function cambiarEstadoRol(Request $request, RolSistema $rol): RedirectResponse
    {
        if ($rol->protegido) {
            throw ValidationException::withMessages([
                'rol' => 'Los roles base del sistema no se pueden desactivar.',
            ]);
        }

        $validated = $request->validate(['activo' => ['required', 'boolean']]);

        if (! $validated['activo'] && User::where('rol', $rol->codigo)->where('activo', true)->exists()) {
            throw ValidationException::withMessages([
                'rol' => 'Desactiva o reasigna las cuentas que utilizan este rol.',
            ]);
        }

        $rol->update($validated);

        return back()->with('success', 'Estado del rol actualizado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validarRol(Request $request, ?RolSistema $rol = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('roles_sistema', 'nombre')->ignore($rol)],
            'codigo' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('roles_sistema', 'codigo')->ignore($rol),
            ],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'modulos' => ['required', 'array', 'min:1'],
            'modulos.*' => ['required', Rule::in(array_keys(self::MODULOS))],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validarCatalogo(Request $request, string $tabla, ?int $ignorar = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique($tabla, 'nombre')->ignore($ignorar)],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
