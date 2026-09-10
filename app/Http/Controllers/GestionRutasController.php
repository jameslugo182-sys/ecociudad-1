<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\Reporte;
use App\Models\RutaRecoleccion;
use App\Services\TrazadoRutaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class GestionRutasController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Rutas/Index', [
            'reportes' => Reporte::query()
                ->with('user:id,name')
                ->where('estado', '!=', 'Atendido')
                ->whereDoesntHave(
                    'rutas',
                    fn ($query) => $query->whereIn('rutas_recoleccion.estado', ['Planificada', 'En curso'])
                )
                ->latest()
                ->get([
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
            'equipos' => Camion::query()
                ->with(['personal' => fn ($query) => $query->select('users.id', 'name')])
                ->where('estado', 'Disponible')
                ->orderBy('codigo')
                ->get(),
            'rutas' => RutaRecoleccion::query()
                ->with([
                    'camion.personal' => fn ($query) => $query->select('users.id', 'name'),
                    'reportes.user:id,name',
                ])
                ->latest('fecha')
                ->latest()
                ->take(20)
                ->get()
                ->each(function (RutaRecoleccion $ruta) {
                    if (in_array($ruta->estado, ['Planificada', 'En curso'], true)) {
                        $ruta->completarTrazadoVial();
                    }
                }),
            'routeNames' => $this->routeNames($request),
        ]);
    }

    public function horarios(Request $request): Response
    {
        return Inertia::render('Rutas/Horarios', [
            'rutas' => RutaRecoleccion::query()
                ->whereIn('estado', ['Planificada', 'En curso'])
                ->with([
                    'camion.personal' => fn ($query) => $query->select('users.id', 'name'),
                    'reportes:id,descripcion,estado,latitud,longitud,ruta_orden',
                ])
                ->latest('fecha')
                ->latest()
                ->get(),
            'routeNames' => $this->routeNames($request),
        ]);
    }

    public function actualizarHorario(Request $request, RutaRecoleccion $ruta): RedirectResponse
    {
        abort_unless(
            in_array($ruta->estado, ['Planificada', 'En curso'], true),
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
        $validated = $request->validate([
            'camion_id' => [
                'required',
                Rule::exists('camiones', 'id')->where('estado', 'Disponible'),
            ],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'reporte_ids' => ['required', 'array', 'min:1'],
            'reporte_ids.*' => ['required', 'integer', 'distinct', 'exists:reportes,id'],
        ]);

        $camion = Camion::with('personal')->findOrFail($validated['camion_id']);

        if ($camion->personal->count() !== 4
            || $camion->personal->where('pivot.puesto', 'conductor')->count() !== 1
            || $camion->personal->where('pivot.puesto', 'recolector')->count() !== 3) {
            throw ValidationException::withMessages([
                'camion_id' => 'El vehículo debe tener un conductor y tres recolectores.',
            ]);
        }

        DB::transaction(function () use ($request, $validated, $camion) {
            $reportes = Reporte::query()
                ->whereIn('id', $validated['reporte_ids'])
                ->where('estado', '!=', 'Atendido')
                ->whereDoesntHave(
                    'rutas',
                    fn ($query) => $query->whereIn('rutas_recoleccion.estado', ['Planificada', 'En curso'])
                )
                ->lockForUpdate()
                ->get();

            if ($reportes->count() !== count($validated['reporte_ids'])) {
                throw ValidationException::withMessages([
                    'reporte_ids' => 'Una o más incidencias ya fueron asignadas o atendidas.',
                ]);
            }

            $trazado = app(TrazadoRutaService::class)->trazar($reportes->all());
            $ordenados = $trazado['paradas'];
            $ruta = RutaRecoleccion::create([
                'camion_id' => $camion->id,
                'generado_por' => $request->user()->id,
                'fecha' => $validated['fecha'],
                'estado' => 'Planificada',
                'distancia_estimada_km' => $trazado['distancia_km'],
                'geometria' => $trazado['geometria'],
            ]);
            $conductor = $camion->personal->firstWhere('pivot.puesto', 'conductor');
            $puntos = [];

            foreach ($ordenados as $indice => $punto) {
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

    /**
     * @return array<string, string>
     */
    private function routeNames(Request $request): array
    {
        $esAdministracion = $request->routeIs('administracion.*');

        return [
            'index' => $esAdministracion ? 'administracion.rutas.index' : 'admin.rutas.index',
            'store' => $esAdministracion ? 'administracion.rutas.store' : 'admin.rutas.store',
            'cancelar' => $esAdministracion ? 'administracion.rutas.cancelar' : 'admin.rutas.cancelar',
            'horarios' => $esAdministracion ? 'administracion.rutas.horarios' : 'admin.rutas.horarios',
            'horario' => $esAdministracion ? 'administracion.rutas.horario' : 'admin.rutas.horario',
        ];
    }
}
