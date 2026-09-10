<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CamionController extends Controller
{
    private const ESTADOS = ['Disponible', 'Mantenimiento', 'Inactivo'];

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::in(self::ESTADOS)],
        ]);

        $camiones = Camion::query()
            ->withCount('personal')
            ->when($filters['buscar'] ?? null, function ($query, $buscar) {
                $query->where(function ($subquery) use ($buscar) {
                    $subquery
                        ->where('codigo', 'like', "%{$buscar}%")
                        ->orWhere('placa', 'like', "%{$buscar}%")
                        ->orWhere('marca', 'like', "%{$buscar}%")
                        ->orWhere('modelo', 'like', "%{$buscar}%");
                });
            })
            ->when($filters['estado'] ?? null, fn ($query, $estado) => $query->where('estado', $estado))
            ->orderBy('codigo')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Administracion/Camiones', [
            'camiones' => $camiones,
            'filters' => [
                'buscar' => $filters['buscar'] ?? '',
                'estado' => $filters['estado'] ?? '',
            ],
            'estados' => self::ESTADOS,
            'siguienteCodigo' => $this->siguienteCodigo(),
        ]);
    }

    public function equipos(): Response
    {
        return Inertia::render('Administracion/Equipos', [
            'camiones' => Camion::query()
                ->disponiblesParaEquipo()
                ->with(['personal' => fn ($query) => $query->select('users.id', 'name', 'email')])
                ->orderBy('codigo')
                ->get(),
            'personalLimpieza' => User::query()
                ->where('rol', 'area_limpieza')
                ->where('activo', true)
                ->with('camiones:id,codigo,placa')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validarCamion($request);
        $datos = $this->normalizarCamion($validated);
        $datos['codigo'] = $this->siguienteCodigo();

        if ($request->hasFile('foto')) {
            $datos['foto_path'] = $request->file('foto')->store('vehiculos', 'public');
        }

        Camion::create($datos);

        return back()
            ->with('success', 'Camión registrado correctamente.')
            ->with('registered', true);
    }

    public function update(Request $request, Camion $camion): RedirectResponse
    {
        $validated = $this->validarCamion($request, $camion);
        $datos = $this->normalizarCamion($validated);

        if ($request->hasFile('foto')) {
            if ($camion->foto_path) {
                Storage::disk('public')->delete($camion->foto_path);
            }
            $datos['foto_path'] = $request->file('foto')->store('vehiculos', 'public');
        }

        $camion->update($datos);

        return back()->with('success', 'Datos del camión actualizados.');
    }

    public function destroy(Camion $camion): RedirectResponse
    {
        if ($camion->foto_path) {
            Storage::disk('public')->delete($camion->foto_path);
        }

        $camion->delete();

        return back()->with('success', 'Camión eliminado.');
    }

    public function asignarEquipo(Request $request, Camion $camion): RedirectResponse
    {
        if ($camion->enMantenimiento()) {
            throw ValidationException::withMessages([
                'equipo' => 'El vehículo está en mantenimiento y no puede recibir un equipo.',
            ]);
        }

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

        return back()
            ->with('success', "Equipo del camión {$camion->codigo} asignado correctamente.")
            ->with('registered', true);
    }

    /**
     * @return array<string, mixed>
     */
    private function validarCamion(Request $request, ?Camion $camion = null): array
    {
        return $request->validate([
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
            'foto' => [
                $camion ? 'nullable' : 'required',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120',
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizarCamion(array $validated): array
    {
        unset($validated['foto']);

        return [
            ...$validated,
            'placa' => Str::upper($validated['placa']),
        ];
    }

    private function siguienteCodigo(): string
    {
        $ultimo = Camion::query()
            ->where('codigo', 'like', 'VH%')
            ->orderByDesc('id')
            ->value('codigo');

        $numero = 1;
        if (is_string($ultimo) && preg_match('/VH(\d+)/i', $ultimo, $coincidencias)) {
            $numero = ((int) $coincidencias[1]) + 1;
        }

        return 'VH'.str_pad((string) $numero, 3, '0', STR_PAD_LEFT);
    }
}
