<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\Reporte;
use App\Models\RutaRecoleccion;
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
                ->get(),
            'routeNames' => [
                'store' => $request->routeIs('administracion.*')
                    ? 'administracion.rutas.store'
                    : 'admin.rutas.store',
                'cancelar' => $request->routeIs('administracion.*')
                    ? 'administracion.rutas.cancelar'
                    : 'admin.rutas.cancelar',
            ],
        ]);
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

            $ordenados = $this->ordenarRuta($reportes->all());
            $distanciaTotal = collect($ordenados)->sum('distancia');
            $ruta = RutaRecoleccion::create([
                'camion_id' => $camion->id,
                'generado_por' => $request->user()->id,
                'fecha' => $validated['fecha'],
                'estado' => 'Planificada',
                'distancia_estimada_km' => round($distanciaTotal, 2),
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

        return back()->with('success', 'Ruta generada y asignada al equipo del vehículo.');
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
     * @param  array<int, Reporte>  $reportes
     * @return array<int, array{reporte: Reporte, distancia: float}>
     */
    private function ordenarRuta(array $reportes): array
    {
        $actualLat = -12.0504;
        $actualLng = -75.2215;
        $ruta = [];

        while ($reportes !== []) {
            $indiceCercano = 0;
            $distanciaMinima = PHP_FLOAT_MAX;

            foreach ($reportes as $indice => $reporte) {
                $distancia = $this->distanciaHaversine(
                    $actualLat,
                    $actualLng,
                    $reporte->latitud,
                    $reporte->longitud
                );

                if ($distancia < $distanciaMinima) {
                    $distanciaMinima = $distancia;
                    $indiceCercano = $indice;
                }
            }

            $siguiente = $reportes[$indiceCercano];
            $ruta[] = ['reporte' => $siguiente, 'distancia' => $distanciaMinima];
            $actualLat = $siguiente->latitud;
            $actualLng = $siguiente->longitud;
            array_splice($reportes, $indiceCercano, 1);
        }

        return $ruta;
    }

    private function distanciaHaversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $radioTierra = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $radioTierra * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
