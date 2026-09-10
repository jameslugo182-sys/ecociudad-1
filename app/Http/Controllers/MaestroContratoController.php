<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use App\Models\RolSistema;
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
}
