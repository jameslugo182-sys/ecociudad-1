<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use App\Models\Colaborador;
use App\Models\Contrato;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ContratoController extends Controller
{
    private const TIPOS = ['CAS', 'Locación de servicios', 'Plazo fijo', 'Indeterminado', 'Prácticas'];

    private const ESTADOS = ['Vigente', 'Suspendido', 'Finalizado', 'Cancelado'];

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'cargo_id' => ['nullable', 'integer', 'exists:cargos,id'],
            'estado' => ['nullable', Rule::in(self::ESTADOS)],
        ]);

        $contratos = Contrato::query()
            ->with(['colaborador', 'cargo:id,nombre'])
            ->when($filters['buscar'] ?? null, function ($query, $buscar) {
                $termino = mb_strtolower($buscar);
                $query->where(function ($subquery) use ($termino) {
                    $subquery
                        ->whereRaw('LOWER(numero) LIKE ?', ["%{$termino}%"])
                        ->orWhereHas('colaborador', function ($colaboradorQuery) use ($termino) {
                            $colaboradorQuery
                                ->whereRaw('LOWER(documento) LIKE ?', ["%{$termino}%"])
                                ->orWhereRaw('LOWER(nombres) LIKE ?', ["%{$termino}%"])
                                ->orWhereRaw('LOWER(apellido_paterno) LIKE ?', ["%{$termino}%"]);
                        });
                });
            })
            ->when($filters['cargo_id'] ?? null, fn ($query, $cargoId) => $query->where('cargo_id', $cargoId))
            ->when($filters['estado'] ?? null, fn ($query, $estado) => $query->where('estado', $estado))
            ->latest('fecha_inicio')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Contratos/Registro', [
            'contratos' => $contratos,
            'cargos' => Cargo::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'tipos' => self::TIPOS,
            'estados' => self::ESTADOS,
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validar($request);

        DB::transaction(function () use ($request, $validated) {
            $colaborador = Colaborador::updateOrCreate(
                ['documento' => $validated['documento']],
                $this->datosColaborador($validated)
            );

            $this->validarCruceFechas($colaborador, $validated);

            Contrato::create([
                ...$this->datosContrato($validated),
                'colaborador_id' => $colaborador->id,
                'documento_path' => $request->file('archivo_contrato')
                    ? $request->file('archivo_contrato')->store('contratos', 'public')
                    : null,
            ]);
        });

        return back()
            ->with('success', 'Contrato registrado correctamente.')
            ->with('registered', true);
    }

    public function update(Request $request, Contrato $contrato): RedirectResponse
    {
        $validated = $this->validar($request, $contrato);

        DB::transaction(function () use ($request, $validated, $contrato) {
            $contrato->colaborador->update($this->datosColaborador($validated));
            $this->validarCruceFechas($contrato->colaborador, $validated, $contrato);

            $datos = $this->datosContrato($validated);

            if ($request->hasFile('archivo_contrato')) {
                Storage::disk('public')->delete($contrato->documento_path);
                $datos['documento_path'] = $request->file('archivo_contrato')->store('contratos', 'public');
            }

            $contrato->update($datos);
        });

        return back()->with('success', 'Contrato actualizado correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?Contrato $contrato = null): array
    {
        return $request->validate([
            'documento' => [
                'required',
                'string',
                'max:15',
                ...($contrato
                    ? [Rule::unique('colaboradores', 'documento')->ignore($contrato->colaborador_id)]
                    : []),
            ],
            'nombres' => ['required', 'string', 'max:120'],
            'apellido_paterno' => ['required', 'string', 'max:80'],
            'apellido_materno' => ['nullable', 'string', 'max:80'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email_personal' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'numero' => ['required', 'string', 'max:50', Rule::unique('contratos', 'numero')->ignore($contrato)],
            'cargo_id' => ['required', Rule::exists('cargos', 'id')->where('activo', true)],
            'tipo' => ['required', Rule::in(self::TIPOS)],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'remuneracion' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'jornada_horas' => ['nullable', 'integer', 'min:1', 'max:168'],
            'area' => ['required', 'string', 'max:120'],
            'sede' => ['nullable', 'string', 'max:120'],
            'estado' => ['required', Rule::in(self::ESTADOS)],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'archivo_contrato' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function datosColaborador(array $validated): array
    {
        return [
            'nombres' => $validated['nombres'],
            'apellido_paterno' => $validated['apellido_paterno'],
            'apellido_materno' => $validated['apellido_materno'] ?? null,
            'fecha_nacimiento' => $validated['fecha_nacimiento'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
            'email' => $validated['email_personal'] ?? null,
            'direccion' => $validated['direccion'] ?? null,
            'activo' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function datosContrato(array $validated): array
    {
        return [
            'numero' => $validated['numero'],
            'cargo_id' => $validated['cargo_id'],
            'tipo' => $validated['tipo'],
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_fin' => $validated['fecha_fin'],
            'remuneracion' => $validated['remuneracion'] ?? null,
            'jornada_horas' => $validated['jornada_horas'] ?? null,
            'area' => $validated['area'],
            'sede' => $validated['sede'] ?? null,
            'estado' => $validated['estado'],
            'observaciones' => $validated['observaciones'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function validarCruceFechas(
        Colaborador $colaborador,
        array $validated,
        ?Contrato $ignorar = null
    ): void {
        $existeCruce = $colaborador->contratos()
            ->when($ignorar, fn ($query) => $query->whereKeyNot($ignorar->id))
            ->whereNotIn('estado', ['Cancelado'])
            ->whereDate('fecha_inicio', '<=', $validated['fecha_fin'])
            ->whereDate('fecha_fin', '>=', $validated['fecha_inicio'])
            ->exists();

        if ($existeCruce) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'El colaborador ya tiene un contrato que se cruza con estas fechas.',
            ]);
        }
    }
}
