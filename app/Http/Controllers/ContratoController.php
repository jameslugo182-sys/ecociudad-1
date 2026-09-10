<?php

namespace App\Http\Controllers;

use App\Models\AreaUnidad;
use App\Models\Cargo;
use App\Models\Colaborador;
use App\Models\Contrato;
use App\Models\SedeTrabajo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ContratoController extends Controller
{
    private const TIPOS = ['CAS', 'Locación de servicios', 'Plazo fijo', 'Indeterminado', 'Prácticas'];

    private const ESTADOS = ['Vigente', 'Suspendido', 'Finalizado', 'Cancelado'];

    public const SEXOS = ['Masculino', 'Femenino'];

    public const ESTADOS_CIVILES = ['Soltero/a', 'Casado/a', 'Conviviente', 'Divorciado/a', 'Viudo/a'];

    public const NIVELES_EDUCATIVOS = [
        'Primaria',
        'Secundaria',
        'Técnico',
        'Universitario',
        'Posgrado',
    ];

    public function index(Request $request): InertiaResponse
    {
        $filters = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'cargo_id' => ['nullable', 'integer', 'exists:cargos,id'],
            'estado' => ['nullable', Rule::in(self::ESTADOS)],
        ]);

        $contratos = Contrato::query()
            ->with(['colaborador', 'cargo:id,nombre', 'areaUnidad:id,nombre', 'sedeTrabajo:id,nombre'])
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
            'areas' => AreaUnidad::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'sedes' => SedeTrabajo::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'tipos' => self::TIPOS,
            'estados' => self::ESTADOS,
            'sexos' => self::SEXOS,
            'estadosCiviles' => self::ESTADOS_CIVILES,
            'nivelesEducativos' => self::NIVELES_EDUCATIVOS,
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
                if ($contrato->documento_path) {
                    Storage::disk('public')->delete($contrato->documento_path);
                }
                $datos['documento_path'] = $request->file('archivo_contrato')->store('contratos', 'public');
            }

            $contrato->update($datos);
        });

        return back()->with('success', 'Contrato actualizado correctamente.');
    }

    public function formato(Request $request): Response
    {
        $validated = $this->validar($request, null, true);
        $cargo = Cargo::find($validated['cargo_id']);
        $area = AreaUnidad::find($validated['area_id']);
        $sede = isset($validated['sede_id']) ? SedeTrabajo::find($validated['sede_id']) : null;

        $pdf = Pdf::loadView('contratos.formato', [
            'datos' => $validated,
            'cargo' => $cargo,
            'area' => $area,
            'sede' => $sede,
            'nombreCompleto' => collect([
                $validated['nombres'],
                $validated['apellido_paterno'],
                $validated['apellido_materno'] ?? null,
            ])->filter()->join(' '),
        ])->setPaper('a4');

        $documento = $validated['documento'] ?? 'colaborador';

        return $pdf->download("formato-contrato-{$documento}.pdf");
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?Contrato $contrato = null, bool $paraFormato = false): array
    {
        $archivo = $paraFormato
            ? ['nullable']
            : ['nullable', 'file', 'mimes:pdf', 'max:10240'];

        return $request->validate([
            'documento' => [
                'required',
                'string',
                'max:15',
                ...($paraFormato
                    ? []
                    : ($contrato
                        ? [Rule::unique('colaboradores', 'documento')->ignore($contrato->colaborador_id)]
                        : [])),
            ],
            'nombres' => ['required', 'string', 'max:120'],
            'apellido_paterno' => ['required', 'string', 'max:80'],
            'apellido_materno' => ['nullable', 'string', 'max:80'],
            'sexo' => ['nullable', Rule::in(self::SEXOS)],
            'estado_civil' => ['nullable', Rule::in(self::ESTADOS_CIVILES)],
            'nacionalidad' => ['nullable', 'string', 'max:80'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email_personal' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'departamento' => ['nullable', 'string', 'max:80'],
            'provincia' => ['nullable', 'string', 'max:80'],
            'distrito' => ['nullable', 'string', 'max:80'],
            'contacto_emergencia' => ['nullable', 'string', 'max:120'],
            'telefono_emergencia' => ['nullable', 'string', 'max:20'],
            'nivel_educativo' => ['nullable', Rule::in(self::NIVELES_EDUCATIVOS)],
            'institucion_estudios' => ['nullable', 'string', 'max:160'],
            'especialidad' => ['nullable', 'string', 'max:160'],
            'grado_titulo' => ['nullable', 'string', 'max:120'],
            'anio_egreso' => ['nullable', 'integer', 'min:1950', 'max:'.(now()->year + 1)],
            'numero' => [
                'required',
                'string',
                'max:50',
                ...($paraFormato ? [] : [Rule::unique('contratos', 'numero')->ignore($contrato)]),
            ],
            'cargo_id' => ['required', Rule::exists('cargos', 'id')->where('activo', true)],
            'tipo' => ['required', Rule::in(self::TIPOS)],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'remuneracion' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'jornada_horas' => ['nullable', 'integer', 'min:1', 'max:168'],
            'area_id' => ['required', Rule::exists('areas_unidad', 'id')->where('activo', true)],
            'sede_id' => ['nullable', Rule::exists('sedes_trabajo', 'id')->where('activo', true)],
            'estado' => ['required', Rule::in(self::ESTADOS)],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'archivo_contrato' => $archivo,
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
            'sexo' => $validated['sexo'] ?? null,
            'estado_civil' => $validated['estado_civil'] ?? null,
            'nacionalidad' => $validated['nacionalidad'] ?? 'Peruana',
            'fecha_nacimiento' => $validated['fecha_nacimiento'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
            'email' => $validated['email_personal'] ?? null,
            'direccion' => $validated['direccion'] ?? null,
            'departamento' => $validated['departamento'] ?? null,
            'provincia' => $validated['provincia'] ?? null,
            'distrito' => $validated['distrito'] ?? null,
            'contacto_emergencia' => $validated['contacto_emergencia'] ?? null,
            'telefono_emergencia' => $validated['telefono_emergencia'] ?? null,
            'nivel_educativo' => $validated['nivel_educativo'] ?? null,
            'institucion_estudios' => $validated['institucion_estudios'] ?? null,
            'especialidad' => $validated['especialidad'] ?? null,
            'grado_titulo' => $validated['grado_titulo'] ?? null,
            'anio_egreso' => $validated['anio_egreso'] ?? null,
            'activo' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function datosContrato(array $validated): array
    {
        $area = AreaUnidad::findOrFail($validated['area_id']);
        $sede = isset($validated['sede_id']) ? SedeTrabajo::find($validated['sede_id']) : null;

        return [
            'numero' => $validated['numero'],
            'cargo_id' => $validated['cargo_id'],
            'tipo' => $validated['tipo'],
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_fin' => $validated['fecha_fin'],
            'remuneracion' => $validated['remuneracion'] ?? null,
            'jornada_horas' => $validated['jornada_horas'] ?? null,
            'area_id' => $area->id,
            'sede_id' => $sede?->id,
            'area' => $area->nombre,
            'sede' => $sede?->nombre,
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
