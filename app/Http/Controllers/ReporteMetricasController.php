<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ReporteMetricasController extends Controller
{
    public function index(): Response
    {
        $conteos = Reporte::query()
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $total = (int) $conteos->sum();
        $atendidos = (int) ($conteos['Atendido'] ?? 0);

        return Inertia::render('Reportes/Metricas', [
            'resumen' => [
                'total' => $total,
                'atendidos' => $atendidos,
                'tasaAtencion' => $total === 0 ? 0 : round(($atendidos / $total) * 100, 1),
                'promedioHoras' => $this->promedioHorasAtencion(),
                'personalActivo' => User::query()
                    ->where('activo', true)
                    ->where('rol', '!=', 'ciudadano')
                    ->count(),
                'vehiculosDisponibles' => Camion::query()->where('estado', 'Disponible')->count(),
            ],
            'porEstado' => collect(['Pendiente', 'Asignado', 'En atención', 'Atendido'])
                ->map(fn (string $estado) => [
                    'label' => $estado,
                    'total' => (int) ($conteos[$estado] ?? 0),
                ]),
            'porTipo' => $this->totalesPorTipo(),
            'porMes' => $this->totalesPorMes(),
        ]);
    }

    private function promedioHorasAtencion(): float
    {
        $tiempos = Reporte::query()
            ->where('estado', 'Atendido')
            ->whereNotNull('atendido_at')
            ->get(['created_at', 'atendido_at']);

        if ($tiempos->isEmpty()) {
            return 0.0;
        }

        return round($tiempos->avg(
            fn (Reporte $reporte) => $reporte->created_at->diffInMinutes($reporte->atendido_at) / 60
        ), 1);
    }

    /**
     * @return Collection<int, array{label: string, total: int}>
     */
    private function totalesPorTipo()
    {
        return Reporte::query()
            ->selectRaw("COALESCE(NULLIF(tipo_incidencia, ''), ?) as label, COUNT(*) as total", ['Por clasificar'])
            ->groupByRaw("COALESCE(NULLIF(tipo_incidencia, ''), ?)", ['Por clasificar'])
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($fila) => [
                'label' => (string) $fila->label,
                'total' => (int) $fila->total,
            ]);
    }

    /**
     * @return Collection<int, array{label: string, total: int}>
     */
    private function totalesPorMes()
    {
        $desde = now()->subMonths(5)->startOfMonth();
        $fechas = Reporte::query()
            ->where('created_at', '>=', $desde)
            ->pluck('created_at');

        return collect(range(5, 0))->map(function (int $mesesAtras) use ($fechas) {
            $fecha = now()->subMonths($mesesAtras);

            return [
                'label' => $fecha->format('m/Y'),
                'total' => $fechas->filter(
                    fn (Carbon $creado) => $creado->isSameMonth($fecha)
                )->count(),
            ];
        });
    }
}
