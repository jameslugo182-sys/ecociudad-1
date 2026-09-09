<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use App\Notifications\ReporteAtendido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class LimpiezaController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $usuarioId = $request->user()->id;
        $reportes = Reporte::query()
            ->where(function ($query) use ($usuarioId) {
                $query->where('assigned_to', $usuarioId)
                    ->orWhereHas('rutas', function ($rutaQuery) use ($usuarioId) {
                        $rutaQuery->whereIn('rutas_recoleccion.estado', ['Planificada', 'En curso'])
                            ->whereHas('camion.personal', fn ($personalQuery) => $personalQuery->where('users.id', $usuarioId));
                    });
            })
            ->with('user:id,name')
            ->orderByRaw('ruta_orden IS NULL, ruta_orden ASC')
            ->orderByRaw('prioridad IS NULL, prioridad ASC')
            ->latest()
            ->get();

        return Inertia::render('Limpieza/Dashboard', [
            'reportes' => $reportes,
            'resumen' => [
                'pendientes' => $reportes->whereIn('estado', ['Asignado', 'En atención'])->count(),
                'atendidos' => $reportes->where('estado', 'Atendido')->count(),
                'conRuta' => $reportes
                    ->whereIn('estado', ['Asignado', 'En atención'])
                    ->whereNotNull('ruta_orden')
                    ->count(),
            ],
        ]);
    }

    public function iniciar(Request $request, Reporte $reporte): RedirectResponse
    {
        $this->autorizarAsignacion($request, $reporte);
        abort_unless($reporte->estado === 'Asignado', 422, 'La incidencia no puede iniciarse en su estado actual.');

        $reporte->update(['estado' => 'En atención']);

        return back()->with('success', "Atención del reporte #{$reporte->id} iniciada.");
    }

    public function registrarEvidencia(Request $request, Reporte $reporte): RedirectResponse
    {
        $this->autorizarAsignacion($request, $reporte);
        abort_if($reporte->estado === 'Atendido', 422, 'La incidencia ya fue atendida.');

        $validated = $request->validate([
            'evidencia' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ], [
            'evidencia.required' => 'Debes adjuntar una fotografía de la limpieza.',
            'evidencia.image' => 'La evidencia debe ser una imagen válida.',
            'evidencia.max' => 'La evidencia no debe pesar más de 10MB.',
        ]);

        if ($reporte->evidencia_path) {
            Storage::disk('public')->delete($reporte->evidencia_path);
        }

        $reporte->update([
            'evidencia_path' => $validated['evidencia']->store('evidencias_limpieza', 'public'),
            'estado' => 'Atendido',
            'atendido_at' => now(),
        ]);
        $reporte->load('rutas.reportes');
        foreach ($reporte->rutas->whereIn('estado', ['Planificada', 'En curso']) as $ruta) {
            $ruta->update([
                'estado' => $ruta->reportes->every(fn (Reporte $parada) => $parada->id === $reporte->id || $parada->estado === 'Atendido')
                    ? 'Finalizada'
                    : 'En curso',
                'iniciada_at' => $ruta->iniciada_at ?? now(),
                'finalizada_at' => $ruta->reportes->every(fn (Reporte $parada) => $parada->id === $reporte->id || $parada->estado === 'Atendido')
                    ? now()
                    : null,
            ]);
        }
        $reporte->user->notify(new ReporteAtendido($reporte));

        return back()->with('success', "Reporte #{$reporte->id} cerrado con evidencia.");
    }

    private function autorizarAsignacion(Request $request, Reporte $reporte): void
    {
        $perteneceAlEquipo = $reporte->rutas()
            ->whereIn('rutas_recoleccion.estado', ['Planificada', 'En curso'])
            ->whereHas('camion.personal', fn ($query) => $query->where('users.id', $request->user()->id))
            ->exists();

        abort_unless($reporte->assigned_to === $request->user()->id || $perteneceAlEquipo, 403);
    }
}
