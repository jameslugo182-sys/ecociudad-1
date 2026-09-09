<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\RolSistema;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CuentaColaboradorController extends Controller
{
    public function index(): Response
    {
        $contratoRegistrado = fn ($query) => $query
            ->where('estado', '!=', 'Cancelado')
            ->latest('fecha_inicio');

        return Inertia::render('Contratos/Usuarios', [
            'disponibles' => Colaborador::query()
                ->whereDoesntHave('usuario')
                ->whereHas('contratos', $contratoRegistrado)
                ->with(['contratos' => fn ($query) => $contratoRegistrado($query)->with('cargo:id,nombre')])
                ->orderBy('apellido_paterno')
                ->get(),
            'cuentas' => User::query()
                ->whereNotNull('colaborador_id')
                ->with(['colaborador.contratos' => fn ($query) => $query->latest('fecha_inicio')->with('cargo:id,nombre')])
                ->latest()
                ->get(['id', 'colaborador_id', 'name', 'email', 'rol', 'activo', 'created_at']),
            'roles' => RolSistema::where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'codigo', 'descripcion', 'modulos']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'colaborador_id' => ['required', 'integer', 'exists:colaboradores,id', 'unique:users,colaborador_id'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'rol' => ['required', Rule::exists('roles_sistema', 'codigo')->where('activo', true)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $colaborador = Colaborador::with('contratos')->findOrFail($validated['colaborador_id']);
        $tieneContratoRegistrado = $colaborador->contratos
            ->contains(fn ($contrato) => $contrato->estado !== 'Cancelado');

        if (! $tieneContratoRegistrado) {
            throw ValidationException::withMessages([
                'colaborador_id' => 'El colaborador necesita un contrato registrado para crear su cuenta.',
            ]);
        }

        User::create([
            'colaborador_id' => $colaborador->id,
            'name' => $colaborador->nombre_completo,
            'email' => $validated['email'],
            'rol' => $validated['rol'],
            'password' => Hash::make($validated['password']),
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        return back()->with('success', 'Cuenta de acceso creada correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->colaborador_id, 404);

        $validated = $request->validate([
            'email' => ['required', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'rol' => ['required', Rule::exists('roles_sistema', 'codigo')->where('activo', true)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'email' => $validated['email'],
            'rol' => $validated['rol'],
            ...(filled($validated['password'] ?? null)
                ? ['password' => Hash::make($validated['password'])]
                : []),
        ]);

        if ($validated['rol'] !== 'area_limpieza') {
            $user->camiones()->detach();
        }

        return back()->with('success', 'Cuenta actualizada correctamente.');
    }

    public function cambiarEstado(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->colaborador_id, 404);
        $validated = $request->validate(['activo' => ['required', 'boolean']]);
        $user->update($validated);

        if (! $validated['activo']) {
            $user->camiones()->detach();
        }

        return back()->with('success', $validated['activo'] ? 'Cuenta activada.' : 'Cuenta desactivada.');
    }
}
