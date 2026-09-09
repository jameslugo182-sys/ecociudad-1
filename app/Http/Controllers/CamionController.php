<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CamionController extends Controller
{
    private const ESTADOS = ['Disponible', 'Mantenimiento', 'Inactivo'];

    public function index(): Response
    {
        return Inertia::render('Administracion/Camiones', [
            'camiones' => Camion::query()
                ->with(['personal' => fn ($query) => $query->select('users.id', 'name', 'email')])
                ->orderBy('codigo')
                ->get(),
            'personalLimpieza' => User::query()
                ->where('rol', 'area_limpieza')
                ->where('activo', true)
                ->with('camiones:id,codigo,placa')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'estados' => self::ESTADOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validarCamion($request);

        Camion::create($this->normalizarCamion($validated));

        return back()->with('success', 'Camión registrado correctamente.');
    }

    public function update(Request $request, Camion $camion): RedirectResponse
    {
        $validated = $this->validarCamion($request, $camion);
        $camion->update($this->normalizarCamion($validated));

        return back()->with('success', 'Datos del camión actualizados.');
    }

    public function destroy(Camion $camion): RedirectResponse
    {
        $camion->delete();

        return back()->with('success', 'Camión eliminado.');
    }

    public function asignarEquipo(Request $request, Camion $camion): RedirectResponse
    {
        $usuarioLimpieza = Rule::exists('users', 'id')
            ->where('rol', 'area_limpieza')
            ->where('activo', true);

        $validated = $request->validate([
            'conductor_id' => ['required', 'integer', $usuarioLimpieza],
            'recolector_ids' => ['required', 'array', 'size:3'],
            'recolector_ids.*' => ['required', 'integer', 'distinct', $usuarioLimpieza],
        ], [
            'recolector_ids.size' => 'El equipo debe tener exactamente tres recolectores.',
        ]);

        $integrantes = collect([
            $validated['conductor_id'],
            ...$validated['recolector_ids'],
        ])->map(fn ($id) => (int) $id);

        if ($integrantes->unique()->count() !== 4) {
            throw ValidationException::withMessages([
                'equipo' => 'El conductor y los tres recolectores deben ser personas diferentes.',
            ]);
        }

        $ocupados = User::query()
            ->whereIn('id', $integrantes)
            ->whereHas('camiones', fn ($query) => $query->where('camiones.id', '!=', $camion->id))
            ->pluck('name');

        if ($ocupados->isNotEmpty()) {
            throw ValidationException::withMessages([
                'equipo' => 'Ya pertenecen a otro camión: '.$ocupados->join(', ').'.',
            ]);
        }

        DB::transaction(function () use ($camion, $validated) {
            $equipo = [
                $validated['conductor_id'] => ['puesto' => 'conductor'],
            ];

            foreach ($validated['recolector_ids'] as $recolectorId) {
                $equipo[$recolectorId] = ['puesto' => 'recolector'];
            }

            $camion->personal()->sync($equipo);
        });

        return back()->with('success', "Equipo del camión {$camion->codigo} asignado correctamente.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validarCamion(Request $request, ?Camion $camion = null): array
    {
        return $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:30',
                Rule::unique('camiones', 'codigo')->ignore($camion),
            ],
            'placa' => [
                'required',
                'string',
                'max:10',
                Rule::unique('camiones', 'placa')->ignore($camion),
            ],
            'marca' => ['nullable', 'string', 'max:80'],
            'modelo' => ['nullable', 'string', 'max:80'],
            'anio' => ['nullable', 'integer', 'min:1980', 'max:'.(now()->year + 1)],
            'capacidad_kg' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'estado' => ['required', Rule::in(self::ESTADOS)],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizarCamion(array $validated): array
    {
        return [
            ...$validated,
            'codigo' => Str::upper($validated['codigo']),
            'placa' => Str::upper($validated['placa']),
        ];
    }
}
