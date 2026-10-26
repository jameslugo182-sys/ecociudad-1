<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\Reporte;
use App\Models\RutaRecoleccion;
use App\Models\User;
use App\Services\PlanificadorFlotaService;
use App\Services\TrazadoRutaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class GestionRutasController extends Controller
{
    private const USERS_ID = 'users.id';

    public function __construct(
        private readonly TrazadoRutaService $trazado,
        private readonly PlanificadorFlotaService $planificador,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Rutas/Index', [
            'reportes' => $this->reportesDisponibles()->get([
                'id',
                'user_id',
                'foto_path',
                'latitud',
                'longitud',
                'descripcion',
                'estado',
                'tipo_incidencia',
                'prioridad',
                'created_at',
            ]),
            'equipos' => $this->camionesDisponibles()
                ->with(['personal' => fn ($query) => $query->select(self::USERS_ID, 'name')])
                ->orderBy('codigo')
                ->get(),
            'rutas' => RutaRecoleccion::query()
                ->with([
                    'camion.personal' => fn ($query) => $query->select(self::USERS_ID, 'name'),
                    'reportes.user:id,name',
                ])
                ->latest('fecha')
                ->latest()
                ->take(20)
                ->get()
                ->each(function (RutaRecoleccion $ruta) {
                    if ($ruta->estaActiva()) {
                        $ruta->completarTrazadoVial();
                    }
                }),
            'routeNames' => $this->routeNames($request),
        ]);
    }

    public function horarios(Request $request): Response
    {
        return Inertia::render('Rutas/Horarios', [
            'rutas' => $this->rutasActivas()
                ->with([
                    'camion.personal' => fn ($query) => $query->select(self::USERS_ID, 'name'),
                    'reportes:id,descripcion,estado,latitud,longitud,ruta_orden',
                ])
                ->latest('fecha')
                ->latest()
                ->get(),
            'routeNames' => $this->routeNames($request),
        ]);
    }

    public function cronograma(Request $request): Response
    {
        $validated = $request->validate([
            'mes' => ['nullable', 'date_format:Y-m'],
        ]);

        $mes = $validated['mes'] ?? now()->format('Y-m');

        return Inertia::render('Rutas/Cronograma', [
            'mes' => $mes,
            'rutas' => $this->rutasActivas()
                ->whereNotNull('hora_inicio')
                ->whereNotNull('hora_fin')
                ->whereNotNull('dias_recoleccion')
                ->with([
                    'camion:id,codigo,placa',
                    'reportes:id',
                ])
                ->latest('fecha')
                ->latest()
                ->get()
                ->filter(fn (RutaRecoleccion $ruta) => filled($ruta->horario_resumen))
                ->values(),
            'routeNames' => $this->routeNames($request),
        ]);
    }

    public function actualizarHorario(Request $request, RutaRecoleccion $ruta): RedirectResponse
    {
        abort_unless(
            $ruta->estaActiva(),
            422,
            'Solo se puede programar el horario de una ruta activa.'
        );

        $validated = $request->validate([
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'dias_recoleccion' => ['required', 'array', 'min:1'],
            'dias_recoleccion.*' => ['integer', 'distinct', Rule::in([1, 2, 3, 4, 5, 6, 7])],
            'horario_nota' => ['nullable', 'string', 'max:160'],
        ], [
            'hora_fin.after' => 'La hora de término debe ser posterior a la hora de inicio.',
            'dias_recoleccion.required' => 'Selecciona al menos un día de recolección.',
        ]);

        $ruta->update([
            'hora_inicio' => $validated['hora_inicio'],
            'hora_fin' => $validated['hora_fin'],
            'dias_recoleccion' => collect($validated['dias_recoleccion'])
                ->map(fn ($dia) => (int) $dia)
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'horario_nota' => $validated['horario_nota'] ?? null,
        ]);
        $ruta->load('camion:id,codigo');

        return back()->with('success', "Horario asignado a la flota {$ruta->camion->codigo}.");
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validarAlta($request);
        $camion = $this->camionConEquipoCompleto((int) $validated['camion_id']);

        DB::transaction(function () use ($request, $validated, $camion) {
            $reportes = $this->reportesElegiblesParaIds($validated['reporte_ids']);

            if ($reportes->count() !== count($validated['reporte_ids'])) {
                throw ValidationException::withMessages([
                    'reporte_ids' => 'Una o más incidencias ya fueron asignadas o atendidas.',
                ]);
            }

            $this->persistirRuta($request->user(), $camion, $validated['fecha'], $reportes);
        });

        return back()
            ->with('success', 'Ruta trazada por las calles más rápidas y asignada al equipo del vehículo.')
            ->with('registered', true);
    }

    public function cancelar(RutaRecoleccion $ruta): RedirectResponse
    {
        abort_unless($ruta->estado === 'Planificada', 422, 'Solo se pueden cancelar rutas planificadas.');

        DB::transaction(function () use ($ruta) {
            $ruta->load('reportes');
            $ruta->update(['estado' => 'Cancelada']);

            foreach ($ruta->reportes as $reporte) {
                $reporte->update([
                    'assigned_to' => null,
                    'estado' => 'Pendiente',
                    'ruta_orden' => null,
                    'assigned_at' => null,
                ]);
            }
        });

        return back()->with('success', 'Ruta cancelada; las incidencias volvieron a estar disponibles.');
    }

    public function proponer(): JsonResponse
    {
        $reportes = $this->reportesDisponibles()->get([
            'id',
            'user_id',
            'latitud',
            'longitud',
            'descripcion',
            'estado',
            'tipo_incidencia',
            'prioridad',
        ]);
        $vehiculos = $this->camionesDisponibles()
            ->with('personal')
            ->get()
            ->filter(fn (Camion $camion) => $camion->tieneEquipoCompleto())
            ->count();

        if ($reportes->isEmpty()) {
            throw ValidationException::withMessages([
                'reportes' => 'No hay incidencias disponibles para generar recorridos.',
            ]);
        }

        if ($vehiculos < 1) {
            throw ValidationException::withMessages([
                'equipos' => 'Necesitas al menos un vehículo con equipo completo (1 conductor y 3 recolectores).',
            ]);
        }

        return response()->json([
            'vehiculos' => $vehiculos,
            'incidencias' => $reportes->count(),
            'propuestas' => $this->planificador->proponer($reportes->all(), $vehiculos),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validarAlta(Request $request): array
    {
        return $request->validate([
            'camion_id' => [
                'required',
                Rule::exists('camiones', 'id')->where('estado', 'Disponible'),
            ],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'reporte_ids' => ['required', 'array', 'min:1'],
            'reporte_ids.*' => ['required', 'integer', 'distinct', 'exists:reportes,id'],
        ], [
            'camion_id.required' => 'Selecciona el vehículo de la ruta.',
            'fecha.required' => 'Indica la fecha de recolección.',
            'fecha.after_or_equal' => 'La fecha de la ruta no puede ser anterior a hoy.',
            'reporte_ids.required' => 'Selecciona al menos una incidencia.',
            'reporte_ids.min' => 'Selecciona al menos una incidencia.',
        ]);
    }

    private function camionConEquipoCompleto(int $camionId): Camion
    {
        $camion = Camion::with('personal')->findOrFail($camionId);

        if (! $camion->tieneEquipoCompleto()) {
            throw ValidationException::withMessages([
                'camion_id' => 'El vehículo debe tener un conductor y tres recolectores.',
            ]);
        }

        return $camion;
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Reporte>
     */
    private function reportesElegiblesParaIds(array $ids): Collection
    {
        return Reporte::query()
            ->whereIn('id', $ids)
            ->where('estado', '!=', 'Atendido')
            ->whereDoesntHave(
                'rutas',
                fn ($query) => $query->whereIn('rutas_recoleccion.estado', RutaRecoleccion::ESTADOS_ACTIVAS)
            )
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  Collection<int, Reporte>  $reportes
     */
    private function persistirRuta(User $autor, Camion $camion, string $fecha, Collection $reportes): void
    {
        $trazado = $this->trazado->trazar($reportes->all());
        $conductor = $this->conductorDelEquipo($camion);
        $ruta = RutaRecoleccion::create([
            'camion_id' => $camion->id,
            'generado_por' => $autor->id,
            'fecha' => $fecha,
            'estado' => 'Planificada',
            'distancia_estimada_km' => $trazado['distancia_km'],
            'geometria' => $trazado['geometria'],
        ]);
        $puntos = [];

        foreach ($trazado['paradas'] as $indice => $punto) {
            $puntos[$punto['reporte']->id] = [
                'orden' => $indice + 1,
                'distancia_desde_anterior_km' => round($punto['distancia'], 2),
            ];
            $punto['reporte']->update([
                'assigned_to' => $conductor->id,
                'estado' => 'Asignado',
                'ruta_orden' => $indice + 1,
                'assigned_at' => now(),
            ]);
        }

        $ruta->reportes()->attach($puntos);
    }

    private function conductorDelEquipo(Camion $camion): User
    {
        // camionConEquipoCompleto() ya garantiza que el equipo tiene exactamente un conductor.
        return $camion->personal->firstWhere('pivot.puesto', 'conductor');
    }

    private function reportesDisponibles(): Builder
    {
        return Reporte::query()
            ->with('user:id,name')
            ->where('estado', '!=', 'Atendido')
            ->whereDoesntHave(
                'rutas',
                fn ($query) => $query->whereIn('rutas_recoleccion.estado', RutaRecoleccion::ESTADOS_ACTIVAS)
            )
            ->latest();
    }

    private function rutasActivas(): Builder
    {
        return RutaRecoleccion::query()->activas();
    }

    private function camionesDisponibles(): Builder
    {
        return Camion::query()->where('estado', 'Disponible');
    }

    /**
     * @return array<string, string>
     */
    private function routeNames(Request $request): array
    {
        $prefijo = $request->routeIs('administracion.*') ? 'administracion.rutas' : 'admin.rutas';

        return [
            'index' => $prefijo.'.index',
            'store' => $prefijo.'.store',
            'proponer' => $prefijo.'.proponer',
            'cancelar' => $prefijo.'.cancelar',
            'horarios' => $prefijo.'.horarios',
            'horario' => $prefijo.'.horario',
            'cronograma' => $prefijo.'.cronograma',
        ];
    }
}
