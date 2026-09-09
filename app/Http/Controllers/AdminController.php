<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use App\Models\User;
use App\Notifications\ReporteAtendido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    private const ESTADOS = ['Pendiente', 'Asignado', 'En atención', 'Atendido'];

    public function dashboard(Request $request): Response
    {
        $esAdministracion = $request->routeIs('administracion.*');
        $filters = $request->validate([
            'estado' => ['nullable', Rule::in(self::ESTADOS)],
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $reportes = Reporte::query()
            ->with(['user:id,name,email', 'responsable:id,name'])
            ->when(
                $filters['estado'] ?? null,
                fn ($query, $estado) => $query->where('estado', $estado)
            )
            ->when($filters['buscar'] ?? null, function ($query, $buscar) {
                $query->where(function ($subquery) use ($buscar) {
                    $subquery
                        ->where('descripcion', 'like', "%{$buscar}%")
                        ->orWhereHas(
                            'user',
                            fn ($userQuery) => $userQuery->where('name', 'like', "%{$buscar}%")
                        );
                });
            })
            ->orderByRaw('prioridad IS NULL, prioridad ASC')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $conteos = Reporte::query()
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return Inertia::render('Admin/Dashboard', [
            'reportes' => $reportes,
            'areasLimpieza' => User::query()
                ->where('rol', 'area_limpieza')
                ->where('activo', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'filters' => [
                'estado' => $filters['estado'] ?? '',
                'buscar' => $filters['buscar'] ?? '',
            ],
            'resumen' => [
                'total' => Reporte::count(),
                'pendientes' => (int) ($conteos['Pendiente'] ?? 0),
                'asignados' => (int) (($conteos['Asignado'] ?? 0) + ($conteos['En atención'] ?? 0)),
                'atendidos' => (int) ($conteos['Atendido'] ?? 0),
            ],
            'estados' => self::ESTADOS,
            'routeNames' => [
                'index' => $esAdministracion ? 'administracion.incidencias.index' : 'admin.reportes.index',
                'mapa' => $esAdministracion ? 'administracion.rutas.index' : 'admin.rutas.index',
                'estado' => $esAdministracion ? 'administracion.incidencias.estado' : 'admin.reportes.estado',
                'asignar' => $esAdministracion ? 'administracion.incidencias.asignar' : 'admin.reportes.asignar',
            ],
        ]);
    }

    public function actualizarEstado(Request $request, Reporte $reporte): RedirectResponse
    {
        $validated = $request->validate([
            'estado' => ['required', Rule::in(self::ESTADOS)],
        ]);
        $debeNotificar = $reporte->estado !== 'Atendido' && $validated['estado'] === 'Atendido';

        $reporte->update([
            'estado' => $validated['estado'],
            'atendido_at' => $validated['estado'] === 'Atendido' ? now() : null,
        ]);

        if ($debeNotificar) {
            $reporte->user->notify(new ReporteAtendido($reporte));
        }

        return back()->with('success', "El reporte #{$reporte->id} cambió a {$validated['estado']}.");
    }

    public function asignar(Request $request, Reporte $reporte): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to' => [
                'required',
                Rule::exists('users', 'id')
                    ->where('rol', 'area_limpieza')
                    ->where('activo', true),
            ],
        ]);

        $reporte->update([
            'assigned_to' => $validated['assigned_to'],
            'estado' => 'Asignado',
            'assigned_at' => now(),
            'ruta_orden' => null,
        ]);

        return back()->with('success', "El reporte #{$reporte->id} fue asignado al área de limpieza.");
    }

    public function generarRuta(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to' => [
                'required',
                Rule::exists('users', 'id')
                    ->where('rol', 'area_limpieza')
                    ->where('activo', true),
            ],
        ]);

        $reportes = Reporte::query()
            ->where('assigned_to', $validated['assigned_to'])
            ->whereIn('estado', ['Asignado', 'En atención'])
            ->orderByRaw('prioridad IS NULL, prioridad ASC')
            ->oldest()
            ->get();

        if ($reportes->isEmpty()) {
            return back()->with('error', 'El responsable no tiene incidencias pendientes para generar una ruta.');
        }

        $ordenados = $this->ordenarPorProximidad($reportes->all());

        DB::transaction(function () use ($ordenados) {
            foreach ($ordenados as $indice => $reporte) {
                $reporte->update(['ruta_orden' => $indice + 1]);
            }
        });

        return back()->with('success', 'Ruta optimizada generada con las incidencias asignadas.');
    }

    /**
     * Heurística de vecino más cercano: inicia por la incidencia más prioritaria
     * y reduce los desplazamientos entre los siguientes puntos.
     *
     * @param  array<int, Reporte>  $reportes
     * @return array<int, Reporte>
     */
    private function ordenarPorProximidad(array $reportes): array
    {
        $ruta = [array_shift($reportes)];

        while ($reportes !== []) {
            $actual = $ruta[array_key_last($ruta)];
            $indiceMasCercano = 0;
            $distanciaMinima = PHP_FLOAT_MAX;

            foreach ($reportes as $indice => $candidato) {
                $distancia = $this->distanciaHaversine($actual, $candidato);

                if ($distancia < $distanciaMinima) {
                    $distanciaMinima = $distancia;
                    $indiceMasCercano = $indice;
                }
            }

            $ruta[] = $reportes[$indiceMasCercano];
            array_splice($reportes, $indiceMasCercano, 1);
        }

        return $ruta;
    }

    private function distanciaHaversine(Reporte $origen, Reporte $destino): float
    {
        $radioTierraKm = 6371;
        $dLat = deg2rad($destino->latitud - $origen->latitud);
        $dLng = deg2rad($destino->longitud - $origen->longitud);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($origen->latitud))
            * cos(deg2rad($destino->latitud))
            * sin($dLng / 2) ** 2;

        return $radioTierraKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
