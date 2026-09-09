<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReporteMetricasController extends Controller
{
    public function index(): Response
    {
        $reportes = Reporte::all();
        $atendidos = $reportes->where('estado', 'Atendido');
        $conTiempo = $atendidos->filter(fn (Reporte $reporte) => $reporte->atendido_at);
        $promedioHoras = $conTiempo->isEmpty()
            ? 0
            : round($conTiempo->avg(
                fn (Reporte $reporte) => $reporte->created_at->diffInMinutes($reporte->atendido_at) / 60
            ), 1);

        $porEstado = collect(['Pendiente', 'Asignado', 'En atención', 'Atendido'])
            ->map(fn (string $estado) => [
                'label' => $estado,
                'total' => $reportes->where('estado', $estado)->count(),
            ]);

        $porTipo = $reportes
            ->groupBy(fn (Reporte $reporte) => $reporte->tipo_incidencia ?: 'Por clasificar')
            ->map(fn ($grupo, $tipo) => ['label' => $tipo, 'total' => $grupo->count()])
            ->sortByDesc('total')
            ->values()
            ->take(6);

        $porMes = collect(range(5, 0))
            ->map(function (int $mesesAtras) use ($reportes) {
                $fecha = now()->subMonths($mesesAtras);

                return [
                    'label' => $fecha->format('m/Y'),
                    'total' => $reportes->filter(
                        fn (Reporte $reporte) => $reporte->created_at->isSameMonth(Carbon::parse($fecha))
                    )->count(),
                ];
            });

        return Inertia::render('Reportes/Metricas', [
            'resumen' => [
                'total' => $reportes->count(),
                'atendidos' => $atendidos->count(),
                'tasaAtencion' => $reportes->isEmpty()
                    ? 0
                    : round(($atendidos->count() / $reportes->count()) * 100, 1),
                'promedioHoras' => $promedioHoras,
                'personalActivo' => User::where('activo', true)->where('rol', '!=', 'ciudadano')->count(),
                'vehiculosDisponibles' => Camion::where('estado', 'Disponible')->count(),
            ],
            'porEstado' => $porEstado,
            'porTipo' => $porTipo,
            'porMes' => $porMes,
        ]);
    }
}
